<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/75 backdrop-blur-sm p-3 sm:p-4"
      @click.self="closeModal"
    >
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl overflow-hidden border border-slate-200 max-h-[92vh] flex flex-col">
        <!-- Header -->
        <div class="px-5 sm:px-6 py-4 bg-slate-900 text-white flex items-center justify-between shrink-0">
          <div class="flex items-center gap-2.5">
            <ScanText v-if="scanMode === 'ocr'" class="w-5 h-5 text-emerald-400" />
            <QrCode v-else class="w-5 h-5 text-indigo-400" />
            <h3 class="font-bold text-base sm:text-lg">Scan Kartu Peserta Siswa</h3>
          </div>
          <button
            type="button"
            class="p-2 rounded-xl hover:bg-white/10 transition"
            @click="closeModal"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Mode Switcher -->
        <div class="px-5 pt-4 shrink-0">
          <div class="grid grid-cols-2 gap-2 p-1.5 rounded-2xl bg-slate-100 border border-slate-200">
            <button
              type="button"
              class="min-h-[44px] px-3 rounded-xl text-xs sm:text-sm font-extrabold flex items-center justify-center gap-2 transition"
              :class="
                scanMode === 'ocr'
                  ? 'bg-emerald-600 text-white shadow-sm'
                  : 'text-slate-600 hover:bg-white/70'
              "
              @click="switchMode('ocr')"
            >
              <ScanText class="w-4 h-4" />
              <span>Semi-OCR NIS</span>
            </button>

            <button
              type="button"
              class="min-h-[44px] px-3 rounded-xl text-xs sm:text-sm font-extrabold flex items-center justify-center gap-2 transition"
              :class="
                scanMode === 'qr'
                  ? 'bg-indigo-600 text-white shadow-sm'
                  : 'text-slate-600 hover:bg-white/70'
              "
              @click="switchMode('qr')"
            >
              <QrCode class="w-4 h-4" />
              <span>Scan QR Code</span>
            </button>
          </div>
        </div>

        <!-- Body -->
        <div class="p-5 sm:p-6 space-y-4 overflow-y-auto">
          <!-- MODE 1: SEMI-OCR BERDASARKAN NIS -->
          <template v-if="scanMode === 'ocr'">
            <div class="relative rounded-2xl overflow-hidden bg-slate-950 border border-slate-800 min-h-[250px] flex items-center justify-center">
              <video
                ref="videoRef"
                autoplay
                playsinline
                muted
                class="w-full max-h-[280px] object-cover"
              ></video>

              <!-- ROI Guide Overlay untuk Angka NIS -->
              <div
                v-if="!ocrCameraError"
                class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center"
              >
                <div class="w-[78%] h-[76px] rounded-xl border-2 border-emerald-400 shadow-[0_0_0_9999px_rgba(15,23,42,0.45)] flex items-center justify-center">
                  <span class="px-2 py-0.5 rounded bg-emerald-500/90 text-white text-[10px] font-extrabold uppercase tracking-wider -translate-y-11">
                    Posisikan Angka NIS di Dalam Kotak
                  </span>
                </div>
              </div>

              <!-- Fallback jika kamera stream tidak tersedia -->
              <div
                v-if="ocrCameraError"
                class="absolute inset-0 flex flex-col items-center justify-center text-center p-5 bg-slate-900 text-white"
              >
                <CameraOff class="w-9 h-9 text-amber-400 mb-2" />
                <p class="font-bold text-sm">Kamera Live Tidak Tersedia</p>
                <p class="text-xs text-slate-300 mt-1 max-w-xs leading-relaxed">{{ ocrCameraError }}</p>
              </div>
            </div>

            <!-- Action Buttons OCR -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
              <button
                type="button"
                :disabled="ocrProcessing || Boolean(ocrCameraError)"
                class="min-h-[50px] px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-extrabold text-sm flex items-center justify-center gap-2 shadow-md shadow-emerald-600/20 transition active:scale-95"
                @click="captureAndRunOcr"
              >
                <Loader2 v-if="ocrProcessing" class="w-5 h-5 animate-spin" />
                <Camera v-else class="w-5 h-5" />
                <span>{{ ocrProcessing ? 'Membaca NIS...' : 'Ambil & Baca NIS' }}</span>
              </button>

              <label class="min-h-[50px] px-4 rounded-2xl border-2 border-slate-300 hover:bg-slate-50 text-slate-800 font-bold text-sm flex items-center justify-center gap-2 cursor-pointer transition active:scale-95">
                <ImagePlus class="w-5 h-5 text-indigo-600" />
                <span>Foto / Upload Kartu</span>
                <input
                  type="file"
                  accept="image/*"
                  capture="environment"
                  class="hidden"
                  @change="handleOcrFileUpload"
                />
              </label>
            </div>

            <!-- Hasil Semi-OCR & Konfirmasi -->
            <div v-if="ocrPreviewUrl || ocrRawText || manualToken" class="rounded-2xl bg-slate-50 border border-slate-200 p-4 space-y-3">
              <div v-if="ocrPreviewUrl" class="flex items-center gap-3">
                <img
                  :src="ocrPreviewUrl"
                  alt="Preview area NIS"
                  class="h-12 w-36 object-contain rounded-lg border border-slate-300 bg-white"
                />
                <div class="text-xs text-slate-600 min-w-0">
                  <p class="font-bold text-slate-800">Hasil Pembacaan OCR:</p>
                  <p class="font-mono truncate">{{ ocrRawText || '(tidak terbaca angka jelas)' }}</p>
                </div>
              </div>

              <!-- Kandidat NIS hasil deteksi -->
              <div v-if="ocrCandidates.length > 0" class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-bold text-slate-500">Pilih NIS:</span>
                <button
                  v-for="cand in ocrCandidates"
                  :key="cand"
                  type="button"
                  class="px-3 py-1.5 rounded-xl text-xs font-mono font-extrabold border transition"
                  :class="
                    manualToken === cand
                      ? 'bg-emerald-600 border-emerald-600 text-white'
                      : 'bg-white border-slate-300 text-slate-800 hover:bg-emerald-50'
                  "
                  @click="manualToken = cand"
                >
                  {{ cand }}
                </button>
              </div>

              <form class="flex gap-2" @submit.prevent="submitManual">
                <input
                  v-model.trim="manualToken"
                  type="text"
                  inputmode="numeric"
                  placeholder="Konfirmasi / koreksi NIS siswa..."
                  class="flex-1 min-h-[48px] px-4 rounded-xl border border-slate-300 bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/15 outline-none text-base font-mono font-bold"
                />
                <button
                  type="submit"
                  :disabled="!manualToken"
                  class="px-5 min-h-[48px] rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-extrabold text-sm flex items-center gap-1.5 transition"
                >
                  <CheckCircle2 class="w-4 h-4" />
                  <span>Cari Siswa</span>
                </button>
              </form>
            </div>
          </template>

          <!-- MODE 2: SCAN QR CODE -->
          <template v-else>
            <div class="relative rounded-2xl overflow-hidden bg-slate-950 border border-slate-800 min-h-[260px] flex items-center justify-center">
              <div id="qr-reader-region" class="w-full"></div>

              <div
                v-if="qrCameraError"
                class="absolute inset-0 flex flex-col items-center justify-center text-center p-6 bg-slate-900 text-white"
              >
                <CameraOff class="w-10 h-10 text-amber-400 mb-3" />
                <p class="font-bold text-sm mb-1">Kamera QR Tidak Dapat Diakses</p>
                <p class="text-xs text-slate-300 max-w-xs leading-relaxed">{{ qrCameraError }}</p>
                <button
                  type="button"
                  class="mt-4 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-xs font-bold"
                  @click="startQrScanner"
                >
                  Coba Lagi
                </button>
              </div>
            </div>

            <div class="pt-3 border-t border-slate-200">
              <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">
                Atau Input NIS / Token QR Manual
              </label>
              <form class="flex gap-2" @submit.prevent="submitManual">
                <input
                  v-model.trim="manualToken"
                  type="text"
                  placeholder="Contoh: 24251001 atau UUID token..."
                  class="flex-1 min-h-[48px] px-4 rounded-xl border border-slate-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 outline-none text-sm font-mono"
                />
                <button
                  type="submit"
                  :disabled="!manualToken"
                  class="px-5 min-h-[48px] rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold text-sm transition"
                >
                  Cari
                </button>
              </form>
            </div>
          </template>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { Html5Qrcode } from 'html5-qrcode'
