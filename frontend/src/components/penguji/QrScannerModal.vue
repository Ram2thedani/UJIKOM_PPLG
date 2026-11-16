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
            <ScanText class="w-5 h-5 text-emerald-400" />
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

        <!-- Body -->
        <div class="p-5 sm:p-6 space-y-4 overflow-y-auto">
          <!-- MODE SCAN KARTU -->
          <template v-if="scanMode === 'ocr'">
            <div class="relative rounded-2xl overflow-hidden bg-slate-950 border border-slate-800 min-h-[300px] sm:min-h-[340px] flex items-center justify-center">
              <video
                ref="videoRef"
                autoplay
                playsinline
                muted
                class="w-full h-full object-cover"
              ></video>

              <!-- Panduan Bingkai Kartu Utuh -->
              <div
                v-if="!ocrCameraError"
                class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center p-4"
              >
                <div
                  class="relative overflow-hidden w-[88%] sm:w-[75%] aspect-[1.58/1] rounded-2xl border-2 shadow-[0_0_0_9999px_rgba(15,23,42,0.6)] flex items-end justify-center pb-3 transition-colors duration-300"
                  :class="scanPhase === 'searching' ? 'border-white/70' : 'border-emerald-400'"
                >
                  <!-- Garis pindai saat menunggu kartu -->
                  <div v-if="scanPhase === 'searching' || scanPhase === 'steady'" class="scan-line"></div>

                  <!-- Loader saat kartu dibaca -->
                  <div
                    v-if="scanPhase === 'reading'"
                    class="absolute inset-0 bg-slate-900/55 flex items-center justify-center"
                  >
                    <div class="w-12 h-12 rounded-full border-4 border-white/25 border-t-emerald-400 animate-spin"></div>
                  </div>

                  <!-- Berhasil -->
                  <div
                    v-else-if="scanPhase === 'success'"
                    class="absolute inset-0 bg-emerald-500/25 flex items-center justify-center"
                  >
                    <CheckCircle2 class="w-14 h-14 text-emerald-300 drop-shadow" />
                  </div>

                  <span class="relative px-3 py-1 rounded-full bg-emerald-500/95 text-white text-[11px] sm:text-xs font-extrabold tracking-wide shadow-md">
                    {{ statusLabel }}
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

            <!-- Action Buttons / Upload Fallback -->
            <div class="grid grid-cols-1 gap-2.5">
              <label class="min-h-[50px] px-4 rounded-2xl border-2 border-slate-300 hover:bg-slate-50 text-slate-800 font-bold text-sm flex items-center justify-center gap-2 cursor-pointer transition active:scale-95">
                <ImagePlus class="w-5 h-5 text-indigo-600" />
                <span>Foto / Upload Kartu (Alternatif)</span>
                <input
                  type="file"
                  accept="image/*"
                  capture="environment"
                  class="hidden"
                  @change="handleOcrFileUpload"
                />
              </label>
            </div>

            <!-- Hasil Nama Siswa & Field NIS -->
            <div v-if="ocrRawText || manualToken" class="rounded-2xl bg-slate-50 border border-slate-200 p-4 space-y-3">
              <div class="flex items-center justify-between text-xs text-slate-600">
                <span class="font-bold text-slate-800">Siswa Ditemukan:</span>
                <span class="font-bold text-emerald-700 bg-emerald-50 px-3 py-1 rounded-lg border border-emerald-200 text-sm">
                  {{ detectedStudentName || '(Menunggu pencocokan nama...)' }}
                </span>
              </div>

              <!-- Kandidat NIS hasil deteksi -->
              <div v-if="ocrCandidates.length > 0" class="flex flex-wrap items-center gap-2">
                <!-- <span class="text-xs font-bold text-slate-500">Pilih NIS:</span> -->
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
                  {{ cand }} {{ getStudentNameByNis(cand) ? `- ${getStudentNameByNis(cand)}` : '' }}
                </button>
              </div>

              <!-- <p v-if="ocrRawText" class="text-[11px] text-slate-500 break-all">
                Teks terbaca: <span class="font-mono">{{ ocrRawText.replace(/\s+/g, ' ') }}</span>
              </p> -->

              <form class="flex gap-2" @submit.prevent="submitManual">
                <input
                  v-model.trim="manualToken"
                  type="text"
                  inputmode="numeric"
                  placeholder="Nomor NIS siswa..."
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
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { createWorker } from 'tesseract.js'
import {
  CameraOff,
  CheckCircle2,
  ImagePlus,
  ScanText,
  X,
} from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  // Daftar siswa untuk pencocokan (format: [{ nis: '...', name: '...' }, ...])
  students: {
    type: Array,
    default: () => [],
  },
  // Fungsi pencarian siswa berdasarkan NIS tanpa terikat kelas (async, return { name } atau null).
  // Dipakai kalau NIS tidak ada di daftar `students`.
  lookupStudent: {
    type: Function,
    default: null,
  },
  // Eksperimen: otomatis "klik" tombol Cari Siswa begitu satu siswa terdeteksi
  autoSubmit: {
    type: Boolean,
    default: true,
  },
  // Jeda (ms) sebelum auto-submit, supaya nama siswa sempat terlihat
  autoSubmitDelay: {
    type: Number,
    default: 700,
  },
})

