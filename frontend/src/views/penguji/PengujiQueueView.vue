<template>
  <div class="min-h-screen bg-slate-50 pb-10">
    <AppNavbar subtitle="Antrean Penilaian Penguji (Tablet Mode)" />

    <main class="max-w-7xl mx-auto px-4 sm:px-6 pt-5 space-y-5">
      <!-- Top Controls: Class Filter, Search, Big QR Scan Button -->
      <section class="bg-white rounded-3xl border border-slate-200 shadow-sm p-4 sm:p-5">
        <div class="flex flex-col lg:flex-row lg:items-center gap-3.5">
          <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 flex-1">
            <!-- Filter Kelas -->
            <div class="sm:col-span-4">
              <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                Pilih Kelas
              </label>
              <select
                v-model="selectedClassId"
                class="w-full min-h-[48px] px-3.5 rounded-2xl border border-slate-300 bg-slate-50 focus:bg-white focus:border-indigo-500 outline-none font-semibold text-sm text-slate-800"
                @change="loadQueue"
              >
                <option v-if="classes.length === 0" :value="null" disabled>
                  Belum ada kelas yang ditugaskan
                </option>
                <option v-for="cls in classes" :key="cls.id" :value="cls.id">
                  {{ cls.name }} [{{ cls.major }}] ({{ cls.academic_year }})
                </option>
              </select>
            </div>

            <!-- Search Bar -->
            <div class="sm:col-span-8">
              <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                Cari Siswa (Nama / NIS)
              </label>
              <div class="relative">
                <Search class="w-5 h-5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input
                  v-model.trim="searchQuery"
                  type="search"
                  placeholder="Ketik nama siswa atau NIS..."
                  class="w-full min-h-[48px] pl-11 pr-4 rounded-2xl border border-slate-300 bg-slate-50 focus:bg-white focus:border-indigo-500 outline-none text-sm"
                />
              </div>
            </div>
          </div>

          <!-- Tombol Scan QR Besar untuk Tablet -->
          <div class="flex items-end gap-2.5 lg:pt-4">
            <button
              type="button"
              class="flex-1 lg:flex-none min-h-[54px] px-6 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-base shadow-lg shadow-indigo-600/25 flex items-center justify-center gap-2.5 transition active:scale-95"
              @click="scannerOpen = true"
            >
              <QrCode class="w-6 h-6" />
              <span>Scan Kartu / NIS</span>
            </button>

            <button
              type="button"
              :disabled="loading"
              class="min-h-[54px] px-4 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold flex items-center justify-center transition active:scale-95"
              title="Refresh Antrean"
              @click="loadQueue"
            >
              <RefreshCw class="w-5 h-5" :class="{ 'animate-spin': loading }" />
            </button>
          </div>
        </div>

        <!-- Progress Bar & Counter -->
        <div class="mt-5 pt-4 border-t border-slate-100">
          <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
            <span class="text-sm font-bold text-slate-700">
              Progres Penilaian:
              <span class="text-indigo-600">{{ stats.graded }} / {{ stats.total }} Siswa</span>
              ({{ progressPercent }}%)
            </span>
            <span class="text-xs text-slate-500">
              Ketuk kartu siswa untuk mulai menilai atau melihat detail
            </span>
          </div>
          <div class="w-full h-3 rounded-full bg-slate-100 overflow-hidden flex">
            <div
              class="bg-emerald-500 h-full transition-all duration-300"
              :style="{ width: `${stats.total ? (stats.kompeten / stats.total) * 100 : 0}%` }"
            ></div>
            <div
              class="bg-rose-500 h-full transition-all duration-300"
              :style="{ width: `${stats.total ? (stats.remedial / stats.total) * 100 : 0}%` }"
            ></div>
          </div>

          <!-- Filter Pills -->
          <div class="mt-4 flex flex-wrap gap-2">
            <button
              v-for="pill in filterPills"
              :key="pill.key"
              type="button"
              class="min-h-[40px] px-3.5 py-1.5 rounded-xl text-xs font-extrabold border transition flex items-center gap-2 active:scale-95"
              :class="statusFilter === pill.key ? pill.activeClass : pill.idleClass"
              @click="statusFilter = pill.key"
            >
              <span class="w-2.5 h-2.5 rounded-full" :class="pill.dotClass"></span>
              <span>{{ pill.label }}</span>
              <span class="px-1.5 py-0.5 rounded-md bg-black/10 text-[11px]">{{ pill.count }}</span>
            </button>
          </div>
        </div>
      </section>

      <!-- Loading Skeleton -->
      <div v-if="loading && students.length === 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3.5">
        <div v-for="n in 12" :key="n" class="h-32 rounded-2xl bg-white border border-slate-200 animate-pulse"></div>
      </div>

      <!-- Empty State -->
      <div
        v-else-if="classes.length === 0"
        class="bg-white rounded-3xl border border-slate-200 p-10 text-center"
      >
        <Users class="w-12 h-12 text-amber-400 mx-auto mb-3" />
        <p class="font-bold text-slate-800">Belum ada kelas yang ditugaskan kepada akun penguji ini.</p>
        <p class="text-sm text-slate-500 mt-1">Hubungi Admin untuk menetapkan daftar kelas yang akan Anda uji.</p>
      </div>

      <div
        v-else-if="filteredStudents.length === 0"
        class="bg-white rounded-3xl border border-slate-200 p-10 text-center"
      >
        <Users class="w-12 h-12 text-slate-300 mx-auto mb-3" />
        <p class="font-bold text-slate-700">Tidak ada siswa yang sesuai filter.</p>
        <p class="text-sm text-slate-500 mt-1">Coba ubah kata kunci pencarian atau filter status.</p>
      </div>

      <!-- 36 Student Color-Coded Cards Grid -->
      <section v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3.5">
        <button
          v-for="student in filteredStudents"
          :key="student.id"
          type="button"
          class="text-left rounded-2xl border-2 p-4 min-h-[132px] flex flex-col justify-between transition shadow-sm hover:shadow-md active:scale-[0.98]"
          :class="cardClass(student.queue_status)"
          @click="handleSelectStudent(student)"
        >
          <div class="w-full">
            <div class="flex items-center justify-between gap-2 mb-2">
              <span class="font-mono text-xs font-bold px-2 py-0.5 rounded-lg bg-black/5 text-slate-700">
                {{ student.nis }}
              </span>

              <span
                class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold uppercase tracking-wide"
                :class="statusBadgeClass(student.queue_status)"
              >
                {{ statusLabel(student.queue_status) }}
              </span>
            </div>

            <h3 class="font-extrabold text-slate-900 text-sm sm:text-base leading-snug line-clamp-2">
              {{ student.name }}
            </h3>
          </div>

          <div class="w-full mt-3 pt-2.5 border-t border-black/10 flex items-center justify-between gap-2">
            <template v-if="student.already_graded">
              <span class="text-xs font-semibold text-slate-600 truncate">
                Nilai Akhir
              </span>
              <span
                class="text-lg font-black"
                :class="student.queue_status === 'kompeten' ? 'text-emerald-700' : 'text-rose-700'"
              >
                {{ formatScore(student.assessment?.final_score) }}
              </span>
            </template>

            <template v-else-if="student.queue_status === 'priority'">
              <span class="text-xs font-bold text-amber-800 flex items-center gap-1 truncate">
                <Star class="w-3.5 h-3.5 fill-amber-500 text-amber-600 shrink-0" />
                <span class="truncate">{{ student.priority_note || 'Prioritas' }}</span>
              </span>
              <ChevronRight class="w-4 h-4 text-amber-700 shrink-0" />
            </template>

            <template v-else>
              <span class="text-xs font-semibold text-slate-500">Siap Dinilai</span>
              <ChevronRight class="w-4 h-4 text-slate-400 shrink-0" />
            </template>
          </div>
        </button>
      </section>
    </main>

    <!-- Modals -->
    <QrScannerModal
      :open="scannerOpen"
      @close="scannerOpen = false"
      @scanned="handleQrScanned"
    />

    <DoubleGradingModal
      :open="guardModalOpen"
      :student="guardedStudent"
      @close="guardModalOpen = false"
      @read-only="openReadOnly"
      @unlocked="openUnlockedRevision"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ChevronRight, QrCode, RefreshCw, Search, Star, Users } from 'lucide-vue-next'