import { createWorker } from 'tesseract.js'
import {
  Camera,
  CameraOff,
  CheckCircle2,
  ImagePlus,
  Loader2,
  QrCode,
  ScanText,
  X,
} from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close', 'scanned'])
const toast = useToast()

const scanMode = ref('ocr')
const manualToken = ref('')

// QR state
const qrCameraError = ref('')
let html5QrCode = null
let isStoppingQr = false

// Semi-OCR state
const videoRef = ref(null)
const ocrCameraError = ref('')
const ocrProcessing = ref(false)
const ocrPreviewUrl = ref('')
const ocrRawText = ref('')
const ocrCandidates = ref([])
let mediaStream = null

async function startOcrCamera() {
  ocrCameraError.value = ''
  stopOcrCamera()
  await nextTick()

  if (!navigator.mediaDevices?.getUserMedia) {
    ocrCameraError.value =
      'Browser membutuhkan HTTPS atau localhost untuk live video. Gunakan tombol "Foto / Upload Kartu" atau input NIS manual.'
    return
  }

  try {
    mediaStream = await navigator.mediaDevices.getUserMedia({
      video: {
        facingMode: { ideal: 'environment' },
        width: { ideal: 1280 },
        height: { ideal: 720 },
      },
      audio: false,
    })

    if (videoRef.value) {
      videoRef.value.srcObject = mediaStream
    }
  } catch {
    ocrCameraError.value =
      'Izin kamera tidak tersedia. Gunakan tombol "Foto / Upload Kartu" di sebelah kanan untuk memotret NIS pada kartu peserta.'
  }
}

