<template>
  <div class="no-print min-h-screen bg-slate-50 pb-12">
    <AppNavbar subtitle="Panel Administrator Ujikom (PPLG & TMS)" />

    <main class="max-w-7xl mx-auto px-4 sm:px-6 pt-6 space-y-6">
      <!-- Navigation Tabs + Global Actions -->
      <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-3 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            type="button"
            class="min-h-[44px] px-4 py-2 rounded-2xl text-sm font-bold flex items-center gap-2 transition"
            :class="
              activeTab === tab.key
                ? 'bg-indigo-600 text-white shadow-sm'
                : 'text-slate-600 hover:bg-slate-100'
            "
            @click="activeTab = tab.key"
          >
            <component :is="tab.icon" class="w-4 h-4" />
            <span>{{ tab.label }}</span>
          </button>
        </div>

        <div class="flex items-center gap-2.5 px-2">
          <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 cursor-pointer">
            <input
              v-model="autoRefresh"
              type="checkbox"
              class="w-4 h-4 rounded text-indigo-600"
            />
            <span>Live 15d</span>
          </label>
          <button
            type="button"
            :disabled="loadingDashboard"
            class="min-h-[40px] px-3.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold flex items-center gap-1.5 transition"
            @click="refreshAll"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loadingDashboard }" />
            <span>Refresh</span>
          </button>
        </div>
      </div>

      <!-- TAB 1: MONITORING REAL-TIME & EKSPOR EXCEL -->
      <section v-if="activeTab === 'monitoring'" class="space-y-6">
        <div class="bg-gradient-to-r from-indigo-600 to-blue-600 rounded-3xl p-5 sm:p-6 text-white flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-lg shadow-indigo-600/15">
          <div>
            <h2 class="text-xl font-extrabold">Monitoring &amp; Rekapitulasi Nilai Ujikom</h2>
            <p class="text-sm text-indigo-100 mt-0.5">
              Ekspor Excel otomatis membuat <strong>Sheet per Penguji</strong>, <strong>Rubrik_Penilaian</strong>, dan sheet akhir <strong>total_hasil</strong> (Total Nilai ÷ Banyak Penguji yang Menilai).
            </p>
          </div>
          <div class="flex flex-wrap items-center gap-2.5">
            <div class="w-64">
              <AppSelect
                v-model="exportTarget"
                variant="glass"
                :options="exportTargetOptions"
                @change="loadRecapData"
              />
            </div>
            <button
              type="button"
              :disabled="exporting"
              class="min-h-[46px] px-5 rounded-xl bg-emerald-500 hover:bg-emerald-400 disabled:opacity-60 text-white font-extrabold text-sm shadow-md flex items-center gap-2 transition active:scale-95"
              @click="handleExportExcel"
            >
              <Loader2 v-if="exporting" class="w-4 h-4 animate-spin" />
              <FileSpreadsheet v-else class="w-4 h-4" />
              <span>{{ exporting ? 'Menyiapkan...' : 'Ekspor Multi-Sheet Excel (.xlsx)' }}</span>
            </button>
          </div>
        </div>

        <!-- KPI Summary Cards -->
        <div v-if="dashboard" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
          <div class="bg-white rounded-2xl border border-slate-200 p-4">
            <p class="text-xs font-bold text-slate-400 uppercase">Total Siswa</p>
            <p class="text-2xl font-black text-slate-900 mt-1">{{ dashboard.summary.total_students }}</p>
          </div>
          <div class="bg-white rounded-2xl border border-slate-200 p-4">
            <p class="text-xs font-bold text-slate-400 uppercase">Sudah Dinilai</p>
            <p class="text-2xl font-black text-indigo-600 mt-1">
              {{ dashboard.summary.graded_count }}
              <span class="text-xs font-bold text-slate-400">({{ dashboard.summary.progress_percent }}%)</span>
            </p>
          </div>
          <div class="bg-emerald-50 rounded-2xl border border-emerald-200 p-4">
            <p class="text-xs font-bold text-emerald-700 uppercase">Kompeten</p>
            <p class="text-2xl font-black text-emerald-700 mt-1">{{ dashboard.summary.kompeten_count }}</p>
          </div>
          <div class="bg-rose-50 rounded-2xl border border-rose-200 p-4">
            <p class="text-xs font-bold text-rose-700 uppercase">Remedial</p>
            <p class="text-2xl font-black text-rose-700 mt-1">{{ dashboard.summary.remedial_count }}</p>
          </div>
          <div class="bg-amber-50 rounded-2xl border border-amber-200 p-4">
            <p class="text-xs font-bold text-amber-700 uppercase">Menunggu</p>
            <p class="text-2xl font-black text-amber-800 mt-1">
              {{ dashboard.summary.waiting_count }}
              <span class="text-xs font-bold">({{ dashboard.summary.priority_waiting_count }} prio)</span>
            </p>
          </div>
          <div class="bg-white rounded-2xl border border-slate-200 p-4">
            <p class="text-xs font-bold text-slate-400 uppercase">Rata-Rata</p>
            <p class="text-2xl font-black text-slate-900 mt-1">{{ formatScore(dashboard.summary.average_score) }}</p>
          </div>
        </div>

        <!-- Progres Kelas & Tabel Progres Penguji -->
        <div v-if="dashboard" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <div class="lg:col-span-5 bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-extrabold text-slate-900 text-base">Progres Penilaian per Kelas</h3>
              <button
                type="button"
                class="text-xs font-bold text-indigo-600 hover:underline"
                @click="openAddClass"
              >
                + Tambah Kelas Manual
              </button>
            </div>

            <div class="space-y-4">
              <div
                v-for="cls in dashboard.class_progress"
                :key="cls.id"
                class="p-4 rounded-2xl bg-slate-50 border border-slate-100"
              >
                <div class="flex items-center justify-between mb-2 gap-2">
                  <div>
                    <div class="flex items-center gap-2">
                      <p class="font-extrabold text-slate-900 text-sm">{{ cls.name }}</p>
                      <span
                        class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase"
                        :class="cls.major === 'TMS' ? 'bg-amber-100 text-amber-800' : 'bg-indigo-100 text-indigo-800'"
                      >
                        {{ cls.major }}
                      </span>
                    </div>
                    <p class="text-xs text-slate-500">
                      {{ cls.academic_year }} • {{ cls.total }} siswa
                      <span v-if="cls.examiners?.length">
                        • Penguji: <strong>{{ cls.examiners.join(', ') }}</strong>
                      </span>
                    </p>
                  </div>
                  <span class="text-sm font-black text-indigo-600">{{ cls.progress_percent }}%</span>
                </div>
                <div class="w-full h-2.5 rounded-full bg-slate-200 overflow-hidden flex">
                  <div
                    class="bg-emerald-500 h-full"
                    :style="{ width: `${cls.total ? (cls.kompeten / cls.total) * 100 : 0}%` }"
                  ></div>
                  <div
                    class="bg-rose-500 h-full"
                    :style="{ width: `${cls.total ? (cls.remedial / cls.total) * 100 : 0}%` }"
                  ></div>
                </div>
                <div class="mt-2 flex flex-wrap gap-3 text-xs font-semibold text-slate-600">
                  <span class="text-emerald-700">Kompeten: {{ cls.kompeten }}</span>
                  <span class="text-rose-700">Remedial: {{ cls.remedial }}</span>
                  <span>Menunggu: {{ cls.waiting }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="lg:col-span-7 space-y-2">
            <AdminDataTable
              :rows="dashboard.examiner_progress"
              :columns="examinerProgressColumns"
              row-key="id"
              :default-page-size="5"
              empty-text="Belum ada data penguji."
            >
              <template #toolbar-left>
                <span class="text-sm font-extrabold text-slate-900 pl-2 border-l border-slate-200">
                  Progres &amp; Penugasan Penguji
                </span>
              </template>

              <template #cell-name="{ row }">
                <div class="flex items-center gap-1.5 flex-wrap">
                  <p class="font-bold text-slate-800">{{ row.name }}</p>
                  <span
                    v-if="row.role === 'penguji'"
                    class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase"
                    :class="
                      row.examiner_type === 'eksternal'
                        ? 'bg-teal-100 text-teal-800'
                        : 'bg-indigo-100 text-indigo-800'
                    "
                  >
                    {{ row.examiner_type === 'eksternal' ? 'Eksternal' : 'Internal' }}
                  </span>
                </div>
                <p class="text-xs text-slate-400">{{ row.username }}</p>
              </template>

              <template #cell-assigned_classes="{ row }">
                <div v-if="row.assigned_classes?.length" class="flex flex-wrap gap-1">
                  <span
                    v-for="cname in row.assigned_classes"
                    :key="cname"
                    class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 text-[11px] font-bold"
                  >
                    {{ cname }}
                  </span>
                </div>
                <span v-else class="text-xs text-slate-400">
                  {{ row.role === 'admin' ? 'Semua Kelas' : 'Belum diatur' }}
                </span>
              </template>

              <template #cell-total_graded="{ row }">
                <span class="font-extrabold text-indigo-600">{{ row.total_graded }}</span>
              </template>

              <template #cell-komp_rem="{ row }">
                <span class="text-xs font-bold">
                  <span class="text-emerald-600">{{ row.kompeten_count }}</span> /
                  <span class="text-rose-600">{{ row.remedial_count }}</span>
                </span>
              </template>

              <template #cell-average_score="{ row }">
                <span class="font-bold">{{ formatScore(row.average_score) }}</span>
              </template>
            </AdminDataTable>
          </div>
        </div>

        <!-- Tabel Rekapitulasi Multi-Penguji (Pratinjau Sheet Akhir: total_hasil) -->
        <div v-if="recapData" class="space-y-2">
          <div class="flex flex-wrap items-center justify-between gap-2 px-1">
            <div>
              <h3 class="font-extrabold text-slate-900 text-base">
                Tabel Rekapitulasi Nilai Siswa &amp; Perhitungan <code class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700">total_hasil</code>
              </h3>
              <p class="text-xs text-slate-500">
                Menampilkan nilai dari masing-masing penguji pada <strong>{{ recapData.class?.name }}</strong> serta <strong>total_hasil = Jumlah Nilai ÷ Banyaknya Penguji yang Menilai</strong>.
              </p>
            </div>
          </div>

          <AdminDataTable
            :rows="recapData.rows || []"
            :columns="recapTableColumns"
            row-key="student_id"
            :default-page-size="10"
            empty-text="Tidak ada data rekapitulasi siswa pada kelas ini."
          >
            <template #toolbar-right>
              <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-600">Filter Kelas:</span>
                <div class="w-60">
                  <AppSelect
                    v-model="exportTarget"
                    size="xs"
                    :options="exportTargetOptions"
                    @change="loadRecapData"
                  />
                </div>
              </div>
            </template>

            <template #cell-nis="{ row }">
              <span class="font-mono text-xs font-bold text-slate-700">{{ row.nis }}</span>
            </template>

            <template #cell-name="{ row }">
              <span class="font-bold text-slate-900">{{ row.name }}</span>
            </template>

            <template
              v-for="ex in recapData.examiners || []"
              :key="`slot-ex-${ex.id}`"
              #[`cell-examiner_${ex.id}`]="{ row }"
            >
              <span
                v-if="row.examiner_scores?.[ex.id]?.final_score !== undefined && row.examiner_scores?.[ex.id]?.final_score !== null"
                :class="row.examiner_scores[ex.id].status === 'kompeten' ? 'text-emerald-700 font-bold' : 'text-rose-700 font-bold'"
              >
                {{ formatScore(row.examiner_scores[ex.id].final_score) }}
              </span>
              <span v-else class="text-slate-300">-</span>
            </template>

            <template #cell-sum_score="{ row }">
              <span class="font-semibold text-slate-700">{{ formatScore(row.sum_score) }}</span>
            </template>

            <template #cell-graded_examiner_count="{ row }">
              <span class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-bold">
                {{ row.graded_examiner_count }} / {{ row.assigned_examiner_count }}
              </span>
            </template>

            <template #cell-total_hasil="{ row }">
              <span
                v-if="row.total_hasil !== null"
                class="font-black text-base"
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
          </AdminDataTable>
        </div>

        <!-- Tabel Penilaian Terbaru -->
        <div v-if="dashboard" class="space-y-2">
          <div class="px-1">
            <h3 class="font-extrabold text-slate-900 text-base">Riwayat Penilaian Terbaru</h3>
          </div>

          <AdminDataTable
            :rows="dashboard.recent_assessments || []"
            :columns="recentAssessmentsColumns"
            row-key="id"
            :default-page-size="10"
            empty-text="Belum ada penilaian yang disubmit."
          >
            <template #cell-student_name="{ row }">
              <p class="font-bold text-slate-800">{{ row.student_name }}</p>
              <p class="text-xs font-mono text-slate-400">{{ row.nis }}</p>
            </template>

            <template #cell-class_name="{ row }">
              <span class="text-slate-700 font-medium">{{ row.class_name }}</span>
              <span
                class="ml-1.5 px-1.5 py-0.5 rounded text-[10px] font-bold uppercase"
                :class="row.major === 'TMS' ? 'bg-amber-100 text-amber-800' : 'bg-indigo-100 text-indigo-800'"
              >
                {{ row.major }}
              </span>
            </template>

            <template #cell-examiner_name="{ row }">
              <div class="flex items-center gap-1.5 flex-wrap">
                <span class="font-semibold text-slate-800">{{ row.examiner_name }}</span>
                <span
                  class="px-1.5 py-0.5 rounded text-[10px] font-extrabold uppercase"
                  :class="
                    row.examiner_type === 'eksternal'
                      ? 'bg-teal-100 text-teal-800'
                      : 'bg-indigo-100 text-indigo-800'
                  "
                >
                  {{ row.examiner_type === 'eksternal' ? 'Eksternal' : 'Internal' }}
                </span>
              </div>
            </template>

            <template #cell-final_score="{ row }">
              <span class="font-black">{{ formatScore(row.final_score) }}</span>
            </template>

            <template #cell-status="{ row }">
              <span
                class="px-2.5 py-1 rounded-full text-xs font-extrabold uppercase"
                :class="row.status === 'kompeten' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
              >
                {{ row.status }}
              </span>
            </template>

            <template #cell-submitted_at="{ row }">
              <span class="text-xs text-slate-500">{{ formatDateTime(row.submitted_at) }}</span>
            </template>
          </AdminDataTable>
        </div>
      </section>

      <!-- TAB 2: MANAJEMEN KELAS & JURUSAN (PPLG / TMS) -->
      <section v-if="activeTab === 'classes'" class="space-y-5">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5 flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2 class="text-lg font-extrabold text-slate-900">Manajemen Kelas &amp; Jurusan (PPLG / TMS)</h2>
            <p class="text-xs text-slate-500">Tambahkan kelas secara manual dan pilih jurusan sebelum menugaskan penguji.</p>
          </div>
          <button
            type="button"
            class="min-h-[44px] px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold flex items-center gap-2 transition"
            @click="openAddClass"
          >
            <Plus class="w-4 h-4" />
            Tambah Kelas Manual
          </button>
        </div>

        <AdminDataTable
          :rows="classes"
          :columns="classesTableColumns"
          row-key="id"
          :default-page-size="10"
          empty-text="Belum ada data kelas."
        >
          <template #cell-name="{ row }">
            <span class="font-extrabold text-slate-900">{{ row.name }}</span>
          </template>

          <template #cell-major="{ row }">
            <span
              class="px-2.5 py-1 rounded-full text-xs font-extrabold uppercase"
              :class="row.major === 'TMS' ? 'bg-amber-100 text-amber-800' : 'bg-indigo-100 text-indigo-800'"
            >
              {{ row.major }}
            </span>
          </template>

          <template #cell-students_count="{ row }">
            <span class="font-bold text-slate-800">{{ row.students_count || 0 }}</span>
          </template>

          <template #cell-examiners="{ row }">
            <div v-if="row.examiners?.length" class="flex flex-wrap gap-1.5">
              <span
                v-for="ex in row.examiners"
                :key="ex.id"
                class="px-2 py-0.5 rounded-lg text-xs font-semibold inline-flex items-center gap-1"
                :class="
                  ex.examiner_type === 'eksternal'
                    ? 'bg-teal-50 text-teal-800 border border-teal-200'
                    : 'bg-indigo-50 text-indigo-800 border border-indigo-200'
                "
              >
                <span>{{ ex.name }}</span>
                <span class="text-[10px] font-extrabold uppercase opacity-80">
                  ({{ ex.examiner_type === 'eksternal' ? 'Eksternal' : 'Internal' }})
                </span>
              </span>
            </div>
            <span v-else class="text-xs text-rose-500 font-semibold">Belum ada penguji</span>
          </template>

          <template #cell-actions="{ row }">
            <div class="inline-flex items-center gap-1.5">
              <button
                type="button"
                class="p-2 rounded-xl bg-slate-100 hover:bg-indigo-100 text-slate-700 hover:text-indigo-700 transition"
                title="Edit Kelas"
                @click="openEditClass(row)"
              >
                <Pencil class="w-4 h-4" />
              </button>
              <button
                type="button"
                class="p-2 rounded-xl bg-slate-100 hover:bg-rose-100 text-slate-700 hover:text-rose-700 transition"
                title="Hapus Kelas"
                @click="deleteClass(row)"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
          </template>
        </AdminDataTable>
      </section>

      <!-- TAB 3: MANAJEMEN SISWA & CETAK QR MASSAL -->
      <section v-if="activeTab === 'students'" class="space-y-5">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
          <div class="flex flex-wrap items-center gap-3 flex-1">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Filter Kelas:</span>
            <div class="w-64">
              <AppSelect
                v-model="studentFilter.class_id"
                :options="studentClassFilterOptions"
                @change="loadStudents"
              />
            </div>
          </div>

          <div class="flex flex-wrap gap-2.5">
            <button
              type="button"
              class="min-h-[46px] px-4 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-sm font-bold flex items-center gap-2 transition"
              @click="printModalOpen = true"
            >
              <Printer class="w-4 h-4" />
              <span>Cetak Kartu Peserta ({{ students.length }})</span>
            </button>

            <button
              type="button"
              class="min-h-[46px] px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold flex items-center gap-2 transition"
              @click="importModalOpen = true"
            >
              <FileSpreadsheet class="w-4 h-4" />
              <span>Impor Excel/CSV</span>
            </button>

            <button
              type="button"
              class="min-h-[46px] px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold flex items-center gap-2 transition"
              @click="openAddStudent"
            >
              <Plus class="w-4 h-4" />
              <span>Tambah Siswa</span>
            </button>
          </div>
        </div>

        <AdminDataTable
          :rows="students"
          :columns="studentsTableColumns"
          row-key="id"
          :default-page-size="10"
          empty-text="Tidak ada data siswa yang sesuai filter."
        >
          <template #cell-nis="{ row }">
            <span class="font-mono font-bold text-slate-700">{{ row.nis }}</span>
          </template>

          <template #cell-name="{ row }">
            <p class="font-bold text-slate-900">{{ row.name }}</p>
            <p class="font-mono text-[11px] text-slate-400">QR: {{ row.qr_code_token.slice(0, 14) }}...</p>
          </template>

          <template #cell-class_name="{ row }">
            <span class="font-semibold text-slate-700">{{ row.class_name }}</span>
          </template>

          <template #cell-major="{ row }">
            <span
              class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase"
              :class="row.major === 'TMS' ? 'bg-amber-100 text-amber-800' : 'bg-indigo-100 text-indigo-800'"
            >
              {{ row.major }}
            </span>
          </template>

          <template #cell-priority="{ row }">
            <span
              v-if="row.is_priority"
              class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-900 text-xs font-bold"
            >
              <Star class="w-3 h-3 fill-amber-500 text-amber-600" />
              {{ row.priority_note || 'Prioritas' }}
            </span>
            <span v-else class="text-xs text-slate-400">Reguler</span>
          </template>

          <template #cell-assessment_status="{ row }">
            <span
              class="px-2.5 py-1 rounded-full text-xs font-extrabold uppercase"
              :class="{
                'bg-emerald-100 text-emerald-800': row.assessment_status === 'kompeten',
                'bg-rose-100 text-rose-800': row.assessment_status === 'remedial',
                'bg-slate-100 text-slate-600': row.assessment_status === 'pending',
              }"
            >
              {{ row.assessment_status === 'pending' ? 'Menunggu' : row.assessment_status }}
            </span>
          </template>

          <template #cell-final_score="{ row }">
            <p class="font-black text-slate-900">{{ formatScore(row.final_score) }}</p>
            <p class="text-[11px] font-semibold text-slate-500">
              {{ row.graded_examiner_count || 0 }}/{{ row.assigned_examiner_count || 1 }} Penguji
            </p>
          </template>

          <template #cell-actions="{ row }">
            <div class="inline-flex items-center gap-1.5">
              <button
                type="button"
                class="p-2 rounded-xl bg-slate-100 hover:bg-indigo-100 text-slate-700 hover:text-indigo-700 transition"
                title="Edit Siswa"
                @click="openEditStudent(row)"
              >
                <Pencil class="w-4 h-4" />
              </button>
              <button
                type="button"
                class="p-2 rounded-xl bg-slate-100 hover:bg-rose-100 text-slate-700 hover:text-rose-700 transition"
                title="Hapus Siswa"
                @click="deleteStudent(row)"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
          </template>
        </AdminDataTable>
      </section>

      <!-- TAB 4: MANAJEMEN AKUN PENGUJI & PENUGASAN KELAS -->
      <section v-if="activeTab === 'examiners'" class="space-y-5">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5 flex items-center justify-between gap-3">
          <div>
            <h2 class="text-lg font-extrabold text-slate-900">Daftar Akun Penguji &amp; Penugasan Kelas</h2>
            <p class="text-xs text-slate-500">
              Tetapkan kelas mana saja (bisa 1, 2, atau lebih kelas dari PPLG / TMS) yang diuji oleh masing-masing penguji.
            </p>
          </div>
          <button
            type="button"
            class="min-h-[44px] px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold flex items-center gap-2 transition shrink-0"
            @click="openAddExaminer"
          >
            <Plus class="w-4 h-4" />
            Tambah Penguji
          </button>
        </div>

        <AdminDataTable
          :rows="examiners"
          :columns="examinersTableColumns"
          row-key="id"
          :default-page-size="10"
          empty-text="Belum ada akun penguji."
        >
          <template #cell-name="{ row }">
            <span class="font-bold text-slate-900">{{ row.name }}</span>
          </template>

          <template #cell-examiner_type="{ row }">
            <span
              class="px-2.5 py-1 rounded-full text-xs font-extrabold uppercase"
              :class="
                row.examiner_type === 'eksternal'
                  ? 'bg-teal-100 text-teal-800'
                  : 'bg-indigo-100 text-indigo-800'
              "
            >
              {{ row.examiner_type === 'eksternal' ? 'Eksternal' : 'Internal' }}
            </span>
          </template>

          <template #cell-email="{ row }">
            <span class="font-mono text-xs text-slate-600">{{ row.email || row.username }}</span>
          </template>

          <template #cell-assigned_classes="{ row }">
            <div v-if="row.assigned_classes?.length" class="flex flex-wrap gap-1.5">
              <span
                v-for="cls in row.assigned_classes"
                :key="cls.id"
                class="px-2 py-0.5 rounded-lg text-xs font-bold"
                :class="cls.major === 'TMS' ? 'bg-amber-100 text-amber-800' : 'bg-indigo-100 text-indigo-800'"
              >
                {{ cls.name }} ({{ cls.major }})
              </span>
            </div>
            <span v-else class="text-xs text-rose-500 font-semibold">Belum ditugaskan ke kelas</span>
          </template>

          <template #cell-pin_status="{ row }">
            <span
              class="px-2.5 py-1 rounded-full text-xs font-bold"
              :class="row.has_pin ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
            >
              {{ row.has_pin ? '6-Digit Aktif' : 'Belum Diatur' }}
            </span>
          </template>

          <template #cell-assessments_count="{ row }">
            <span class="font-extrabold text-indigo-600">{{ row.assessments_count }}</span>
          </template>

          <template #cell-actions="{ row }">
            <div class="inline-flex items-center gap-1.5">
              <button
                type="button"
                class="p-2 rounded-xl bg-slate-100 hover:bg-indigo-100 text-slate-700 hover:text-indigo-700 transition"
                title="Edit & Atur Kelas Penguji"
                @click="openEditExaminer(row)"
              >
                <Pencil class="w-4 h-4" />
              </button>
              <button
                type="button"
                class="p-2 rounded-xl bg-slate-100 hover:bg-rose-100 text-slate-700 hover:text-rose-700 transition"
                @click="deleteExaminer(row)"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
          </template>
        </AdminDataTable>
      </section>

      <!-- TAB 5: PENGATURAN RUBRIK & BOBOT PER JURUSAN + TAMBAH HALAMAN MANUAL -->
      <section v-if="activeTab === 'rubrics'" class="space-y-5">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5 space-y-4 sticky top-20 z-20">
          <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
              <h2 class="text-lg font-extrabold text-slate-900">Pengaturan Rubrik &amp; Bobot per Jurusan</h2>
              <p class="text-xs text-slate-500">
                Kelompokkan rubrik untuk PPLG atau TMS, tambahkan halaman baru secara manual, dan pastikan total bobot = 100%.
              </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
              <div class="inline-flex p-1 rounded-2xl bg-slate-100 border border-slate-200">
                <button
                  v-for="m in ['PPLG', 'TMS']"
                  :key="m"
                  type="button"
                  class="px-4 py-2 rounded-xl text-xs font-extrabold transition"
                  :class="
                    activeRubricMajor === m
                      ? 'bg-indigo-600 text-white shadow-sm'
                      : 'text-slate-600 hover:bg-white'
                  "
                  @click="switchRubricMajor(m)"
                >
                  Jurusan {{ m }}
                </button>
              </div>

              <span
                class="px-4 py-2 rounded-2xl text-sm font-extrabold border"
                :class="
                  isWeightValid
                    ? 'bg-emerald-50 border-emerald-200 text-emerald-700'
                    : 'bg-rose-50 border-rose-200 text-rose-700'
                "
              >
                Total ({{ activeRubricMajor }}): {{ totalRubricWeight.toFixed(2) }}%
              </span>

              <button
                type="button"
                class="min-h-[44px] px-4 rounded-2xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs sm:text-sm flex items-center gap-1.5 transition"
                @click="addManualPage"
              >
                <Plus class="w-4 h-4" />
                <span>Tambah Halaman</span>
              </button>

              <button
                type="button"
                :disabled="!isWeightValid || savingRubrics"
                class="min-h-[44px] px-5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold text-sm flex items-center gap-2 transition"
                @click="saveAllRubrics"
              >
                <Loader2 v-if="savingRubrics" class="w-4 h-4 animate-spin" />
                <Save v-else class="w-4 h-4" />
                <span>{{ savingRubrics ? 'Menyimpan...' : `Simpan Rubrik ${activeRubricMajor}` }}</span>
              </button>
            </div>
          </div>
        </div>

        <div v-if="rubricPages.length === 0" class="bg-white rounded-3xl border border-slate-200 p-10 text-center">
          <p class="font-bold text-slate-700">Belum ada halaman rubrik untuk jurusan {{ activeRubricMajor }}.</p>
          <button
            type="button"
            class="mt-3 px-4 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-bold"
            @click="addManualPage"
          >
            + Tambah Halaman 1
          </button>
        </div>

        <div v-else class="space-y-6">
          <div
            v-for="pageGroup in rubricPages"
            :key="pageGroup.page_number"
            class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden"
          >
            <div class="px-5 py-4 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
              <div class="flex flex-wrap items-center gap-3 flex-1 min-w-[240px]">
                <span class="px-3 py-1.5 rounded-xl bg-indigo-600 text-white text-xs font-extrabold uppercase shrink-0">
                  Halaman {{ pageGroup.page_number }}
                </span>
                <input
                  :value="pageGroup.page_title"
                  type="text"
                  placeholder="Judul Halaman (contoh: Persiapan Kerja & K3)"
                  class="flex-1 min-w-[220px] px-3.5 py-2 rounded-xl border border-slate-300 bg-white text-sm font-bold text-slate-900"
                  @input="updatePageTitle(pageGroup.page_number, $event.target.value)"
                />
                <span class="text-xs font-bold text-slate-500 shrink-0">
                  Subtotal: {{ pageGroup.subtotal.toFixed(2) }}%
                </span>
              </div>

              <div class="flex items-center gap-2">
                <button
                  type="button"
                  class="px-3 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center gap-1"
                  @click="addItemToPage(pageGroup.page_number, pageGroup.page_title)"
                >
                  <Plus class="w-3.5 h-3.5" />
                  Tambah Butir
                </button>
                <button
                  v-if="rubricPages.length > 1"
                  type="button"
                  class="px-3 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold"
                  @click="removePage(pageGroup.page_number)"
                >
                  Hapus Halaman
                </button>
              </div>
            </div>

            <div class="p-5 space-y-4">
              <div
                v-for="(item, idx) in pageGroup.items"
                :key="item._uid"
                class="p-4 rounded-2xl bg-slate-50/70 border border-slate-200 space-y-3"
              >
                <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                  <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Pindah Hal.</label>
                    <AppSelect
                      v-model="item.page_number"
                      size="sm"
                      :options="rubricPageSelectOptions"
                    />
                  </div>

                  <div class="md:col-span-7">
                    <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">
                      Butir #{{ idx + 1 }} — Judul Aspek Penilaian
                    </label>
                    <input
                      v-model.trim="item.title"
                      type="text"
                      placeholder="Nama kriteria penilaian..."
                      class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white text-sm font-bold"
                    />
                  </div>

                  <div class="md:col-span-2">
                    <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">Bobot (%)</label>
                    <input
                      v-model.number="item.weight"
                      type="number"
                      min="0"
                      max="100"
                      step="0.5"
                      class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white text-sm font-black text-indigo-700"
                    />
                  </div>

                  <div class="md:col-span-1 flex justify-end">
                    <button
                      type="button"
                      class="p-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 transition"
                      title="Hapus Butir"
                      @click="removeRubricItem(item._uid)"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </div>
                </div>

                <div>
                  <label class="block text-[11px] font-bold uppercase text-slate-400 mb-1">
                    Indikator Penilaian (Pisahkan dengan koma)
                  </label>
                  <input
                    v-model="item.indicatorsText"
                    type="text"
                    placeholder="Indikator 1, Indikator 2, Indikator 3"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white text-sm text-slate-700"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- TAB 6: LAPORAN NILAI (PDF & WORD) PER SISWA / PER KELAS / JURUSAN -->
      <section v-if="activeTab === 'reports'">
        <AdminReportSection :classes="classes" />
      </section>
    </main>

    <!-- Modals -->
    <ClassFormModal
      :open="classModalOpen"
      :school-class="editingClass"
      @close="classModalOpen = false"
      @saved="onClassSaved"
    />

    <StudentFormModal
      :open="studentModalOpen"
      :student="editingStudent"
      :classes="classes"
      @close="studentModalOpen = false"
      @saved="onStudentSaved"
    />

    <StudentImportModal
      :open="importModalOpen"
      :classes="classes"
      @close="importModalOpen = false"
      @imported="onStudentsImported"
    />

    <ExaminerFormModal
      :open="examinerModalOpen"
      :examiner="editingExaminer"
      :classes="classes"
      @close="examinerModalOpen = false"
      @saved="onExaminerSaved"
    />

    <PrintQrCardsModal
      :open="printModalOpen"
      :students="students"
      @close="printModalOpen = false"
    />
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import {
  Activity,
  FileSpreadsheet,
  FileText,
  FolderKanban,
  ListChecks,
  Loader2,
  Pencil,
  Plus,
  Printer,
  RefreshCw,
  Save,
  Search,
  Star,
  Trash2,
  UserCog,
  Users,
} from 'lucide-vue-next'
import AppNavbar from '@/components/AppNavbar.vue'
import AppSelect from '@/components/AppSelect.vue'
import AdminDataTable from '@/components/admin/AdminDataTable.vue'
import AdminReportSection from '@/components/admin/AdminReportSection.vue'
import ClassFormModal from '@/components/admin/ClassFormModal.vue'
import StudentFormModal from '@/components/admin/StudentFormModal.vue'
import StudentImportModal from '@/components/admin/StudentImportModal.vue'
import ExaminerFormModal from '@/components/admin/ExaminerFormModal.vue'
import PrintQrCardsModal from '@/components/admin/PrintQrCardsModal.vue'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'
import { exportAssessmentExcel } from '@/utils/excelExport'
import { formatDateTime, formatScore } from '@/utils/formatters'

