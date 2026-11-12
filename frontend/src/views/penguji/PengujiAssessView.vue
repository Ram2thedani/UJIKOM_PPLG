<template>
  <div class="min-h-screen bg-slate-50 pb-28">
    <!-- Sticky Top Bar -->
    <header class="sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200 shadow-sm">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 py-3 flex flex-wrap items-center justify-between gap-3">
        <!-- Back & Student Info -->
        <div class="flex items-center gap-3 min-w-0">
          <button
            type="button"
            class="min-h-[44px] min-w-[44px] px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm flex items-center gap-1.5 transition active:scale-95"
            @click="goBack"
          >
            <ArrowLeft class="w-5 h-5" />
            <span class="hidden sm:inline">Antrean</span>
          </button>

          <div class="min-w-0">
            <div class="flex items-center gap-2">
              <h1 class="font-extrabold text-slate-900 text-base sm:text-lg truncate">
                {{ student?.name || 'Memuat...' }}
              </h1>
              <span
                v-if="student?.is_priority"
                class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[11px] font-extrabold uppercase shrink-0"
              >
                Prioritas
              </span>
              <span
                v-if="isReadOnly"
                class="px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 text-[11px] font-extrabold uppercase shrink-0"
              >
                Read-Only
              </span>
              <span
                v-else-if="alreadyGraded"
                class="px-2 py-0.5 rounded-full bg-amber-500 text-white text-[11px] font-extrabold uppercase shrink-0"
              >
                Mode Revisi
              </span>
            </div>
            <p class="text-xs text-slate-500 truncate">
              NIS: {{ student?.nis || '-' }} • {{ student?.class_name || '-' }}
              <span v-if="draftSavedAt && !isReadOnly" class="ml-2 text-indigo-600 font-semibold">
                • Draft tersimpan {{ draftSavedAt }}
              </span>
            </p>
          </div>
        </div>

        <!-- Live Score, Live Badge, & Passive Timer -->
        <div class="flex items-center gap-2.5 sm:gap-4 ml-auto">
          <!-- Passive Timer -->
          <div
            class="px-3 py-1.5 rounded-xl bg-slate-100 border border-slate-200 flex items-center gap-1.5 text-slate-700"
            title="Durasi sesi penilaian"
          >
            <Clock class="w-4 h-4 text-slate-500" />
            <span class="font-mono text-sm font-bold">{{ formatDuration(elapsedSeconds) }}</span>
          </div>

          <!-- Live Final Score + Status Badge -->
          <div class="flex items-center gap-2.5 pl-3 border-l border-slate-200">
            <div class="text-right">
              <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Nilai Akhir</p>
              <p
                class="text-2xl font-black leading-none"
                :class="liveFinalScore >= 80 ? 'text-emerald-600' : 'text-rose-600'"
              >
                {{ liveFinalScore.toFixed(2) }}
              </p>
            </div>
            <span
              class="px-3 py-1.5 rounded-xl text-xs font-extrabold uppercase tracking-wide"
              :class="
                liveFinalScore >= 80
                  ? 'bg-emerald-100 text-emerald-800 border border-emerald-200'
                  : 'bg-rose-100 text-rose-800 border border-rose-200'
              "
            >
              {{ liveFinalScore >= 80 ? 'KOMPETEN' : 'REMEDIAL' }}
            </span>
          </div>
        </div>
      </div>

      <!-- 3-Page Step-Stepper -->
      <div class="max-w-5xl mx-auto px-4 sm:px-6 pb-3">
        <div class="grid grid-cols-3 gap-2">
          <button
            v-for="page in pageMeta"
            :key="page.number"
            type="button"
            class="min-h-[48px] px-3 py-2 rounded-2xl border text-left transition flex items-center gap-2.5 active:scale-[0.99]"
            :class="
              currentPage === page.number
                ? 'bg-indigo-600 border-indigo-600 text-white shadow-sm'
                : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'
            "
            @click="currentPage = page.number"
          >
            <span
              class="w-7 h-7 rounded-xl flex items-center justify-center text-xs font-black shrink-0"
              :class="
                currentPage === page.number
                  ? 'bg-white/20 text-white'
                  : 'bg-white border border-slate-300 text-slate-700'
              "
            >
              {{ page.number }}
            </span>
            <div class="min-w-0">
              <p class="text-xs font-extrabold truncate">{{ page.shortTitle }}</p>
              <p
                class="text-[11px] truncate"
                :class="currentPage === page.number ? 'text-indigo-100' : 'text-slate-500'"
              >
                Bobot {{ pageWeight(page.number) }}%
              </p>
            </div>
          </button>
        </div>
      </div>
    </header>

    <!-- Main Assessment Content -->
    <main class="max-w-5xl mx-auto px-4 sm:px-6 pt-5 space-y-5">
      <!-- Read-Only Banner -->
      <div
        v-if="isReadOnly"
        class="rounded-2xl bg-amber-50 border border-amber-200 p-4 flex flex-wrap items-center justify-between gap-3"
      >
        <div class="flex items-center gap-2.5 text-amber-900">
          <Lock class="w-5 h-5 text-amber-600 shrink-0" />
          <p class="text-sm font-semibold">
            Anda sedang melihat hasil penilaian dalam <strong>Mode Baca (Read-Only)</strong>.
          </p>
        </div>
        <button
          type="button"
          class="min-h-[44px] px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-sm flex items-center gap-2 transition active:scale-95"
          @click="guardModalOpen = true"
        >
          <KeyRound class="w-4 h-4" />
          Buka Kunci Revisi (PIN)
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="space-y-4">
        <div v-for="n in 3" :key="n" class="h-52 rounded-3xl bg-white border border-slate-200 animate-pulse"></div>
      </div>

      <template v-else>
        <!-- Page Header -->
        <div class="flex items-center justify-between gap-3">
          <div>
            <p class="text-xs font-bold uppercase tracking-wider text-indigo-600">
              Halaman {{ currentPage }} dari 3
            </p>
            <h2 class="text-lg sm:text-xl font-extrabold text-slate-900">
              {{ pageMeta[currentPage - 1].fullTitle }}
            </h2>
          </div>

          <button
            v-if="restoredDraft && !isReadOnly"
            type="button"
            class="px-3 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold transition"
            @click="resetAllToDefault"
          >
            Reset Draft ke 80
          </button>
        </div>

        <!-- 3 Rubric Items for Active Page -->
        <section class="space-y-4">
          <div
            v-for="item in currentPageItems"
            :key="item.id"
            class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5 sm:p-6"
          >
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
              <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                  <span class="px-2.5 py-0.5 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-extrabold">
                    Butir {{ item.item_order }}
                  </span>
                  <span class="px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-bold">
                    Bobot {{ item.weight }}%
                  </span>
                  <span class="text-xs font-semibold text-slate-500">
                    Kontribusi: <strong class="text-slate-800">{{ itemWeightedScore(item).toFixed(2) }}</strong>
                  </span>
                </div>

                <h3 class="text-base sm:text-lg font-extrabold text-slate-900">
                  {{ item.title }}
                </h3>

                <!-- Indikator Penilaian -->
                <div class="mt-3 flex flex-wrap gap-1.5">
                  <span
                    v-for="(ind, idx) in item.indicators"
                    :key="idx"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-600 font-medium"
                  >
                    <Check class="w-3 h-3 text-indigo-500 shrink-0" />
                    {{ ind }}
                  </span>
                </div>
              </div>

              <!-- Current Score Display (NO input field -> zero virtual keyboard popup!) -->
              <div class="flex sm:flex-col items-center justify-between sm:items-end gap-2 shrink-0">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Skor Mentah</span>
                <div
                  class="min-w-[88px] py-2 px-4 rounded-2xl text-center border-2 font-black text-3xl select-none"
                  :class="
                    scores[item.id] >= 80
                      ? 'bg-emerald-50 border-emerald-300 text-emerald-700'
                      : 'bg-rose-50 border-rose-300 text-rose-700'
                  "
                >
                  {{ scores[item.id] ?? 80 }}
                </div>
              </div>
            </div>

            <!-- 5 Large Touch Buttons (Tablet-Optimized) -->
            <div class="mt-5 grid grid-cols-5 gap-2 sm:gap-3">
              <button
                type="button"
                :disabled="isReadOnly || scores[item.id] <= 0"
                class="min-h-[52px] rounded-2xl border-2 border-rose-200 bg-rose-50 hover:bg-rose-100 disabled:opacity-40 text-rose-700 font-black text-base select-none transition active:scale-95"
                @click="adjustScore(item.id, -5)"
              >
                -5
              </button>

              <button
                type="button"
                :disabled="isReadOnly || scores[item.id] <= 0"
                class="min-h-[52px] rounded-2xl border-2 border-rose-200 bg-white hover:bg-rose-50 disabled:opacity-40 text-rose-700 font-extrabold text-base select-none transition active:scale-95"
                @click="adjustScore(item.id, -1)"
              >
                -1
              </button>

              <button
                type="button"
                :disabled="isReadOnly"
                class="min-h-[52px] rounded-2xl border-2 border-slate-200 bg-slate-100 hover:bg-slate-200 disabled:opacity-40 text-slate-700 font-bold text-xs sm:text-sm select-none transition active:scale-95"
                @click="setScore(item.id, 80)"
              >
                Reset 80
              </button>

              <button
                type="button"
                :disabled="isReadOnly || scores[item.id] >= 100"
                class="min-h-[52px] rounded-2xl border-2 border-emerald-200 bg-white hover:bg-emerald-50 disabled:opacity-40 text-emerald-700 font-extrabold text-base select-none transition active:scale-95"
                @click="adjustScore(item.id, 1)"
              >
                +1
              </button>

              <button
                type="button"
                :disabled="isReadOnly || scores[item.id] >= 100"
                class="min-h-[52px] rounded-2xl border-2 border-emerald-200 bg-emerald-50 hover:bg-emerald-100 disabled:opacity-40 text-emerald-700 font-black text-base select-none transition active:scale-95"
                @click="adjustScore(item.id, 5)"
              >
                +5
              </button>
            </div>
          </div>
        </section>

        <!-- Page 3 Extra: Quick Tags & Examiner Note -->
        <section
          v-if="currentPage === 3"
          class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5 sm:p-6 space-y-5"
        >
          <div>
            <h3 class="text-base font-extrabold text-slate-900">Quick Tags Evaluasi (Pilih Cepat)</h3>
            <p class="text-xs text-slate-500 mt-0.5">
              Ketuk satu atau beberapa tag di bawah untuk melampirkan umpan balik instan ke dashboard siswa.
            </p>

            <div class="mt-4 space-y-3">
              <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700 mb-2">
                  Apresiasi & Keunggulan
                </p>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="tag in appreciationTags"
                    :key="tag"
                    type="button"
                    :disabled="isReadOnly"
                    class="min-h-[42px] px-3.5 py-2 rounded-xl border text-xs sm:text-sm font-bold transition select-none active:scale-95"
                    :class="
                      quickTags.includes(tag)
                        ? 'bg-emerald-600 border-emerald-600 text-white shadow-sm'
                        : 'bg-emerald-50/70 border-emerald-200 text-emerald-800 hover:bg-emerald-100'
                    "
                    @click="toggleTag(tag)"
                  >
                    {{ tag }}
                  </button>
                </div>
              </div>

              <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-amber-700 mb-2">
                  Catatan Perbaikan / Remedial
                </p>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="tag in remedialTags"
                    :key="tag"
                    type="button"
                    :disabled="isReadOnly"
                    class="min-h-[42px] px-3.5 py-2 rounded-xl border text-xs sm:text-sm font-bold transition select-none active:scale-95"
                    :class="
                      quickTags.includes(tag)
                        ? 'bg-rose-600 border-rose-600 text-white shadow-sm'
                        : 'bg-amber-50/80 border-amber-200 text-amber-900 hover:bg-amber-100'
                    "
                    @click="toggleTag(tag)"
                  >
                    {{ tag }}
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div>
            <label for="examiner-note" class="block text-sm font-extrabold text-slate-800 mb-1.5">
              Catatan Tambahan Penguji (Opsional)
            </label>
            <textarea
              id="examiner-note"
              v-model="examinerNote"
              :disabled="isReadOnly"
              rows="3"
              placeholder="Tuliskan catatan spesifik perbaikan bug, fitur yang kurang, atau apresiasi untuk siswa..."
              class="w-full rounded-2xl border border-slate-300 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 outline-none p-4 text-sm disabled:opacity-70"
            ></textarea>
          </div>
        </section>
      </template>
    </main>

    <!-- Bottom Sticky Wizard Navigation -->
    <footer class="fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur border-t border-slate-200 shadow-lg">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between gap-3">
        <button
          type="button"
          :disabled="currentPage === 1"
          class="min-h-[52px] px-5 rounded-2xl border border-slate-300 bg-white hover:bg-slate-100 disabled:opacity-40 text-slate-800 font-bold text-sm sm:text-base flex items-center gap-2 transition active:scale-95"
          @click="currentPage--"
        >
          <ChevronLeft class="w-5 h-5" />
          <span>Sebelumnya</span>
        </button>

        <button
          v-if="currentPage < 3"
          type="button"
          class="flex-1 sm:flex-none min-h-[52px] px-7 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-sm sm:text-base shadow-lg shadow-indigo-600/25 flex items-center justify-center gap-2 transition active:scale-95"
          @click="currentPage++"
        >
          <span>Lanjut Halaman {{ currentPage + 1 }}</span>
          <ChevronRight class="w-5 h-5" />
        </button>

        <button
          v-else-if="!isReadOnly"
          type="button"
          :disabled="submitting"
          class="flex-1 sm:flex-none min-h-[52px] px-7 rounded-2xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white font-extrabold text-sm sm:text-base shadow-lg shadow-emerald-600/25 flex items-center justify-center gap-2 transition active:scale-95"
          @click="submitAssessment"
        >
          <Loader2 v-if="submitting" class="w-5 h-5 animate-spin" />
          <CheckCircle2 v-else class="w-5 h-5" />
          <span>{{ submitting ? 'Menyimpan...' : 'Simpan & Selesai' }}</span>
        </button>

        <button
          v-else
          type="button"
          class="flex-1 sm:flex-none min-h-[52px] px-6 rounded-2xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-sm sm:text-base flex items-center justify-center gap-2 transition active:scale-95"
          @click="goBack"
        >
          <span>Kembali ke Antrean</span>
        </button>
      </div>
    </footer>

    <!-- Double-Grading Guard Modal (jika diakses langsung via URL dalam mode Read-Only) -->
    <DoubleGradingModal
      :open="guardModalOpen"
      :student="studentForModal"
      @close="guardModalOpen = false"
      @read-only="guardModalOpen = false"
      @unlocked="handleUnlockedFromDirectUrl"
    />
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowLeft,
  Check,
  CheckCircle2,
  ChevronLeft,
  ChevronRight,
  Clock,
  KeyRound,
  Loader2,
  Lock,
} from 'lucide-vue-next'
import DoubleGradingModal from '@/components/penguji/DoubleGradingModal.vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'
import { formatDuration } from '@/utils/formatters'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const toast = useToast()