const emit = defineEmits(['close', 'scanned'])
const toast = useToast()

const scanMode = ref('ocr')
const manualToken = ref('')
const resolvedNames = ref({}) // cache hasil lookup: { [nis]: name | null }
const lookingUp = ref(false)

// State pemindai kartu
const videoRef = ref(null)
const ocrCameraError = ref('')
const ocrProcessing = ref(false)
const ocrRawText = ref('')
const ocrCandidates = ref([])
const ocrSuccess = ref(false)
const cardSteady = ref(false)
let mediaStream = null
let ocrInterval = null
let ocrBusy = false
let ocrWorkerPromise = null
let lastHit = { nis: null, count: 0 }
let scanCycle = 0
let autoSubmitTimer = null

// Auto capture: ambil gambar otomatis saat kartu terdeteksi diam di dalam bingkai
const SCAN_INTERVAL_MS = 350 // seberapa sering frame dicek
const STEADY_FRAMES_NEEDED = 3 // jumlah cek berturut-turut yang stabil (~1 detik)
const MOTION_MAX = 7 // batas selisih piksel antar-frame (makin kecil = harus makin diam)
const EDGE_MIN = 0.05 // batas minimal kerapatan tepi (membedakan kartu dari meja kosong)
const RETRY_COOLDOWN_MS = 1500 // jeda sebelum mencoba lagi setelah gagal membaca
const SAMPLE_W = 96
const SAMPLE_H = 60
let sampleCanvas = null
let prevFrame = null
let steadyCount = 0
let cooldownUntil = 0

const scanPhase = computed(() => {
  if (ocrSuccess.value) return 'success'
  if (ocrProcessing.value) return 'reading'
  if (cardSteady.value) return 'steady'
  return 'searching'
})

const statusLabel = computed(() => {
  switch (scanPhase.value) {
    case 'success':
      return 'Kartu terbaca'
    case 'reading':
      return 'Membaca kartu...'
    case 'steady':
      return 'Tahan sebentar...'
    default:
      return 'Posisikan Seluruh Kartu di Dalam Bingkai'
  }
})

// ---------- Helper pencocokan ----------

function normalizeNis(value) {
  return String(value ?? '').replace(/\D/g, '')
}