const toast = useToast()

const tabs = [
  { key: 'monitoring', label: 'Monitoring & Ekspor', icon: Activity },
  { key: 'reports', label: 'Laporan (PDF / Word)', icon: FileText },
  { key: 'classes', label: 'Kelas & Jurusan', icon: FolderKanban },
  { key: 'students', label: 'Siswa & Cetak Kartu', icon: Users },
  { key: 'examiners', label: 'Akun Penguji', icon: UserCog },
  { key: 'rubrics', label: 'Rubrik & Bobot', icon: ListChecks },
]

const activeTab = ref('monitoring')
const autoRefresh = ref(true)
let refreshInterval = null
let uidCounter = 1

const loadingDashboard = ref(false)
const dashboard = ref(null)
const classes = ref([])
const students = ref([])
const examiners = ref([])
const editableRubrics = ref([])
const activeRubricMajor = ref('PPLG')

const exportTarget = ref('all|PPLG')
const exporting = ref(false)
const recapData = ref(null)

const classModalOpen = ref(false)
const editingClass = ref(null)

const studentFilter = reactive({ class_id: 'all', search: '' })
const studentModalOpen = ref(false)
const editingStudent = ref(null)
const importModalOpen = ref(false)
const printModalOpen = ref(false)

const examinerModalOpen = ref(false)
const editingExaminer = ref(null)