const studentId = computed(() => route.params.studentId)
const draftStorageKey = computed(() => `ujikom_assess_draft_${studentId.value}`)

const loading = ref(true)
const submitting = ref(false)
const student = ref(null)
const existingAssessment = ref(null)
const alreadyGraded = ref(false)
const rubrics = ref([])

const currentPage = ref(1)
const scores = reactive({})
const quickTags = ref([])
const examinerNote = ref('')
const elapsedSeconds = ref(0)
const draftSavedAt = ref('')
const restoredDraft = ref(false)
const guardModalOpen = ref(false)

let timerInterval = null

const pageMeta = [
  { number: 1, shortTitle: 'DB & Desain', fullTitle: 'Analisis, Desain, & Arsitektur Database' },
  { number: 2, shortTitle: 'Koding & Fitur', fullTitle: 'Implementasi Koding, Fitur, & Standar' },
  { number: 3, shortTitle: 'Testing & Docs', fullTitle: 'Pengujian, Debugging, & Dokumentasi' },
]

const appreciationTags = [
  'Struktur DB & Relasi Rapi',
  'Query SQL & Trigger Efisien',
  'CRUD & Role Auth Lengkap',
  'UI/UX Bersih & Responsif',
  'Error Handling Baik',
  'Dokumentasi Kode Jelas',
]