function normalizeName(value) {
  return String(value ?? '')
    .toUpperCase()
    .replace(/[^A-Z\s]/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
}

function getStudentNameByNis(nis) {
  const target = normalizeNis(nis)
  if (!target) return null
  const local = (props.students || []).find((s) => normalizeNis(s.nis) === target)
  if (local?.name) return local.name
  return resolvedNames.value[target] || null
}

// Cari nama siswa lewat lookupStudent (tanpa filter kelas) bila belum ada di daftar lokal
async function resolveNis(nis) {
  const target = normalizeNis(nis)
  if (!target || !props.lookupStudent) return
  if (getStudentNameByNis(target)) return
  if (target in resolvedNames.value) return // sudah pernah dicari
  try {
    const res = await props.lookupStudent(target)
    const name =
      typeof res === 'string' ? res : res?.name ?? res?.nama ?? res?.student?.name ?? null
    resolvedNames.value = { ...resolvedNames.value, [target]: name || null }
  } catch {
    // Gagal jaringan: jangan di-cache supaya bisa dicoba lagi
  }
}

const detectedStudentName = computed(() => {
  if (!manualToken.value) return null
  const name = getStudentNameByNis(manualToken.value)
  if (name) return name
  if (lookingUp.value) return 'Mencari nama...'
  return props.lookupStudent ? 'Nama tidak ditemukan' : 'Nama tidak ditemukan di daftar kelas ini'
})

let lookupTimer = null
watch(manualToken, (val) => {
  clearTimeout(lookupTimer)
  const nis = normalizeNis(val)
  if (!props.lookupStudent || nis.length < 5 || getStudentNameByNis(nis) || nis in resolvedNames.value) {
    lookingUp.value = false
    return
  }
  lookingUp.value = true
  lookupTimer = setTimeout(async () => {
    await resolveNis(nis)
    if (normalizeNis(manualToken.value) === nis) lookingUp.value = false
  }, 400)
})

function levenshtein(a, b) {
  if (a === b) return 0
  const m = a.length
  const n = b.length
  if (!m) return n
  if (!n) return m
  let prev = Array.from({ length: n + 1 }, (_, i) => i)
  for (let i = 1; i <= m; i++) {
    const cur = [i]
    for (let j = 1; j <= n; j++) {
      cur[j] = Math.min(
        prev[j] + 1,
        cur[j - 1] + 1,
        prev[j - 1] + (a[i - 1] === b[j - 1] ? 0 : 1)
      )
    }
    prev = cur
  }
  return prev[n]
}

// Ubah huruf yang sering salah dibaca menjadi angka (O->0, I/l->1, S->5, B->8, Z->2)
function digitsFromText(text) {
  return String(text || '')
    .replace(/[Oo]/g, '0')
    .replace(/[IlL|!]/g, '1')
    .replace(/[Ss]/g, '5')
    .replace(/[Bb]/g, '8')
    .replace(/Z/g, '2')
    .replace(/\D/g, '')
}

// Ada potongan angka yang mirip NIS dengan maksimal 1 digit berbeda?
function hasNearMatch(haystack, nis) {
  const len = nis.length
  for (let i = 0; i + len <= haystack.length; i++) {
    let diff = 0
    for (let j = 0; j < len && diff <= 1; j++) {
      if (haystack[i + j] !== nis[j]) diff++
    }
    if (diff <= 1) return true
  }
  return false
}

// Beri skor tiap siswa berdasarkan teks OCR (NIS + nama sebagai pengaman ganda)
function matchStudents(text) {
  const students = props.students || []
  if (!students.length) return []

  const plain = String(text || '').replace(/\D/g, '')
  const mapped = digitsFromText(text)
  const words = normalizeName(text)
    .split(' ')
    .filter((w) => w.length >= 3)

  const results = []
  for (const s of students) {
    const nis = normalizeNis(s.nis)
    let score = 0
    let exact = false

    if (nis.length >= 5) {
      if (plain.includes(nis) || mapped.includes(nis)) {
        score += 3
        exact = true
      } else if (hasNearMatch(mapped, nis)) {
        score += 1.5
      }
    }

    const tokens = normalizeName(s.name)
      .split(' ')
      .filter((t) => t.length >= 3)
    if (tokens.length) {
      const hit = tokens.filter((t) =>
        words.some((w) => levenshtein(w, t) <= (t.length >= 6 ? 1 : 0))
      ).length
      const ratio = hit / tokens.length
      if (tokens.length >= 2 && ratio >= 0.99) score += 2.5
      else if (hit >= 2 && ratio >= 0.6) score += 1.5
    }

    if (score > 0) results.push({ nis, score, exact })
  }
  return results.sort((a, b) => b.score - a.score)
}

// ---------- Tesseract worker ----------

function getOcrWorker() {
  if (!ocrWorkerPromise) {
    ocrWorkerPromise = (async () => {
      const worker = await createWorker('eng')
      // Tanpa whitelist angka: nama siswa juga dipakai untuk verifikasi
      await worker.setParameters({
        tessedit_pageseg_mode: '11',
        preserve_interword_spaces: '1',
      })
      return worker
    })().catch((err) => {
      ocrWorkerPromise = null
      throw err
    })
  }
  return ocrWorkerPromise
}

async function terminateOcrWorker() {
  if (!ocrWorkerPromise) return
  const promise = ocrWorkerPromise
  ocrWorkerPromise = null
  try {
    const worker = await promise
    await worker.terminate()
  } catch {
    // Abaikan
  }
}

// ---------- Kamera ----------

function stopScanLoops() {
  if (ocrInterval) {
    clearInterval(ocrInterval)
    ocrInterval = null
  }
  prevFrame = null
  steadyCount = 0
  cardSteady.value = false
}

// Ambil versi kecil (grayscale) dari area bingkai untuk deteksi kartu diam
function sampleGuideArea() {
  const video = videoRef.value
  if (!video || !video.videoWidth) return null

  const vw = video.videoWidth
  const vh = video.videoHeight
  const cropW = Math.round(vw * 0.8)
  const cropH = Math.round(cropW / 1.58)
  const cropX = Math.round((vw - cropW) / 2)
  const cropY = Math.round((vh - cropH) / 2)

  if (!sampleCanvas) {
    sampleCanvas = document.createElement('canvas')
    sampleCanvas.width = SAMPLE_W
    sampleCanvas.height = SAMPLE_H
  }
  const ctx = sampleCanvas.getContext('2d', { willReadFrequently: true })
  ctx.drawImage(video, cropX, cropY, cropW, cropH, 0, 0, SAMPLE_W, SAMPLE_H)
  const { data } = ctx.getImageData(0, 0, SAMPLE_W, SAMPLE_H)

  const gray = new Uint8Array(SAMPLE_W * SAMPLE_H)
  for (let i = 0, j = 0; i < gray.length; i++, j += 4) {
    gray[i] = (data[j] * 299 + data[j + 1] * 587 + data[j + 2] * 114) / 1000
  }
  return gray
}

function analyzeFrames(cur, prev) {
  let diff = 0
  let edges = 0
  for (let i = 0; i < cur.length; i++) {
    diff += Math.abs(cur[i] - prev[i])
    if (i % SAMPLE_W < SAMPLE_W - 1 && Math.abs(cur[i] - cur[i + 1]) > 25) edges++
  }
  return { motion: diff / cur.length, edgeRatio: edges / cur.length }
}

async function scanTick() {
  if (ocrBusy || ocrSuccess.value) return
  const frame = sampleGuideArea()
  if (!frame) return

  const prev = prevFrame
  prevFrame = frame
  if (!prev) return

  const { motion, edgeRatio } = analyzeFrames(frame, prev)
  if (motion < MOTION_MAX && edgeRatio > EDGE_MIN) steadyCount++
  else steadyCount = 0
  cardSteady.value = steadyCount > 0

  if (steadyCount >= STEADY_FRAMES_NEEDED && Date.now() >= cooldownUntil) {
    steadyCount = 0
    cardSteady.value = false
    await captureAndRunOcr()
    cooldownUntil = Date.now() + RETRY_COOLDOWN_MS
    prevFrame = null
  }
}

function startScanLoops() {
  stopScanLoops()
  lastHit = { nis: null, count: 0 }
  cooldownUntil = 0
  ocrInterval = setInterval(scanTick, SCAN_INTERVAL_MS)
}

async function startOcrCamera() {
  ocrCameraError.value = ''
  ocrSuccess.value = false
  stopOcrCamera()
  await nextTick()

  if (!navigator.mediaDevices?.getUserMedia) {
    ocrCameraError.value = 'Browser membutuhkan HTTPS atau localhost untuk live video.'
    return
  }

  try {
    mediaStream = await navigator.mediaDevices.getUserMedia({
      video: {
        facingMode: { ideal: 'environment' },
        width: { ideal: 1920 },
        height: { ideal: 1080 },
      },
      audio: false,
    })

    if (videoRef.value) {
      videoRef.value.srcObject = mediaStream
      startScanLoops()
    }
  } catch {
    ocrCameraError.value = 'Izin kamera tidak tersedia. Gunakan tombol foto/upload.'
  }
}

function stopOcrCamera() {
  stopScanLoops()
  if (mediaStream) {
    mediaStream.getTracks().forEach((track) => track.stop())
    mediaStream = null
  }
  if (videoRef.value) {
    videoRef.value.srcObject = null
  }
}

// ---------- OCR ----------

// Pra-proses: grayscale + regangkan kontras (persentil 2%-98%), tanpa binarisasi keras
function preprocessCanvas(canvas) {
  const ctx = canvas.getContext('2d')
  const img = ctx.getImageData(0, 0, canvas.width, canvas.height)
  const d = img.data
  const hist = new Array(256).fill(0)
  const total = d.length / 4

  for (let i = 0; i < d.length; i += 4) {
    const g = Math.round(0.299 * d[i] + 0.587 * d[i + 1] + 0.114 * d[i + 2])
    d[i] = g
    hist[g]++
  }

  let acc = 0
  let lo = 0
  let hi = 255
  for (let v = 0; v < 256; v++) {
    acc += hist[v]
    if (acc >= total * 0.02) {
      lo = v
      break
    }
  }
  acc = 0
  for (let v = 255; v >= 0; v--) {
    acc += hist[v]
    if (acc >= total * 0.02) {
      hi = v
      break
    }
  }
  const range = Math.max(hi - lo, 1)

  for (let i = 0; i < d.length; i += 4) {
    const v = Math.max(0, Math.min(255, ((d[i] - lo) * 255) / range))
    d[i] = v
    d[i + 1] = v
    d[i + 2] = v
  }
  ctx.putImageData(img, 0, 0)
}

function acceptStudent(nis, candidates) {
  manualToken.value = nis
  ocrCandidates.value = candidates
  ocrSuccess.value = true
  stopScanLoops()
  toast.success(`Siswa terdeteksi: ${getStudentNameByNis(nis) || nis}`)

  if (props.autoSubmit) {
    clearTimeout(autoSubmitTimer)
    autoSubmitTimer = setTimeout(() => {
      autoSubmitTimer = null
      // Batalkan jika modal sudah ditutup atau NIS diubah manual selama jeda
      if (!props.open || manualToken.value !== nis) return
      submitManual()
    }, props.autoSubmitDelay)
  }
}

// single = true untuk upload foto (satu kali baca, tanpa konfirmasi 2 frame)
async function runOcrOnCanvas(canvas, single = false) {
  if (ocrBusy) return
  ocrBusy = true
  ocrProcessing.value = true

  try {
    preprocessCanvas(canvas)

    const worker = await getOcrWorker()
    const {
      data: { text },
    } = await worker.recognize(canvas)

    if (!props.open) return

    const cleaned = (text || '').trim()
    if (cleaned) ocrRawText.value = cleaned

    const ranked = matchStudents(cleaned)
    const top = ranked[0]
    const second = ranked[1]
    const unambiguous = top && (!second || top.score - second.score >= 1)
    const confident = top && unambiguous && top.score >= 2.5

    if (confident) {
      const instant = single || top.exact
      let ok = instant
      if (!instant) {
        lastHit = lastHit.nis === top.nis
          ? { nis: top.nis, count: lastHit.count + 1 }
          : { nis: top.nis, count: 1 }
        ok = lastHit.count >= 2
      }
      if (ok) {
        acceptStudent(top.nis, ranked.slice(0, 3).map((r) => r.nis))
        return
      }
      // Belum terkonfirmasi: tampilkan sementara
      ocrCandidates.value = [top.nis]
      if (!manualToken.value) manualToken.value = top.nis
      return
    }

    lastHit = { nis: null, count: 0 }

    // Cadangan: tampilkan deretan angka panjang yang terbaca
    const runs = (cleaned.match(/\d[\d\s]{4,}\d/g) || [])
      .map((r) => r.replace(/\D/g, ''))
      .filter((r) => r.length >= 5)
    const uniqueRuns = [...new Set(runs)]
    ocrCandidates.value = uniqueRuns
    if (uniqueRuns.length && !manualToken.value) manualToken.value = uniqueRuns[0]

    // NIS tidak ada di daftar lokal: cek lewat lookupStudent (tanpa filter kelas)
    if (props.lookupStudent) {
      for (const run of uniqueRuns.filter((r) => r.length >= 8 && r.length <= 12).slice(0, 3)) {
        await resolveNis(run)
        if (!props.open) return
        if (getStudentNameByNis(run)) {
          acceptStudent(run, [run])
          return
        }
      }
    }

    if (single) {
      toast.info('Siswa belum cocok dengan daftar. Pastikan kartu jelas atau ketik NIS manual.')
    }
  } catch {
    // Diamkan: pemindaian otomatis akan mencoba lagi pada siklus berikutnya
  } finally {
    ocrBusy = false
    ocrProcessing.value = false
  }
}

async function captureAndRunOcr() {
  const video = videoRef.value
  if (!video || !video.videoWidth) return

  const vw = video.videoWidth
  const vh = video.videoHeight

  // Bergantian: siklus genap = area bingkai (lebih fokus), siklus ganjil = seluruh frame.
  // Dengan begitu kartu dengan layout/orientasi berbeda tetap terbaca walau tidak pas di bingkai.
  const useFullFrame = scanCycle++ % 2 === 1
  let cropW, cropH, cropX, cropY
  if (useFullFrame) {
    cropW = vw
    cropH = vh
    cropX = 0
    cropY = 0
  } else {
    cropW = Math.round(vw * 0.8)
    cropH = Math.round(cropW / 1.58)
    cropX = Math.round((vw - cropW) / 2)
    cropY = Math.round((vh - cropH) / 2)
  }

  // Perbesar hasil crop supaya huruf/angka kecil lebih mudah dibaca
  const scale = cropW < 1600 ? 1600 / cropW : 1
  const canvas = document.createElement('canvas')
  canvas.width = Math.round(cropW * scale)
  canvas.height = Math.round(cropH * scale)
  const ctx = canvas.getContext('2d')
  ctx.drawImage(video, cropX, cropY, cropW, cropH, 0, 0, canvas.width, canvas.height)

  // Bekukan gambar selama membaca supaya siswa tahu kartunya sudah "diambil"
  video.pause()
  await runOcrOnCanvas(canvas, false)

  // Gagal baca: lanjutkan video agar siswa bisa menyesuaikan posisi kartu
  if (!ocrSuccess.value && props.open && videoRef.value && mediaStream) {
    videoRef.value.play().catch(() => {})
  }
}

async function handleOcrFileUpload(event) {
  const file = event.target.files?.[0]
  if (!file) return

  const img = new Image()
  const objectUrl = URL.createObjectURL(file)
  img.onload = async () => {
    const targetW = 1600
    const scale = targetW / img.width
    const canvas = document.createElement('canvas')
    canvas.width = Math.round(img.width * scale)
    canvas.height = Math.round(img.height * scale)
    const ctx = canvas.getContext('2d')
    ctx.drawImage(img, 0, 0, canvas.width, canvas.height)
    URL.revokeObjectURL(objectUrl)
    stopScanLoops()
    await runOcrOnCanvas(canvas, true)
  }
  img.onerror = () => {
    URL.revokeObjectURL(objectUrl)
    toast.error('Gagal memuat gambar. Silakan coba file lain.')
  }
  img.src = objectUrl
  event.target.value = ''
}

// ---------- Penutupan & reset ----------

async function stopAllCameras() {
  stopOcrCamera()
  await terminateOcrWorker()
}

function resetState() {
  clearTimeout(lookupTimer)
  clearTimeout(autoSubmitTimer)
  autoSubmitTimer = null
  ocrSuccess.value = false
  cardSteady.value = false
  lookingUp.value = false
  manualToken.value = ''
  ocrRawText.value = ''
  ocrCandidates.value = []
  lastHit = { nis: null, count: 0 }
}

async function closeModal() {
  await stopAllCameras()
  resetState()
  emit('close')
}

async function submitManual() {
  if (!manualToken.value) return
  const token = manualToken.value
  await stopAllCameras()
  resetState()
  emit('scanned', token)
}

watch(
  () => props.open,
  async (val) => {
    if (val) {
      await startOcrCamera()
    } else {
      await stopAllCameras()
    }
  }
)

onBeforeUnmount(() => {
  clearTimeout(autoSubmitTimer)
  stopAllCameras()
})
</script>

<style scoped>
.scan-line {
  position: absolute;
  left: 0;
  right: 0;
  top: 4%;
  height: 3px;
  background: linear-gradient(90deg, transparent, rgba(52, 211, 153, 0.95), transparent);
  box-shadow: 0 0 14px rgba(52, 211, 153, 0.8);
  animation: scan-sweep 2s ease-in-out infinite;
}

@keyframes scan-sweep {
  0%,
  100% {
    top: 4%;
  }
  50% {
    top: 94%;
  }
}

@media (prefers-reduced-motion: reduce) {
  .scan-line {
    animation: none;
    top: 50%;
  }
}
</style>