const savingRubrics = ref(false)

// Options untuk Combobox AppSelect
const exportTargetOptions = computed(() => [
  {
    value: 'all|PPLG',
    label: 'Semua Kelas Jurusan PPLG',
    badge: 'PPLG',
    badgeClass: 'bg-indigo-100 text-indigo-800',
    sublabel: 'Rekapitulasi seluruh kelas PPLG',
  },
  {
    value: 'all|TMS',
    label: 'Semua Kelas Jurusan TMS',
    badge: 'TMS',
    badgeClass: 'bg-amber-100 text-amber-800',
    sublabel: 'Rekapitulasi seluruh kelas TMS',
  },
  ...classes.value.map((cls) => ({
    value: String(cls.id),
    label: cls.name,
    badge: cls.major,
    badgeClass: cls.major === 'TMS' ? 'bg-amber-100 text-amber-800' : 'bg-indigo-100 text-indigo-800',
    sublabel: `Tahun Ajaran ${cls.academic_year} • ${cls.students_count || 0} Siswa`,
  })),
])

const studentClassFilterOptions = computed(() => [
  {
    value: 'all',
    label: 'Semua Kelas',
    sublabel: 'Tampilkan siswa dari seluruh kelas',
  },
  ...classes.value.map((cls) => ({
    value: cls.id,
    label: cls.name,
    badge: cls.major,
    badgeClass: cls.major === 'TMS' ? 'bg-amber-100 text-amber-800' : 'bg-indigo-100 text-indigo-800',
    sublabel: `Tahun Ajaran ${cls.academic_year}`,
  })),
])