const remedialTags = [
  'Perbaiki Relasi / Normalisasi DB',
  'Lengkapi Stored Procedure / Trigger',
  'Alur Transaksi Belum Berjalan',
  'Masih Ada Bug Fatal / Error',
  'Validasi Input Belum Ada',
  'Dokumentasi Kode Kurang Lengkap',
]

const isReadOnly = computed(() => {
  if (route.query.mode === 'readonly') return true
  if (alreadyGraded.value && !auth.getStudentRevisionPin(studentId.value)) {
    return true
  }
  return false
})

const studentForModal = computed(() => {
  if (!student.value) return null
  return {
    ...student.value,
    assessment: existingAssessment.value,
  }
})

const currentPageItems = computed(() =>
  rubrics.value.filter((item) => item.page_number === currentPage.value)
)

function pageWeight(pageNum) {
  return rubrics.value
    .filter((item) => item.page_number === pageNum)
    .reduce((sum, item) => sum + Number(item.weight), 0)
}

function itemWeightedScore(item) {
  const raw = Number(scores[item.id] ?? 80)
  return Math.round(((raw * Number(item.weight)) / 100) * 100) / 100
}

const liveFinalScore = computed(() => {
  const total = rubrics.value.reduce((sum, item) => sum + itemWeightedScore(item), 0)
  return Math.round(total * 100) / 100
})