function stopOcrCamera() {
  if (mediaStream) {
    mediaStream.getTracks().forEach((track) => track.stop())
    mediaStream = null
  }
  if (videoRef.value) {
    videoRef.value.srcObject = null
  }
}

async function runOcrOnCanvas(canvas) {
  ocrProcessing.value = true
  ocrCandidates.value = []
  ocrRawText.value = ''

  try {
    // Tingkatkan kontras grayscale agar angka NIS terbaca lebih tajam
    const ctx = canvas.getContext('2d')
    const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height)
    const d = imgData.data
    for (let i = 0; i < d.length; i += 4) {
      const gray = 0.299 * d[i] + 0.587 * d[i + 1] + 0.114 * d[i + 2]
      const contrasted = gray > 135 ? 255 : gray < 85 ? 0 : gray
      d[i] = contrasted
      d[i + 1] = contrasted
      d[i + 2] = contrasted
    }
    ctx.putImageData(imgData, 0, 0)
    ocrPreviewUrl.value = canvas.toDataURL('image/png')

    const worker = await createWorker('eng')
    await worker.setParameters({
      tessedit_char_whitelist: '0123456789',
    })
    const {
      data: { text },
    } = await worker.recognize(canvas)
    await worker.terminate()

    const cleaned = (text || '').trim()
    ocrRawText.value = cleaned

    const matches = cleaned.match(/\d{4,14}/g) || []
    const uniqueCandidates = [...new Set(matches)]
    ocrCandidates.value = uniqueCandidates

    if (uniqueCandidates.length > 0) {
      manualToken.value = uniqueCandidates[0]
      toast.success(`NIS terdeteksi: ${uniqueCandidates[0]}. Periksa lalu klik Cari Siswa.`)
    } else {
      const digitsOnly = cleaned.replace(/\D/g, '')
      if (digitsOnly.length >= 4) {
        manualToken.value = digitsOnly
        ocrCandidates.value = [digitsOnly]
      } else {
        toast.info('Angka NIS kurang jelas. Coba dekatkan kartu atau koreksi NIS secara manual.')
      }
    }
  } catch {
    toast.error('Gagal memproses OCR gambar. Silakan coba lagi atau ketik NIS manual.')
  } finally {
    ocrProcessing.value = false
  }
}