// Definisi Kolom Tabel dengan Filter di Setiap Judul Tabel
const examinerProgressColumns = [
  {
    key: 'name',
    label: 'Nama Penguji',
    accessor: (r) => `${r.name} ${r.username}`,
  },
  {
    key: 'assigned_classes',
    label: 'Kelas Tugas',
    accessor: (r) => (r.assigned_classes?.length ? r.assigned_classes.join(', ') : r.role === 'admin' ? 'Semua Kelas' : 'Belum diatur'),
  },
  {
    key: 'total_graded',
    label: 'Dinilai',
    align: 'center',
  },
  {
    key: 'komp_rem',
    label: 'Komp / Rem',
    align: 'center',
    accessor: (r) => `${r.kompeten_count}/${r.remedial_count}`,
  },
  {
    key: 'average_score',
    label: 'Rata2',
    align: 'right',
  },
]

const recapTableColumns = computed(() => {
  const dynamicExaminerCols = (recapData.value?.examiners || []).map((ex, idx) => {
    const typeLabel = ex.examiner_type === 'eksternal' ? 'Eksternal' : 'Internal'
    return {
      key: `examiner_${ex.id}`,
      label: `P${idx + 1}: ${ex.name} (${typeLabel})`,
      align: 'center',
      accessor: (row) => {
        const score = row.examiner_scores?.[ex.id]?.final_score
        return score !== null && score !== undefined ? Number(score).toFixed(2) : '-'
      },
    }
  })

  return [
    { key: 'nis', label: 'NIS' },
    { key: 'name', label: 'Nama Siswa' },
    { key: 'class_name', label: 'Kelas', filterType: 'select' },
    ...dynamicExaminerCols,
    {
      key: 'sum_score',
      label: 'Jumlah Nilai',
      align: 'center',
      accessor: (r) => (r.sum_score !== null && r.sum_score !== undefined ? Number(r.sum_score).toFixed(2) : '-'),
    },
    {
      key: 'graded_examiner_count',
      label: 'Penguji Menilai',
      align: 'center',
      accessor: (r) => `${r.graded_examiner_count}/${r.assigned_examiner_count}`,
    },
    {
      key: 'total_hasil',
      label: 'total_hasil',
      align: 'center',
      headerClass: 'bg-indigo-50/70 text-indigo-900',
      cellClass: 'bg-indigo-50/30',
      accessor: (r) => (r.total_hasil !== null && r.total_hasil !== undefined ? Number(r.total_hasil).toFixed(2) : '-'),
    },
    {
      key: 'status',
      label: 'Status Akhir',
      align: 'center',
      filterType: 'select',
      filterOptions: [
        { label: 'Kompeten', value: 'kompeten', dotClass: 'bg-emerald-500' },
        { label: 'Remedial', value: 'remedial', dotClass: 'bg-rose-500' },
        { label: 'Menunggu', value: 'pending', dotClass: 'bg-slate-400' },
      ],
    },
  ]
})