function adjustScore(rubricId, delta) {
  if (isReadOnly.value) return
  const current = Number(scores[rubricId] ?? 80)
  const clamped = Math.max(0, Math.min(100, current + delta))
  scores[rubricId] = clamped
  saveDraft()
}

function setScore(rubricId, value) {
  if (isReadOnly.value) return
  scores[rubricId] = Math.max(0, Math.min(100, value))
  saveDraft()
}

function toggleTag(tag) {
  if (isReadOnly.value) return
  const idx = quickTags.value.indexOf(tag)
  if (idx >= 0) {
    quickTags.value.splice(idx, 1)
  } else {
    quickTags.value.push(tag)
  }
  saveDraft()
}

function saveDraft() {
  if (isReadOnly.value) return
  const payload = {
    scores: { ...scores },
    quick_tags: [...quickTags.value],
    examiner_note: examinerNote.value,
    elapsed_seconds: elapsedSeconds.value,
    saved_at: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
  }
  localStorage.setItem(draftStorageKey.value, JSON.stringify(payload))
  draftSavedAt.value = payload.saved_at
}

function clearDraft() {
  localStorage.removeItem(draftStorageKey.value)
  draftSavedAt.value = ''
  restoredDraft.value = false
}

function resetAllToDefault() {
  rubrics.value.forEach((item) => {
    scores[item.id] = 80
  })
  quickTags.value = []
  examinerNote.value = ''
  clearDraft()
  toast.info('Draft direset ke nilai default 80.')
}