async function captureAndRunOcr() {
  const video = videoRef.value
  if (!video || !video.videoWidth) {
    toast.error('Kamera belum siap.')
    return
  }

  // Crop fokus ke kotak target di tengah (78% lebar, 32% tinggi)
  const vw = video.videoWidth
  const vh = video.videoHeight
  const cropW = Math.round(vw * 0.78)
  const cropH = Math.round(vh * 0.34)
  const cropX = Math.round((vw - cropW) / 2)
  const cropY = Math.round((vh - cropH) / 2)

  const canvas = document.createElement('canvas')
  canvas.width = cropW
  canvas.height = cropH
  const ctx = canvas.getContext('2d')
  ctx.drawImage(video, cropX, cropY, cropW, cropH, 0, 0, cropW, cropH)

  await runOcrOnCanvas(canvas)
}

async function handleOcrFileUpload(event) {
  const file = event.target.files?.[0]
  if (!file) return

  const img = new Image()
  const objectUrl = URL.createObjectURL(file)
  img.onload = async () => {
    const maxW = 1000
    const scale = img.width > maxW ? maxW / img.width : 1
    const canvas = document.createElement('canvas')
    canvas.width = Math.round(img.width * scale)
    canvas.height = Math.round(img.height * scale)
    const ctx = canvas.getContext('2d')
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height)
    URL.revokeObjectURL(objectUrl)
    await runOcrOnCanvas(canvas)
  }
  img.src = objectUrl
  event.target.value = ''
}

async function startQrScanner() {
  qrCameraError.value = ''
  await stopQrScanner()
  await nextTick()

  const regionEl = document.getElementById('qr-reader-region')
  if (!regionEl) return

  try {
    html5QrCode = new Html5Qrcode('qr-reader-region')
    await html5QrCode.start(
      { facingMode: 'environment' },
      {
        fps: 10,
        qrbox: { width: 230, height: 230 },
        aspectRatio: 1.333,
      },
      async (decodedText) => {
        if (isStoppingQr) return
        isStoppingQr = true
        await stopAllCameras()
        emit('scanned', decodedText.trim())
      },
      () => {}
    )
  } catch {
    qrCameraError.value =
      'Kamera membutuhkan HTTPS atau localhost. Anda bisa memasukkan NIS/Token secara manual di bawah.'
  }
}

async function stopQrScanner() {
  if (html5QrCode) {
    try {
      if (html5QrCode.isScanning) {
        await html5QrCode.stop()
      }
      await html5QrCode.clear()
    } catch {
      // Abaikan error cleanup
    } finally {
      html5QrCode = null
    }
  }
  isStoppingQr = false
}

async function stopAllCameras() {
  stopOcrCamera()
  await stopQrScanner()
}

async function switchMode(mode) {
  if (scanMode.value === mode) return
  await stopAllCameras()
  scanMode.value = mode
  if (props.open) {
    if (mode === 'ocr') await startOcrCamera()
    else await startQrScanner()
  }
}

async function closeModal() {
  await stopAllCameras()
  manualToken.value = ''
  ocrPreviewUrl.value = ''
  ocrRawText.value = ''
  ocrCandidates.value = []
  emit('close')
}

async function submitManual() {
  if (!manualToken.value) return
  const token = manualToken.value
  await stopAllCameras()
  manualToken.value = ''
  ocrPreviewUrl.value = ''
  ocrRawText.value = ''
  ocrCandidates.value = []
  emit('scanned', token)
}

watch(
  () => props.open,
  async (val) => {
    if (val) {
      if (scanMode.value === 'ocr') await startOcrCamera()
      else await startQrScanner()
    } else {
      await stopAllCameras()
    }
  }
)

onBeforeUnmount(stopAllCameras)
</script>