const recentAssessmentsColumns = [
  {
    key: 'student_name',
    label: 'Siswa / NIS',
    accessor: (r) => `${r.student_name} ${r.nis}`,
  },
  {
    key: 'class_name',
    label: 'Kelas',
    filterType: 'select',
  },
  {
    key: 'examiner_name',
    label: 'Penguji',
  },
  {
    key: 'final_score',
    label: 'Nilai',
    align: 'center',
  },
  {
    key: 'status',
    label: 'Status',
    align: 'center',
    filterType: 'select',
    filterOptions: [
      { label: 'Kompeten', value: 'kompeten', dotClass: 'bg-emerald-500' },
      { label: 'Remedial', value: 'remedial', dotClass: 'bg-rose-500' },
    ],
  },
  {
    key: 'submitted_at',
    label: 'Waktu',
    align: 'right',
    accessor: (r) => formatDateTime(r.submitted_at),
  },
]

const classesTableColumns = [
  { key: 'name', label: 'Nama Kelas' },
  {
    key: 'major',
    label: 'Jurusan',
    filterType: 'select',
    filterOptions: [
      { label: 'PPLG', value: 'PPLG', badge: 'PPLG', badgeClass: 'bg-indigo-100 text-indigo-800' },
      { label: 'TMS', value: 'TMS', badge: 'TMS', badgeClass: 'bg-amber-100 text-amber-800' },
    ],
  },
  { key: 'academic_year', label: 'Tahun Ajaran', filterType: 'select' },
  { key: 'students_count', label: 'Jumlah Siswa', align: 'center' },
  {
    key: 'examiners',
    label: 'Penguji Ditugaskan',
    accessor: (r) => (r.examiners?.length ? r.examiners.map((e) => e.name).join(', ') : 'Belum ada penguji'),
  },
  { key: 'actions', label: 'Aksi', align: 'right', sortable: false, filterable: false },
]