import AppNavbar from '@/components/AppNavbar.vue'
import QrScannerModal from '@/components/penguji/QrScannerModal.vue'
import DoubleGradingModal from '@/components/penguji/DoubleGradingModal.vue'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'
import { formatScore } from '@/utils/formatters'

const router = useRouter()
const toast = useToast()

const classes = ref([])
const selectedClassId = ref(null)
const students = ref([])
const stats = ref({
  total: 0,
  kompeten: 0,
  remedial: 0,
  priority: 0,
  pending: 0,
  graded: 0,
})
const loading = ref(false)
const searchQuery = ref('')
const statusFilter = ref('all')

const scannerOpen = ref(false)
const guardModalOpen = ref(false)
const guardedStudent = ref(null)

const progressPercent = computed(() => {
  if (!stats.value.total) return 0
  return Math.round((stats.value.graded / stats.value.total) * 100)
})

const filterPills = computed(() => [
  {
    key: 'all',
    label: 'Semua',
    count: stats.value.total,
    dotClass: 'bg-slate-600',
    activeClass: 'bg-slate-900 border-slate-900 text-white',
    idleClass: 'bg-white border-slate-200 text-slate-700 hover:bg-slate-100',
  },
  {
    key: 'priority',
    label: 'Prioritas',
    count: stats.value.priority,
    dotClass: 'bg-amber-500',
    activeClass: 'bg-amber-500 border-amber-500 text-white',
    idleClass: 'bg-amber-50 border-amber-200 text-amber-800 hover:bg-amber-100',
  },
  {
    key: 'pending',
    label: 'Menunggu',
    count: stats.value.pending,
    dotClass: 'bg-slate-400',
    activeClass: 'bg-slate-600 border-slate-600 text-white',
    idleClass: 'bg-slate-100 border-slate-200 text-slate-700 hover:bg-slate-200',
  },
  {
    key: 'kompeten',
    label: 'Kompeten',
    count: stats.value.kompeten,
    dotClass: 'bg-emerald-500',
    activeClass: 'bg-emerald-600 border-emerald-600 text-white',
    idleClass: 'bg-emerald-50 border-emerald-200 text-emerald-800 hover:bg-emerald-100',
  },
  {
    key: 'remedial',
    label: 'Remedial',
    count: stats.value.remedial,
    dotClass: 'bg-rose-500',
    activeClass: 'bg-rose-600 border-rose-600 text-white',
    idleClass: 'bg-rose-50 border-rose-200 text-rose-800 hover:bg-rose-100',
  },
])

