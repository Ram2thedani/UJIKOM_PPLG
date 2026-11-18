<template>
  <div class="space-y-6">
    <!-- Panel Konfigurasi & Filter Laporan -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5 sm:p-6 space-y-5">
      <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-4">
        <div>
          <h2 class="text-xl font-extrabold text-slate-900">
            Laporan &amp; Dokumentasi Nilai Ujikom (PDF / Word)
          </h2>
          <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
            Unduh laporan nilai keseluruhan per jurusan/kelas maupun lembar nilai per siswa lengkap dengan rata-rata setiap bobot rubrik, nilai akhir, serta tanda tangan Penguji Internal &amp; Eksternal.
          </p>
        </div>

        <!-- Mode Switcher: Per Siswa vs Keseluruhan Jurusan/Kelas -->
        <div class="inline-flex p-1.5 rounded-2xl bg-slate-100 border border-slate-200">
          <button
            type="button"
            class="min-h-[42px] px-4 rounded-xl text-xs sm:text-sm font-extrabold flex items-center gap-2 transition"
            :class="
              reportMode === 'student'
                ? 'bg-indigo-600 text-white shadow-xs'
                : 'text-slate-600 hover:bg-white'
            "
            @click="reportMode = 'student'"
          >
            <UserCheck class="w-4 h-4" />
            <span>Laporan Per Siswa</span>
          </button>

          <button
            type="button"
            class="min-h-[42px] px-4 rounded-xl text-xs sm:text-sm font-extrabold flex items-center gap-2 transition"
            :class="
              reportMode === 'class'
                ? 'bg-indigo-600 text-white shadow-xs'
                : 'text-slate-600 hover:bg-white'
            "
            @click="reportMode = 'class'"
          >
            <FileSpreadsheet class="w-4 h-4" />
            <span>Laporan Per Kelas / Jurusan</span>
          </button>
        </div>
      </div>

      <!-- Filter Lingkup (Jurusan/Kelas) & Pilihan Siswa -->
      <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
        <div :class="reportMode === 'student' ? 'md:col-span-5' : 'md:col-span-6'">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">
            Pilih Jurusan / Kelas
          </label>
          <AppSelect
            v-model="selectedScope"
            :options="scopeOptions"
            @change="loadReportData"
          />
        </div>

        <div v-if="reportMode === 'student'" class="md:col-span-7">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">
            Pilih Siswa ({{ rows.length }} Siswa)
          </label>
          <AppSelect
            v-model="selectedStudentId"
            searchable
            search-placeholder="Ketik nama atau NIS siswa..."
            :options="studentSelectOptions"
            placeholder="Pilih siswa untuk pratinjau & unduh..."
          />
        </div>

        <div v-else class="md:col-span-6">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">
            Tempat &amp; Tanggal Tanda Tangan Laporan
          </label>
          <input
            v-model="reportMeta.dateLocation"
            type="text"
            placeholder="Contoh: Bandung, 18 November 2026"
            class="w-full min-h-[46px] px-3.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-800 focus:border-indigo-500 outline-none"
          />
        </div>
      </div>

      <!-- Pengaturan Nama Penandatangan (Penguji Internal & Penguji Eksternal) -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2 border-t border-slate-100">
        <div v-if="reportMode === 'student'">
          <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">
            Tempat &amp; Tanggal Laporan
          </label>
          <input
            v-model="reportMeta.dateLocation"
            type="text"
            placeholder="Contoh: Bandung, 18 November 2026"
            class="w-full min-h-[42px] px-3.5 rounded-xl border border-slate-300 text-xs sm:text-sm font-semibold text-slate-800 focus:border-indigo-500 outline-none"
          />
        </div>

        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-indigo-600 mb-1">
            Tanda Tangan Penguji Internal
          </label>
          <input
            v-model="reportMeta.internalExaminerName"
            type="text"
            :placeholder="autoInternalName || 'Nama Penguji Internal...'"
            class="w-full min-h-[42px] px-3.5 rounded-xl border border-slate-300 text-xs sm:text-sm font-semibold text-slate-800 focus:border-indigo-500 outline-none"
          />
        </div>

        <div>
          <label class="block text-xs font-bold uppercase tracking-wider text-teal-700 mb-1">
            Tanda Tangan Penguji Eksternal
          </label>
          <input
            v-model="reportMeta.externalExaminerName"
            type="text"
            :placeholder="autoExternalName || 'Nama Penguji Eksternal (DUDIKA)...'"
            class="w-full min-h-[42px] px-3.5 rounded-xl border border-slate-300 text-xs sm:text-sm font-semibold text-slate-800 focus:border-indigo-500 outline-none"
          />
        </div>
      </div>

      <!-- Action Bar Download PDF & Word -->
      <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
        <template v-if="reportMode === 'student'">
          <div class="flex flex-wrap items-center gap-2.5">
            <button
              type="button"
              :disabled="!activeStudentRow"
              class="min-h-[46px] px-5 rounded-xl bg-rose-600 hover:bg-rose-700 disabled:opacity-50 text-white font-extrabold text-xs sm:text-sm flex items-center gap-2 shadow-sm transition active:scale-95"
              @click="downloadCurrentStudent('pdf')"
            >
              <Printer class="w-4 h-4" />
              <span>Download PDF Siswa Ini</span>
            </button>

            <button
              type="button"
              :disabled="!activeStudentRow"
              class="min-h-[46px] px-5 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-extrabold text-xs sm:text-sm flex items-center gap-2 shadow-sm transition active:scale-95"
              @click="downloadCurrentStudent('word')"
            >
              <FileText class="w-4 h-4" />
              <span>Download Word (.doc) Siswa Ini</span>
            </button>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <button
              type="button"
              :disabled="rows.length === 0"
              class="min-h-[42px] px-4 rounded-xl bg-slate-800 hover:bg-slate-900 disabled:opacity-50 text-white font-bold text-xs flex items-center gap-1.5 transition"
              @click="downloadAllBundle('pdf')"
            >
              <Printer class="w-3.5 h-3.5" />
              <span>Bundel PDF Semua ({{ rows.length }} Siswa)</span>
            </button>

            <button
              type="button"
              :disabled="rows.length === 0"
              class="min-h-[42px] px-4 rounded-xl bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 disabled:opacity-50 text-indigo-800 font-bold text-xs flex items-center gap-1.5 transition"
              @click="downloadAllBundle('word')"
            >
              <Download class="w-3.5 h-3.5" />
              <span>Bundel Word (.doc) Semua Siswa</span>
            </button>
          </div>
        </template>

        <template v-else>
          <div class="text-xs font-semibold text-slate-500">
            Menampilkan rekapitulasi <strong>{{ reportPayload?.class?.name }}</strong> ({{ rows.length }} siswa)
          </div>
          <div class="flex flex-wrap items-center gap-2.5">
            <button
              type="button"
              :disabled="!reportPayload || rows.length === 0"
              class="min-h-[46px] px-5 rounded-xl bg-rose-600 hover:bg-rose-700 disabled:opacity-50 text-white font-extrabold text-xs sm:text-sm flex items-center gap-2 shadow-sm transition active:scale-95"
              @click="downloadClassSummary('pdf')"
            >
              <Printer class="w-4 h-4" />
              <span>Download PDF Rekapitulasi Kelas/Jurusan</span>
            </button>

            <button
              type="button"
              :disabled="!reportPayload || rows.length === 0"
              class="min-h-[46px] px-5 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white font-extrabold text-xs sm:text-sm flex items-center gap-2 shadow-sm transition active:scale-95"
              @click="downloadClassSummary('word')"
            >
              <FileText class="w-4 h-4" />
              <span>Download Word (.doc) Rekapitulasi</span>
            </button>
          </div>
        </template>
      </div>
    </div>

    <!-- PRATINJAU DOKUMEN RESMI (A4 DOCUMENT PREVIEW) -->
    <div v-if="loading" class="h-96 rounded-3xl bg-white border border-slate-200 animate-pulse"></div>

    <div
      v-else-if="reportMode === 'student' && activeStudentRow"
      class="bg-white rounded-3xl border-2 border-slate-300 shadow-md p-6 sm:p-10 max-w-4xl mx-auto space-y-5"
    >
      <!-- Kop Surat Pratinjau -->
      <div class="border-b-4 border-double border-slate-900 pb-4 flex items-center justify-between gap-4">
        <img :src="'/api/logo'" alt="Logo Marhas" class="w-16 h-16 object-contain shrink-0" />
        <div class="text-center flex-1">
          <h3 class="text-xl font-black uppercase tracking-wide text-slate-900">MARHAS UJIKOM</h3>
          <p class="text-sm font-extrabold text-slate-800">
            SISTEM PENILAIAN UJI KOMPETENSI KEAHLIAN (UJIKOM)
          </p>
          <p class="text-xs text-slate-500 mt-0.5">
            Program Keahlian:
            {{
              activeStudentRow.major === 'TMS'
                ? 'Teknik Mekanik / Sepeda Motor (TMS)'
                : 'Pengembangan Perangkat Lunak dan Gim (PPLG / RPL)'
            }}
          </p>
        </div>
        <div class="w-16 shrink-0"></div>
      </div>

      <div class="text-center">
        <h4 class="text-base font-black uppercase underline text-slate-900">
          LEMBAR LAPORAN HASIL PENILAIAN PESERTA UJIKOM
        </h4>
        <p class="text-xs text-slate-500 mt-0.5">
          Tahun Ajaran {{ activeStudentRow.academic_year || '2026/2027' }}
        </p>
      </div>

      <!-- Identitas Siswa -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs sm:text-sm bg-slate-50 rounded-2xl p-4 border border-slate-200">
        <div><span class="text-slate-500">Nama Siswa:</span> <strong class="text-slate-900 ml-1">{{ activeStudentRow.name }}</strong></div>
        <div><span class="text-slate-500">Kelas / Jurusan:</span> <strong class="text-slate-900 ml-1">{{ activeStudentRow.class_name }} ({{ activeStudentRow.major }})</strong></div>
        <div><span class="text-slate-500">NIS:</span> <strong class="font-mono text-slate-900 ml-1">{{ activeStudentRow.nis }}</strong></div>
        <div>
          <span class="text-slate-500">Status Akhir:</span>
          <span
            class="ml-1.5 px-2.5 py-0.5 rounded-full text-xs font-extrabold uppercase"
            :class="{
              'bg-emerald-100 text-emerald-800': activeStudentRow.status === 'kompeten',
              'bg-rose-100 text-rose-800': activeStudentRow.status === 'remedial',
              'bg-slate-200 text-slate-700': activeStudentRow.status === 'pending',
            }"
          >
            {{
              activeStudentRow.status === 'kompeten'
                ? 'KOMPETEN (LULUS)'
                : activeStudentRow.status === 'remedial'
                  ? 'PERLU REMEDIAL'
                  : 'BELUM DINILAI'
            }}
          </span>
        </div>
        <div class="sm:col-span-2"><span class="text-slate-500">Waktu Penilaian:</span> <strong class="text-slate-900 ml-1">{{ activeStudentRow.submitted_at || '-' }}</strong></div>
      </div>

      <!-- Tabel Rincian Nilai Rata-Rata Skor Rubrik -->
      <div class="overflow-x-auto">
        <table class="w-full text-xs sm:text-sm border-collapse border border-slate-400">
          <thead>
            <tr class="bg-slate-100 text-slate-900 font-bold text-center">
              <th class="border border-slate-400 px-3 py-2.5 w-12">No</th>
              <th class="border border-slate-400 px-4 py-2.5 text-left">Aspek Kompetensi / Butir Rubrik Penilaian</th>
              <th class="border border-slate-400 px-4 py-2.5 w-44">Rata-Rata Skor (0-100)</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(rubric, idx) in rubrics" :key="rubric.id">
              <td class="border border-slate-400 px-3 py-2.5 text-center">{{ idx + 1 }}</td>
              <td class="border border-slate-400 px-4 py-2.5">
                <p class="font-bold text-slate-900">{{ rubric.title }}</p>
              </td>
              <td class="border border-slate-400 px-4 py-2.5 text-center font-bold text-slate-900">
                {{ formatScore(activeStudentRow.scores?.[rubric.id]?.raw_score) }}
              </td>
            </tr>

            <!-- Nilai Akhir Rata-Rata -->
            <tr class="bg-indigo-50 font-black text-slate-900">
              <td colspan="2" class="border border-slate-400 px-4 py-3 text-right">
                NILAI AKHIR RATA-RATA
              </td>
              <td class="border border-slate-400 px-4 py-3 text-center text-base text-indigo-700">
                {{ formatScore(activeStudentRow.total_hasil) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Catatan Penguji -->
      <div class="rounded-xl border border-slate-300 bg-slate-50 p-3.5 text-xs space-y-1">
        <p>
          <strong>Tag Evaluasi Penguji:</strong>
          {{ activeStudentRow.quick_tags?.length ? activeStudentRow.quick_tags.join(', ') : '-' }}
        </p>
        <p>
          <strong>Catatan Penguji (Internal &amp; Eksternal):</strong>
          {{ activeStudentRow.examiner_note || 'Tidak ada catatan tambahan.' }}
        </p>
      </div>

      <!-- Blok Tanda Tangan Bawah: Penguji Internal & Penguji Eksternal -->
      <div class="pt-4 grid grid-cols-2 gap-6 text-center text-xs sm:text-sm">
        <div>
          <p class="text-transparent select-none">.</p>
          <p class="font-bold text-slate-900">Penguji Internal</p>
          <p class="text-xs text-slate-500">( Guru Produktif / Sekolah )</p>
          <div class="h-16"></div>
          <p class="font-bold underline text-slate-900">
            ( {{ effectiveInternalName }} )
          </p>
        </div>

        <div>
          <p class="text-slate-700">{{ reportMeta.dateLocation }}</p>
          <p class="font-bold text-slate-900">Penguji Eksternal</p>
          <p class="text-xs text-slate-500">( Asesor DUDIKA / Industri )</p>
          <div class="h-16"></div>
          <p class="font-bold underline text-slate-900">
            ( {{ effectiveExternalName }} )
          </p>
        </div>
      </div>
    </div>

    <!-- Tabel Daftar Siswa untuk Unduh Cepat Per Siswa (PDF / Word) -->
    <AdminDataTable
      :rows="rows"
      :columns="studentReportTableColumns"
      row-key="student_id"
      :default-page-size="10"
      empty-text="Belum ada data siswa pada kelas/jurusan ini."
    >
      <template #toolbar-left>
        <span class="text-sm font-extrabold text-slate-900 pl-2 border-l border-slate-200">
          Daftar Laporan Nilai Per Siswa ({{ reportPayload?.class?.name }})
        </span>
      </template>

      <template #cell-nis="{ row }">
        <span class="font-mono font-bold text-slate-700">{{ row.nis }}</span>
      </template>

      <template #cell-name="{ row }">
        <button
          type="button"
          class="font-bold text-indigo-700 hover:underline text-left"
          @click="selectStudentForPreview(row)"
        >
          {{ row.name }}
        </button>
      </template>

      <template #cell-total_hasil="{ row }">
        <span
          v-if="row.total_hasil !== null"
          class="font-black"
          :class="row.total_hasil >= 80 ? 'text-emerald-700' : 'text-rose-700'"
        >
          {{ formatScore(row.total_hasil) }}
        </span>
        <span v-else class="text-slate-400">-</span>
      </template>

      <template #cell-status="{ row }">
        <span
          class="px-2.5 py-1 rounded-full text-xs font-extrabold uppercase"
          :class="{
            'bg-emerald-100 text-emerald-800': row.status === 'kompeten',
            'bg-rose-100 text-rose-800': row.status === 'remedial',
            'bg-slate-100 text-slate-600': row.status === 'pending',
          }"
        >
          {{ row.status === 'pending' ? 'Menunggu' : row.status }}
        </span>
      </template>

      <template #cell-actions="{ row }">
        <div class="inline-flex items-center gap-1.5">
          <button
            type="button"
            class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-indigo-100 text-slate-700 hover:text-indigo-800 text-xs font-bold transition"
            @click="selectStudentForPreview(row)"
          >
            Pratinjau
          </button>
          <button
            type="button"
            class="px-2.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-700 text-xs font-bold flex items-center gap-1 transition"
            @click="downloadSpecificStudent(row, 'pdf')"
          >
            <Printer class="w-3.5 h-3.5" />
            <span>PDF</span>
          </button>
          <button
            type="button"
            class="px-2.5 py-1.5 rounded-xl bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-700 text-xs font-bold flex items-center gap-1 transition"
            @click="downloadSpecificStudent(row, 'word')"
          >
            <FileText class="w-3.5 h-3.5" />
            <span>Word</span>
          </button>
        </div>
      </template>
    </AdminDataTable>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import {
  Download,
  FileSpreadsheet,
  FileText,
  Printer,
  UserCheck,
} from 'lucide-vue-next'
import AppSelect from '@/components/AppSelect.vue'
import AdminDataTable from '@/components/admin/AdminDataTable.vue'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'
import { formatScore } from '@/utils/formatters'
import {
  exportAllStudentsBundleReport,
  exportClassSummaryDocument,
  exportSingleStudentReport,
} from '@/utils/reportExport'

const props = defineProps({
  classes: {
    type: Array,
    default: () => [],
  },
})

const toast = useToast()

const reportMode = ref('student') // 'student' | 'class'
const selectedScope = ref('all|PPLG')
const selectedStudentId = ref(null)
const loading = ref(false)
const reportPayload = ref(null)

const defaultDateStr = new Intl.DateTimeFormat('id-ID', {
  day: 'numeric',
  month: 'long',
  year: 'numeric',
}).format(new Date())

const reportMeta = reactive({
  dateLocation: `Bandung, ${defaultDateStr}`,
  internalExaminerName: '',
  externalExaminerName: '',
})

const scopeOptions = computed(() => [
  {
    value: 'all|PPLG',
    label: 'Semua Kelas — Jurusan PPLG',
    badge: 'PPLG',
    badgeClass: 'bg-indigo-100 text-indigo-800',
    sublabel: 'Seluruh siswa jurusan PPLG',
  },
  {
    value: 'all|TMS',
    label: 'Semua Kelas — Jurusan TMS',
    badge: 'TMS',
    badgeClass: 'bg-amber-100 text-amber-800',
    sublabel: 'Seluruh siswa jurusan TMS',
  },
  ...props.classes.map((cls) => ({
    value: String(cls.id),
    label: cls.name,
    badge: cls.major,
    badgeClass: cls.major === 'TMS' ? 'bg-amber-100 text-amber-800' : 'bg-indigo-100 text-indigo-800',
    sublabel: `Tahun Ajaran ${cls.academic_year} • ${cls.students_count || 0} Siswa`,
  })),
])

const rows = computed(() => reportPayload.value?.rows || [])
const rubrics = computed(() => reportPayload.value?.rubrics || [])
const examiners = computed(() => reportPayload.value?.examiners || [])

const studentSelectOptions = computed(() =>
  rows.value.map((r) => ({
    value: r.student_id,
    label: `${r.nis} — ${r.name}`,
    badge: r.status === 'kompeten' ? 'KOMPETEN' : r.status === 'remedial' ? 'REMEDIAL' : 'MENUNGGU',
    badgeClass:
      r.status === 'kompeten'
        ? 'bg-emerald-100 text-emerald-800'
        : r.status === 'remedial'
          ? 'bg-rose-100 text-rose-800'
          : 'bg-slate-200 text-slate-700',
    sublabel: `${r.class_name} (${r.major}) • Nilai Akhir: ${r.total_hasil !== null ? Number(r.total_hasil).toFixed(2) : '-'}`,
  }))
)

const activeStudentRow = computed(() =>
  rows.value.find((r) => Number(r.student_id) === Number(selectedStudentId.value)) || rows.value[0] || null
)

const autoInternalName = computed(() => {
  if (activeStudentRow.value?.internal_examiners?.length) {
    return activeStudentRow.value.internal_examiners.join(', ')
  }
  return examiners.value
    .filter((e) => e.examiner_type !== 'eksternal')
    .map((e) => e.name)
    .join(', ')
})

const autoExternalName = computed(() => {
  if (activeStudentRow.value?.external_examiners?.length) {
    return activeStudentRow.value.external_examiners.join(', ')
  }
  return examiners.value
    .filter((e) => e.examiner_type === 'eksternal')
    .map((e) => e.name)
    .join(', ')
})

const effectiveInternalName = computed(
  () => reportMeta.internalExaminerName.trim() || autoInternalName.value || '........................................'
)

const effectiveExternalName = computed(
  () => reportMeta.externalExaminerName.trim() || autoExternalName.value || '........................................'
)

const studentReportTableColumns = [
  { key: 'nis', label: 'NIS' },
  { key: 'name', label: 'Nama Siswa' },
  { key: 'class_name', label: 'Kelas' },
  {
    key: 'graded_examiner_count',
    label: 'Penguji Menilai',
    align: 'center',
    accessor: (r) => `${r.graded_examiner_count}/${r.assigned_examiner_count}`,
  },
  {
    key: 'total_hasil',
    label: 'Nilai Akhir (total_hasil)',
    align: 'center',
    accessor: (r) => (r.total_hasil !== null && r.total_hasil !== undefined ? Number(r.total_hasil).toFixed(2) : '-'),
  },
  { key: 'status', label: 'Status', align: 'center' },
  { key: 'actions', label: 'Unduh Laporan (PDF / Word)', align: 'right', sortable: false },
]

async function loadReportData() {
  loading.value = true
  try {
    let url = ''
    if (selectedScope.value.startsWith('all|')) {
      const major = selectedScope.value.split('|')[1]
      url = `/admin/export/all?major=${encodeURIComponent(major)}`
    } else {
      url = `/admin/export/${selectedScope.value}`
    }
    const { data } = await api.get(url)
    reportPayload.value = data

    if (data.rows?.length > 0) {
      const stillExists = data.rows.some((r) => Number(r.student_id) === Number(selectedStudentId.value))
      if (!stillExists) {
        selectedStudentId.value = data.rows[0].student_id
      }
    } else {
      selectedStudentId.value = null
    }
  } catch {
    toast.error('Gagal memuat data laporan.')
  } finally {
    loading.value = false
  }
}

watch(activeStudentRow, (row) => {
  if (row) {
    reportMeta.internalExaminerName =
      row.internal_examiners?.join(', ') || autoInternalName.value || ''
    reportMeta.externalExaminerName =
      row.external_examiners?.join(', ') || autoExternalName.value || ''
  }
})

function selectStudentForPreview(row) {
  selectedStudentId.value = row.student_id
  reportMode.value = 'student'
  window.scrollTo({ top: 140, behavior: 'smooth' })
}

async function downloadCurrentStudent(format) {
  if (!activeStudentRow.value) return
  await exportSingleStudentReport({
    row: activeStudentRow.value,
    rubrics: rubrics.value,
    examiners: examiners.value,
    reportMeta: {
      dateLocation: reportMeta.dateLocation,
      internalExaminerName: reportMeta.internalExaminerName || autoInternalName.value,
      externalExaminerName: reportMeta.externalExaminerName || autoExternalName.value,
    },
    format,
  })
}

async function downloadSpecificStudent(row, format) {
  await exportSingleStudentReport({
    row,
    rubrics: rubrics.value,
    examiners: examiners.value,
    reportMeta: {
      dateLocation: reportMeta.dateLocation,
      internalExaminerName:
        reportMeta.internalExaminerName || row.internal_examiners?.join(', ') || autoInternalName.value,
      externalExaminerName:
        reportMeta.externalExaminerName || row.external_examiners?.join(', ') || autoExternalName.value,
    },
    format,
  })
}

async function downloadAllBundle(format) {
  if (!reportPayload.value) return
  await exportAllStudentsBundleReport({
    payload: reportPayload.value,
    reportMeta: {
      dateLocation: reportMeta.dateLocation,
      internalExaminerName: reportMeta.internalExaminerName,
      externalExaminerName: reportMeta.externalExaminerName,
    },
    format,
  })
}

async function downloadClassSummary(format) {
  if (!reportPayload.value) return
  await exportClassSummaryDocument({
    payload: reportPayload.value,
    reportMeta: {
      dateLocation: reportMeta.dateLocation,
      internalExaminerName: reportMeta.internalExaminerName || autoInternalName.value,
      externalExaminerName: reportMeta.externalExaminerName || autoExternalName.value,
    },
    format,
  })
}

onMounted(loadReportData)
</script>