const studentsTableColumns = [
  { key: 'nis', label: 'NIS' },
  { key: 'name', label: 'Nama Siswa' },
  { key: 'class_name', label: 'Kelas', filterType: 'select' },
  {
    key: 'major',
    label: 'Jurusan',
    filterType: 'select',
    filterOptions: [
      { label: 'PPLG', value: 'PPLG', badge: 'PPLG', badgeClass: 'bg-indigo-100 text-indigo-800' },
      { label: 'TMS', value: 'TMS', badge: 'TMS', badgeClass: 'bg-amber-100 text-amber-800' },
    ],
  },
  {
    key: 'priority',
    label: 'Prioritas',
    filterType: 'select',
    accessor: (r) => (r.is_priority ? 'Prioritas' : 'Reguler'),
    filterOptions: [
      { label: 'Prioritas', value: 'Prioritas', dotClass: 'bg-amber-500' },
      { label: 'Reguler', value: 'Reguler', dotClass: 'bg-slate-400' },
    ],
  },
  {
    key: 'assessment_status',
    label: 'Status Ujikom',
    align: 'center',
    filterType: 'select',
    filterOptions: [
      { label: 'Kompeten', value: 'kompeten', dotClass: 'bg-emerald-500' },
      { label: 'Remedial', value: 'remedial', dotClass: 'bg-rose-500' },
      { label: 'Menunggu', value: 'pending', dotClass: 'bg-slate-400' },
    ],
  },
  {
    key: 'final_score',
    label: 'Nilai (total_hasil)',
    align: 'center',
    accessor: (r) => (r.final_score !== null && r.final_score !== undefined ? Number(r.final_score).toFixed(2) : '-'),
  },
  { key: 'actions', label: 'Aksi', align: 'right', sortable: false, filterable: false },
]