function startTimer() {
  stopTimer()
  if (isReadOnly.value) return
  timerInterval = setInterval(() => {
    elapsedSeconds.value += 1
  }, 1000)
}

function stopTimer() {
  if (timerInterval) {
    clearInterval(timerInterval)
    timerInterval = null
  }
}

async function loadAssessmentData() {
  loading.value = true
  try {
    const { data } = await api.get(`/assessments/${studentId.value}`)
    student.value = data.student
    rubrics.value = data.rubrics
    alreadyGraded.value = data.already_graded
    existingAssessment.value = data.assessment

    // Inisialisasi default skor 80
    data.rubrics.forEach((item) => {
      scores[item.id] = 80
    })

    // Jika sudah dinilai, isi dari data tersimpan
    if (data.assessment && data.already_graded) {
      data.assessment.details?.forEach((d) => {
        scores[d.rubric_item_id] = d.raw_score
      })
      quickTags.value = [...(data.assessment.quick_tags || [])]
      examinerNote.value = data.assessment.examiner_note || ''
      elapsedSeconds.value = isReadOnly.value ? (data.assessment.duration_seconds || 0) : 0
    }

    // Pulihkan draft localStorage jika sedang dalam mode edit
    if (!isReadOnly.value) {
      const rawDraft = localStorage.getItem(draftStorageKey.value)
      if (rawDraft) {
        try {
          const parsed = JSON.parse(rawDraft)
          if (parsed.scores) {
            Object.entries(parsed.scores).forEach(([k, v]) => {
              scores[k] = Number(v)
            })
          }
          if (Array.isArray(parsed.quick_tags)) {
            quickTags.value = parsed.quick_tags
          }
          if (typeof parsed.examiner_note === 'string') {
            examinerNote.value = parsed.examiner_note
          }
          if (typeof parsed.elapsed_seconds === 'number' && !alreadyGraded.value) {
            elapsedSeconds.value = parsed.elapsed_seconds
          }
          draftSavedAt.value = parsed.saved_at || ''
          restoredDraft.value = true
        } catch {
          clearDraft()
        }
      }
    }

    startTimer()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Gagal memuat lembar penilaian siswa.')
    router.replace({ name: 'penguji.queue' })
  } finally {
    loading.value = false
  }
}