const filteredStudents = computed(() => {
  return students.value.filter((s) => {
    if (statusFilter.value !== 'all' && s.queue_status !== statusFilter.value) {
      return false
    }
    if (searchQuery.value) {
      const q = searchQuery.value.toLowerCase()
      return s.name.toLowerCase().includes(q) || s.nis.toLowerCase().includes(q)
    }
    return true
  })
})

function cardClass(status) {
  switch (status) {
    case 'kompeten':
      return 'bg-emerald-50/90 border-emerald-400 hover:bg-emerald-100/80'
    case 'remedial':
      return 'bg-rose-50/90 border-rose-400 hover:bg-rose-100/80'
    case 'priority':
      return 'bg-amber-50 border-amber-400 ring-2 ring-amber-300/60 hover:bg-amber-100/80'
    default:
      return 'bg-white border-slate-300 hover:border-indigo-400 hover:bg-slate-50'
  }
}

function statusBadgeClass(status) {
  switch (status) {
    case 'kompeten':
      return 'bg-emerald-600 text-white'
    case 'remedial':
      return 'bg-rose-600 text-white'
    case 'priority':
      return 'bg-amber-500 text-white'
    default:
      return 'bg-slate-200 text-slate-700'
  }
}

function statusLabel(status) {
  switch (status) {
    case 'kompeten':
      return 'Kompeten'
    case 'remedial':
      return 'Remedial'
    case 'priority':
      return 'Prioritas'
    default:
      return 'Menunggu'
  }
}

async function loadClasses() {
  const { data } = await api.get('/classes')
  classes.value = data.classes
  if (classes.value.length > 0 && !selectedClassId.value) {
    selectedClassId.value = classes.value[0].id
  }
}

async function loadQueue() {
  if (!selectedClassId.value) return
  loading.value = true
  try {
    const { data } = await api.get(`/classes/${selectedClassId.value}/queue`)
    students.value = data.students
    stats.value = data.stats
  } catch (err) {
    toast.error(err.response?.data?.message || 'Gagal memuat antrean siswa.')
  } finally {
    loading.value = false
  }
}

function handleSelectStudent(student) {
  if (student.already_graded) {
    guardedStudent.value = student
    guardModalOpen.value = true
    return
  }

  router.push({ name: 'penguji.assess', params: { studentId: student.id } })
}

async function handleQrScanned(qrToken) {
  scannerOpen.value = false
  try {
    const { data } = await api.get(`/students/scan/${encodeURIComponent(qrToken)}`)
    const scannedStudent = data.student

    if (data.already_graded) {
      guardedStudent.value = scannedStudent
      guardModalOpen.value = true
    } else {
      toast.success(`QR terverifikasi: ${scannedStudent.name}`)
      router.push({ name: 'penguji.assess', params: { studentId: scannedStudent.id } })
    }
  } catch (err) {
    toast.error(err.response?.data?.message || 'QR Code siswa tidak ditemukan.')
  }
}

function openReadOnly(student) {
  guardModalOpen.value = false
  router.push({
    name: 'penguji.assess',
    params: { studentId: student.id },
    query: { mode: 'readonly' },
  })
}

function openUnlockedRevision(student) {
  guardModalOpen.value = false
  router.push({
    name: 'penguji.assess',
    params: { studentId: student.id },
    query: { mode: 'revision' },
  })
}

onMounted(async () => {
  try {
    await loadClasses()
    await loadQueue()
  } catch (err) {
    toast.error('Gagal memuat daftar kelas.')
  }
})
</script>