const examinersTableColumns = [
  { key: 'name', label: 'Nama Penguji' },
  {
    key: 'examiner_type',
    label: 'Jenis Penguji',
    align: 'center',
    accessor: (r) => (r.examiner_type === 'eksternal' ? 'Eksternal' : 'Internal'),
  },
  { key: 'email', label: 'Email / Username', accessor: (r) => r.email || r.username },
  {
    key: 'assigned_classes',
    label: 'Kelas yang Diuji',
    accessor: (r) =>
      r.assigned_classes?.length
        ? r.assigned_classes.map((c) => `${c.name} (${c.major})`).join(', ')
        : 'Belum ditugaskan',
  },
  {
    key: 'pin_status',
    label: 'PIN Revisi',
    align: 'center',
    filterType: 'select',
    accessor: (r) => (r.has_pin ? 'Aktif' : 'Belum Diatur'),
    filterOptions: [
      { label: '6-Digit Aktif', value: 'Aktif', dotClass: 'bg-emerald-500' },
      { label: 'Belum Diatur', value: 'Belum Diatur', dotClass: 'bg-rose-500' },
    ],
  },
  { key: 'assessments_count', label: 'Siswa Dinilai', align: 'center' },
  { key: 'actions', label: 'Aksi', align: 'right', sortable: false, filterable: false },
]

const totalRubricWeight = computed(() =>
  editableRubrics.value.reduce((sum, r) => sum + (Number(r.weight) || 0), 0)
)
const isWeightValid = computed(() => Math.abs(totalRubricWeight.value - 100) < 0.01)

const rubricPages = computed(() => {
  const map = new Map()
  const sorted = [...editableRubrics.value].sort((a, b) => a.page_number - b.page_number)

  sorted.forEach((item) => {
    const num = Number(item.page_number) || 1
    if (!map.has(num)) {
      map.set(num, {
        page_number: num,
        page_title: item.page_title || `Halaman ${num}`,
        items: [],
        subtotal: 0,
      })
    }
    const group = map.get(num)
    group.items.push(item)
    group.subtotal += Number(item.weight) || 0
  })

  return Array.from(map.values())
})

const rubricPageSelectOptions = computed(() => {
  const maxPage = Math.max(
    3,
    editableRubrics.value.reduce((m, r) => Math.max(m, Number(r.page_number) || 1), 1) + 1
  )
  const opts = []
  for (let i = 1; i <= maxPage; i++) {
    opts.push({ label: `Halaman ${i}`, value: i })
  }
  return opts
})

async function loadDashboard() {
  loadingDashboard.value = true
  try {
    const { data } = await api.get('/admin/dashboard')
    dashboard.value = data
  } catch {
    toast.error('Gagal memuat statistik dashboard.')
  } finally {
    loadingDashboard.value = false
  }
}

async function loadClasses() {
  const { data } = await api.get('/admin/classes')
  classes.value = data.classes
}

async function loadStudents() {
  const params = {}
  if (studentFilter.class_id !== 'all') params.class_id = studentFilter.class_id
  if (studentFilter.search) params.search = studentFilter.search

  const { data } = await api.get('/admin/students', { params })
  students.value = data.students
}

async function loadExaminers() {
  const { data } = await api.get('/admin/examiners')
  examiners.value = data.examiners
}

async function loadRubrics() {
  const { data } = await api.get('/admin/rubrics', {
    params: { major: activeRubricMajor.value },
  })
  editableRubrics.value = data.rubrics.map((r) => ({
    ...r,
    _uid: uidCounter++,
    page_title: r.page_title || `Halaman ${r.page_number}`,
    indicatorsText: Array.isArray(r.indicators) ? r.indicators.join(', ') : '',
  }))
}

async function switchRubricMajor(major) {
  activeRubricMajor.value = major
  await loadRubrics()
}

async function loadRecapData() {
  try {
    let url = ''
    if (exportTarget.value.startsWith('all|')) {
      const major = exportTarget.value.split('|')[1]
      url = `/admin/export/all?major=${encodeURIComponent(major)}`
    } else {
      url = `/admin/export/${exportTarget.value}`
    }
    const { data } = await api.get(url)
    recapData.value = data
    return data
  } catch {
    return null
  }
}