async function submitAssessment() {
  submitting.value = true
  try {
    const payload = {
      student_id: Number(studentId.value),
      scores: rubrics.value.map((item) => ({
        rubric_item_id: item.id,
        raw_score: Number(scores[item.id] ?? 80),
      })),
      quick_tags: quickTags.value,
      examiner_note: examinerNote.value,
      duration_seconds: elapsedSeconds.value,
    }

    if (alreadyGraded.value) {
      payload.revision_pin = auth.getStudentRevisionPin(studentId.value)
    }

    const { data } = await api.post('/assessments', payload)
    clearDraft()
    auth.clearStudentRevisionPin(studentId.value)
    stopTimer()

    const statusText = data.assessment.status === 'kompeten' ? 'KOMPETEN' : 'REMEDIAL'
    toast.success(`Penilaian tersimpan! Nilai: ${Number(data.assessment.final_score).toFixed(2)} (${statusText})`)
    router.push({ name: 'penguji.queue' })
  } catch (err) {
    if (err.response?.data?.require_pin) {
      guardModalOpen.value = true
    }
    toast.error(err.response?.data?.message || 'Gagal menyimpan penilaian.')
  } finally {
    submitting.value = false
  }
}

function handleUnlockedFromDirectUrl() {
  guardModalOpen.value = false
  router.replace({
    name: 'penguji.assess',
    params: { studentId: studentId.value },
    query: { mode: 'revision' },
  })
  startTimer()
}

function goBack() {
  router.push({ name: 'penguji.queue' })
}

watch(examinerNote, () => {
  if (!loading.value && !isReadOnly.value) {
    saveDraft()
  }
})

watch(isReadOnly, (readOnly) => {
  if (readOnly) stopTimer()
  else startTimer()
})

onMounted(loadAssessmentData)
onBeforeUnmount(stopTimer)
</script>
