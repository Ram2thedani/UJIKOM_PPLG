<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/75 backdrop-blur-sm p-4"
      @click.self="closeModal"
    >
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-200">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <QrCode class="w-5 h-5 text-indigo-400" />
            <h3 class="font-bold text-lg">Scan QR Kartu Siswa</h3>
          </div>
          <button
            type="button"
            class="p-2 rounded-xl hover:bg-white/10 transition"
            @click="closeModal"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="p-5 sm:p-6 space-y-5">
          <!-- Camera Viewport -->
          <div class="relative rounded-2xl overflow-hidden bg-slate-950 border border-slate-800 min-h-[270px] flex items-center justify-center">
            <div id="qr-reader-region" class="w-full"></div>

            <div
              v-if="cameraError"
              class="absolute inset-0 flex flex-col items-center justify-center text-center p-6 bg-slate-900 text-white"
            >
              <CameraOff class="w-10 h-10 text-amber-400 mb-3" />
              <p class="font-bold text-sm mb-1">Kamera Tidak Dapat Diakses</p>
              <p class="text-xs text-slate-300 max-w-xs leading-relaxed">{{ cameraError }}</p>
              <button
                type="button"
                class="mt-4 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-xs font-bold"
                @click="startScanner"
              >
                Coba Aktifkan Kamera Lagi
              </button>
            </div>
          </div>

          <p class="text-xs text-center text-slate-500">
            Arahkan kamera belakang tablet ke QR Code pada dashboard atau kartu peserta siswa.
          </p>

          <!-- Manual Fallback -->
          <div class="pt-4 border-t border-slate-200">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">
              Atau Masukkan NIS / Token QR Manual
            </label>
            <form class="flex gap-2" @submit.prevent="submitManual">
              <input
                v-model.trim="manualToken"
                type="text"
                placeholder="Contoh: 24251001 atau UUID token..."
                class="flex-1 px-4 py-3 rounded-xl border border-slate-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 outline-none text-sm"
              />
              <button
                type="submit"
                :disabled="!manualToken"
                class="px-5 py-3 min-h-[48px] rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold text-sm transition"
              >
                Cari
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { Html5Qrcode } from 'html5-qrcode'
import { CameraOff, QrCode, X } from 'lucide-vue-next'

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close', 'scanned'])

const manualToken = ref('')
const cameraError = ref('')
let html5QrCode = null
let isStopping = false

async function startScanner() {
  cameraError.value = ''
  await stopScanner()
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
        if (isStopping) return
        isStopping = true
        await stopScanner()
        emit('scanned', decodedText.trim())
      },
      () => {
        // Abaikan error per-frame saat mencari QR
      }
    )
  } catch (err) {
    cameraError.value =
      'Pastikan izin kamera diberikan dan akses menggunakan HTTPS atau localhost. Anda tetap dapat memasukkan NIS/Token secara manual di bawah.'
  }
}

async function stopScanner() {
  if (html5QrCode) {
    try {
      if (html5QrCode.isScanning) {
        await html5QrCode.stop()
      }
      await html5QrCode.clear()
    } catch {
      // Abaikan error cleanup kamera
    } finally {
      html5QrCode = null
    }
  }
  isStopping = false
}

async function closeModal() {
  await stopScanner()
  manualToken.value = ''
  emit('close')
}

async function submitManual() {
  if (!manualToken.value) return
  const token = manualToken.value
  await stopScanner()
  manualToken.value = ''
  emit('scanned', token)
}

watch(
  () => props.open,
  async (val) => {
    if (val) {
      await startScanner()
    } else {
      await stopScanner()
    }
  }
)

onBeforeUnmount(stopScanner)
</script>