async function refreshAll() {
  await Promise.all([
    loadDashboard(),
    loadClasses(),
    loadStudents(),
    loadExaminers(),
    loadRubrics(),
    loadRecapData(),
  ])
}

function openAddClass() {
  editingClass.value = null
  classModalOpen.value = true
}

function openEditClass(cls) {
  editingClass.value = cls
  classModalOpen.value = true
}

async function onClassSaved() {
  classModalOpen.value = false
  await Promise.all([loadClasses(), loadDashboard(), loadExaminers(), loadRecapData()])
}

async function deleteClass(cls) {
  if (!window.confirm(`Hapus kelas ${cls.name} (${cls.major})?`)) return
  try {
    await api.delete(`/admin/classes/${cls.id}`)
    toast.success('Kelas berhasil dihapus.')
    await Promise.all([loadClasses(), loadDashboard(), loadExaminers(), loadRecapData()])
  } catch (err) {
    toast.error(err.response?.data?.message || 'Gagal menghapus kelas.')
  }
}

async function handleExportExcel() {
  exporting.value = true
  try {
    const data = await loadRecapData()
    if (!data) throw new Error('Data kosong')
    exportAssessmentExcel(data)
    toast.success('File Excel multi-sheet (termasuk sheet akhir total_hasil) berhasil diunduh.')
  } catch {
    toast.error('Gagal mengekspor data ke Excel.')
  } finally {
    exporting.value = false
  }
}

function openAddStudent() {
  editingStudent.value = null
  studentModalOpen.value = true
}

function openEditStudent(student) {
  editingStudent.value = student
  studentModalOpen.value = true
}

async function onStudentSaved() {
  studentModalOpen.value = false
  await Promise.all([loadStudents(), loadDashboard(), loadClasses(), loadRecapData()])
}

async function onStudentsImported() {
  importModalOpen.value = false
  await Promise.all([loadStudents(), loadDashboard(), loadClasses(), loadRecapData()])
}

async function deleteStudent(student) {
  if (!window.confirm(`Hapus siswa ${student.name} (${student.nis})?`)) return
  try {
    await api.delete(`/admin/students/${student.id}`)
    toast.success('Siswa berhasil dihapus.')
    await Promise.all([loadStudents(), loadDashboard(), loadClasses(), loadRecapData()])
  } catch (err) {
    toast.error(err.response?.data?.message || 'Gagal menghapus siswa.')
  }
}

function openAddExaminer() {
  editingExaminer.value = null
  examinerModalOpen.value = true
}

function openEditExaminer(examiner) {
  editingExaminer.value = examiner
  examinerModalOpen.value = true
}

async function onExaminerSaved() {
  examinerModalOpen.value = false
  await Promise.all([loadExaminers(), loadDashboard(), loadClasses(), loadRecapData()])
}

async function deleteExaminer(examiner) {
  if (!window.confirm(`Hapus akun penguji ${examiner.name}?`)) return
  try {
    await api.delete(`/admin/examiners/${examiner.id}`)
    toast.success('Akun penguji berhasil dihapus.')
    await Promise.all([loadExaminers(), loadDashboard(), loadClasses(), loadRecapData()])
  } catch (err) {
    toast.error(err.response?.data?.message || 'Gagal menghapus akun penguji.')
  }
}

function addManualPage() {
  const maxPage = editableRubrics.value.reduce((m, r) => Math.max(m, Number(r.page_number) || 0), 0)
  const nextPage = maxPage + 1
  const defaultTitle = `Halaman ${nextPage} (${activeRubricMajor.value})`
  addItemToPage(nextPage, defaultTitle)
  toast.info(`Halaman ${nextPage} ditambahkan. Sesuaikan judul, butir, dan bobot hingga total 100%.`)
}

function addItemToPage(pageNumber, pageTitle) {
  editableRubrics.value.push({
    _uid: uidCounter++,
    id: null,
    major: activeRubricMajor.value,
    page_number: pageNumber,
    page_title: pageTitle || `Halaman ${pageNumber}`,
    title: '',
    weight: 0,
    indicatorsText: 'Indikator 1, Indikator 2',
  })
}

function updatePageTitle(pageNumber, newTitle) {
  editableRubrics.value.forEach((item) => {
    if (Number(item.page_number) === Number(pageNumber)) {
      item.page_title = newTitle
    }
  })
}

function removePage(pageNumber) {
  if (!window.confirm(`Hapus Halaman ${pageNumber} beserta seluruh butirnya?`)) return
  editableRubrics.value = editableRubrics.value.filter(
    (item) => Number(item.page_number) !== Number(pageNumber)
  )
}

function removeRubricItem(uid) {
  if (editableRubrics.value.length <= 1) {
    toast.error('Minimal harus terdapat 1 butir rubrik.')
    return
  }
  editableRubrics.value = editableRubrics.value.filter((item) => item._uid !== uid)
}

async function saveAllRubrics() {
  if (!isWeightValid.value) {
    toast.error(`Total bobot rubrik ${activeRubricMajor.value} harus tepat 100%.`)
    return
  }

  for (const r of editableRubrics.value) {
    if (!r.title?.trim()) {
      toast.error('Semua judul aspek penilaian wajib diisi.')
      return
    }
    if (!r.page_title?.trim()) {
      toast.error(`Judul Halaman ${r.page_number} wajib diisi.`)
      return
    }
  }

  savingRubrics.value = true
  try {
    const payload = {
      major: activeRubricMajor.value,
      rubrics: editableRubrics.value.map((r) => ({
        id: r.id || null,
        page_number: Number(r.page_number),
        page_title: r.page_title.trim(),
        title: r.title.trim(),
        weight: Number(r.weight),
        indicators: r.indicatorsText
          .split(',')
          .map((s) => s.trim())
          .filter(Boolean),
      })),
    }

    await api.put('/admin/rubrics/bulk', payload)
    toast.success(`Rubrik & bobot jurusan ${activeRubricMajor.value} berhasil disimpan.`)
    await loadRubrics()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Gagal menyimpan rubrik.')
  } finally {
    savingRubrics.value = false
  }
}

function setupPolling() {
  if (refreshInterval) {
    clearInterval(refreshInterval)
    refreshInterval = null
  }
  if (autoRefresh.value) {
    refreshInterval = setInterval(() => {
      if (activeTab.value === 'monitoring') {
        loadDashboard()
        loadRecapData()
      }
    }, 15000)
  }
}

watch(autoRefresh, setupPolling)

onMounted(async () => {
  await refreshAll()
  setupPolling()
})

onBeforeUnmount(() => {
  if (refreshInterval) clearInterval(refreshInterval)
})
</script>
