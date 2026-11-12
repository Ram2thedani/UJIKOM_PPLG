<template>
  <div class="no-print min-h-screen bg-slate-50 pb-12">
    <AppNavbar subtitle="Panel Administrator Ujikom RPL/PPLG" />

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
        <!-- Export Bar -->
        <div class="bg-gradient-to-r from-indigo-600 to-blue-600 rounded-3xl p-5 sm:p-6 text-white flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-lg shadow-indigo-600/15">
          <div>
            <h2 class="text-xl font-extrabold">Monitoring & Rekapitulasi Nilai Ujikom</h2>
            <p class="text-sm text-indigo-100 mt-0.5">
              Pantau progres kelas secara real-time dan ekspor rekap lengkap beserta rincian 9 butir rubrik.
            </p>
          </div>
          <div class="flex flex-wrap items-center gap-2.5">
            <select
              v-model="exportClassId"
              class="min-h-[46px] px-3.5 rounded-xl bg-white/15 border border-white/25 text-white text-sm font-semibold outline-none"
            >
              <option value="all" class="text-slate-900">Semua Kelas</option>
              <option v-for="cls in classes" :key="cls.id" :value="cls.id" class="text-slate-900">
                {{ cls.name }}
              </option>
            </select>
            <button
              type="button"
              :disabled="exporting"
              class="min-h-[46px] px-5 rounded-xl bg-emerald-500 hover:bg-emerald-400 disabled:opacity-60 text-white font-extrabold text-sm shadow-md flex items-center gap-2 transition active:scale-95"
              @click="handleExportExcel"
            >
              <Loader2 v-if="exporting" class="w-4 h-4 animate-spin" />
              <FileSpreadsheet v-else class="w-4 h-4" />
              <span>{{ exporting ? 'Menyiapkan...' : 'Ekspor Excel (.xlsx)' }}</span>
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

        <!-- Progres Kelas & Penguji -->
        <div v-if="dashboard" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Progres Kelas -->
          <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-extrabold text-slate-900 text-base">Progres Penilaian per Kelas</h3>
              <button
                type="button"
                class="text-xs font-bold text-indigo-600 hover:underline"
                @click="showAddClassForm = !showAddClassForm"
              >
                + Tambah Kelas
              </button>
            </div>

            <form
              v-if="showAddClassForm"
              class="mb-4 p-3.5 rounded-2xl bg-slate-50 border border-slate-200 flex flex-wrap gap-2"
              @submit.prevent="createClass"
            >
              <input
                v-model.trim="newClass.name"
                type="text"
                required
                placeholder="Nama Kelas (cth: XII PPLG 2)"
                class="flex-1 min-w-[140px] px-3 py-2 rounded-xl border border-slate-300 text-sm"
              />
              <input
                v-model.trim="newClass.academic_year"
                type="text"
                required
                placeholder="2026/2027"
                class="w-28 px-3 py-2 rounded-xl border border-slate-300 text-sm"
              />
              <button
                type="submit"
                class="px-4 py-2 rounded-xl bg-indigo-600 text-white text-xs font-bold"
              >
                Simpan
              </button>
            </form>

            <div class="space-y-4">
              <div
                v-for="cls in dashboard.class_progress"
                :key="cls.id"
                class="p-4 rounded-2xl bg-slate-50 border border-slate-100"
              >
                <div class="flex items-center justify-between mb-2">
                  <div>
                    <p class="font-extrabold text-slate-900 text-sm">{{ cls.name }}</p>
                    <p class="text-xs text-slate-500">{{ cls.academic_year }} • {{ cls.total }} siswa</p>
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

          <!-- Progres Penguji -->
          <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
            <h3 class="font-extrabold text-slate-900 text-base mb-4">Progres & Aktivitas Penguji</h3>
            <div class="overflow-x-auto">
              <table class="w-full text-sm text-left">
                <thead class="text-xs uppercase text-slate-400 border-b border-slate-200">
                  <tr>
                    <th class="pb-2.5">Nama Penguji</th>
                    <th class="pb-2.5 text-center">Dinilai</th>
                    <th class="pb-2.5 text-center">Komp / Rem</th>
                    <th class="pb-2.5 text-right">Rata2 Skor</th>
                    <th class="pb-2.5 text-right">Durasi</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="ex in dashboard.examiner_progress" :key="ex.id">
                    <td class="py-3">
                      <p class="font-bold text-slate-800">{{ ex.name }}</p>
                      <p class="text-xs text-slate-400">{{ ex.username }}</p>
                    </td>
                    <td class="py-3 text-center font-extrabold text-indigo-600">{{ ex.total_graded }}</td>
                    <td class="py-3 text-center text-xs font-bold">
                      <span class="text-emerald-600">{{ ex.kompeten_count }}</span> /
                      <span class="text-rose-600">{{ ex.remedial_count }}</span>
                    </td>
                    <td class="py-3 text-right font-bold">{{ formatScore(ex.average_score) }}</td>
                    <td class="py-3 text-right font-mono text-xs text-slate-500">
                      {{ formatDuration(ex.average_duration_seconds) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Penilaian Terbaru -->
        <div v-if="dashboard" class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
          <h3 class="font-extrabold text-slate-900 text-base mb-4">10 Penilaian Terbaru</h3>
          <div v-if="dashboard.recent_assessments.length === 0" class="text-sm text-slate-500 py-4 text-center">
            Belum ada penilaian yang disubmit.
          </div>
          <div v-else class="overflow-x-auto">
            <table class="w-full text-sm text-left">
              <thead class="text-xs uppercase text-slate-400 border-b border-slate-200">
                <tr>
                  <th class="pb-2.5">Siswa</th>
                  <th class="pb-2.5">Kelas</th>
                  <th class="pb-2.5">Penguji</th>
                  <th class="pb-2.5 text-center">Nilai</th>
                  <th class="pb-2.5 text-center">Status</th>
                  <th class="pb-2.5 text-right">Waktu</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="item in dashboard.recent_assessments" :key="item.id">
                  <td class="py-3">
                    <p class="font-bold text-slate-800">{{ item.student_name }}</p>
                    <p class="text-xs font-mono text-slate-400">{{ item.nis }}</p>
                  </td>
                  <td class="py-3 text-slate-600">{{ item.class_name }}</td>
                  <td class="py-3 text-slate-600">{{ item.examiner_name }}</td>
                  <td class="py-3 text-center font-black">{{ formatScore(item.final_score) }}</td>
                  <td class="py-3 text-center">
                    <span
                      class="px-2.5 py-1 rounded-full text-xs font-extrabold uppercase"
                      :class="
                        item.status === 'kompeten'
                          ? 'bg-emerald-100 text-emerald-800'
                          : 'bg-rose-100 text-rose-800'
                      "
                    >
                      {{ item.status }}
                    </span>
                  </td>
                  <td class="py-3 text-right text-xs text-slate-500">{{ formatDateTime(item.submitted_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- TAB 2: MANAJEMEN SISWA & CETAK QR MASSAL -->
      <section v-if="activeTab === 'students'" class="space-y-5">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
          <div class="flex flex-wrap items-center gap-3 flex-1">
            <select
              v-model="studentFilter.class_id"
              class="min-h-[44px] px-3.5 rounded-xl border border-slate-300 bg-slate-50 text-sm font-semibold"
              @change="loadStudents"
            >
              <option value="all">Semua Kelas</option>
              <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
            </select>

            <div class="relative flex-1 min-w-[200px]">
              <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                v-model.trim="studentFilter.search"
                type="search"
                placeholder="Cari nama atau NIS siswa..."
                class="w-full min-h-[44px] pl-10 pr-4 rounded-xl border border-slate-300 text-sm outline-none focus:border-indigo-500"
                @input="loadStudents"
              />
            </div>
          </div>

          <div class="flex flex-wrap gap-2.5">
            <button
              type="button"
              class="min-h-[44px] px-4 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-sm font-bold flex items-center gap-2 transition"
              @click="printModalOpen = true"
            >
              <Printer class="w-4 h-4" />
              <span>Cetak QR Massal ({{ students.length }})</span>
            </button>

            <button
              type="button"
              class="min-h-[44px] px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold flex items-center gap-2 transition"
              @click="importModalOpen = true"
            >
              <FileSpreadsheet class="w-4 h-4" />
              <span>Impor Excel/CSV</span>
            </button>

            <button
              type="button"
              class="min-h-[44px] px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold flex items-center gap-2 transition"
              @click="openAddStudent"
            >
              <Plus class="w-4 h-4" />
              <span>Tambah Siswa</span>
            </button>
          </div>
        </div>

        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
              <thead class="bg-slate-50 text-xs uppercase text-slate-500 border-b border-slate-200">
                <tr>
                  <th class="px-5 py-3.5">NIS</th>
                  <th class="px-5 py-3.5">Nama Siswa</th>
                  <th class="px-5 py-3.5">Kelas</th>
                  <th class="px-5 py-3.5">Prioritas</th>
                  <th class="px-5 py-3.5 text-center">Status Ujikom</th>
                  <th class="px-5 py-3.5 text-center">Nilai</th>
                  <th class="px-5 py-3.5 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="s in students" :key="s.id" class="hover:bg-slate-50">
                  <td class="px-5 py-3.5 font-mono font-bold text-slate-700">{{ s.nis }}</td>
                  <td class="px-5 py-3.5">
                    <p class="font-bold text-slate-900">{{ s.name }}</p>
                    <p class="font-mono text-[11px] text-slate-400">QR: {{ s.qr_code_token.slice(0, 14) }}...</p>
                  </td>
                  <td class="px-5 py-3.5 text-slate-600">{{ s.class_name }}</td>
                  <td class="px-5 py-3.5">
                    <span
                      v-if="s.is_priority"
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-900 text-xs font-bold"
                    >
                      <Star class="w-3 h-3 fill-amber-500 text-amber-600" />
                      {{ s.priority_note || 'Prioritas' }}
                    </span>
                    <span v-else class="text-xs text-slate-400">Reguler</span>
                  </td>
                  <td class="px-5 py-3.5 text-center">
                    <span
                      class="px-2.5 py-1 rounded-full text-xs font-extrabold uppercase"
                      :class="{
                        'bg-emerald-100 text-emerald-800': s.assessment_status === 'kompeten',
                        'bg-rose-100 text-rose-800': s.assessment_status === 'remedial',
                        'bg-slate-100 text-slate-600': s.assessment_status === 'pending',
                      }"
                    >
                      {{ s.assessment_status === 'pending' ? 'Menunggu' : s.assessment_status }}
                    </span>
                  </td>
                  <td class="px-5 py-3.5 text-center font-black">
                    {{ formatScore(s.final_score) }}
                  </td>
                  <td class="px-5 py-3.5 text-right">
                    <div class="inline-flex items-center gap-1.5">
                      <button
                        type="button"
                        class="p-2 rounded-xl bg-slate-100 hover:bg-indigo-100 text-slate-700 hover:text-indigo-700 transition"
                        title="Edit Siswa"
                        @click="openEditStudent(s)"
                      >
                        <Pencil class="w-4 h-4" />
                      </button>
                      <button
                        type="button"
                        class="p-2 rounded-xl bg-slate-100 hover:bg-rose-100 text-slate-700 hover:text-rose-700 transition"
                        title="Hapus Siswa"
                        @click="deleteStudent(s)"
                      >
                        <Trash2 class="w-4 h-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- TAB 3: MANAJEMEN AKUN PENGUJI -->
      <section v-if="activeTab === 'examiners'" class="space-y-5">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5 flex items-center justify-between">
          <div>
            <h2 class="text-lg font-extrabold text-slate-900">Daftar Akun Penguji Ujikom</h2>
            <p class="text-xs text-slate-500">Kelola akun login penguji beserta 6-digit PIN otorisasi revisi nilai.</p>
          </div>
          <button
            type="button"
            class="min-h-[44px] px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold flex items-center gap-2 transition"
            @click="openAddExaminer"
          >
            <Plus class="w-4 h-4" />
            Tambah Penguji
          </button>
        </div>

        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
              <thead class="bg-slate-50 text-xs uppercase text-slate-500 border-b border-slate-200">
                <tr>
                  <th class="px-5 py-3.5">Nama Penguji</th>
                  <th class="px-5 py-3.5">Email / Username</th>
                  <th class="px-5 py-3.5 text-center">PIN Revisi</th>
                  <th class="px-5 py-3.5 text-center">Siswa Dinilai</th>
                  <th class="px-5 py-3.5 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="ex in examiners" :key="ex.id" class="hover:bg-slate-50">
                  <td class="px-5 py-3.5 font-bold text-slate-900">{{ ex.name }}</td>
                  <td class="px-5 py-3.5 font-mono text-xs text-slate-600">{{ ex.email || ex.username }}</td>
                  <td class="px-5 py-3.5 text-center">
                    <span
                      class="px-2.5 py-1 rounded-full text-xs font-bold"
                      :class="ex.has_pin ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                    >
                      {{ ex.has_pin ? '6-Digit Aktif' : 'Belum Diatur' }}
                    </span>
                  </td>
                  <td class="px-5 py-3.5 text-center font-extrabold text-indigo-600">{{ ex.assessments_count }}</td>
                  <td class="px-5 py-3.5 text-right">
                    <div class="inline-flex items-center gap-1.5">
                      <button
                        type="button"
                        class="p-2 rounded-xl bg-slate-100 hover:bg-indigo-100 text-slate-700 hover:text-indigo-700 transition"
                        @click="openEditExaminer(ex)"
                      >
                        <Pencil class="w-4 h-4" />
                      </button>
                      <button
                        type="button"
                        class="p-2 rounded-xl bg-slate-100 hover:bg-rose-100 text-slate-700 hover:text-rose-700 transition"
                        @click="deleteExaminer(ex)"
                      >
                        <Trash2 class="w-4 h-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- TAB 4: PENGATURAN BOBOT & INDIKATOR RUBRIK -->
      <section v-if="activeTab === 'rubrics'" class="space-y-5">
        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5 flex flex-wrap items-center justify-between gap-4 sticky top-20 z-20">
          <div>
            <h2 class="text-lg font-extrabold text-slate-900">Pengaturan Rubrik & Bobot Penilaian</h2>
            <p class="text-xs text-slate-500">Pastikan total bobot keseluruhan dari seluruh butir rubrik tepat 100%.</p>
          </div>

          <div class="flex items-center gap-3">
            <span
              class="px-4 py-2 rounded-2xl text-sm font-extrabold border"
              :class="
                isWeightValid
                  ? 'bg-emerald-50 border-emerald-200 text-emerald-700'
                  : 'bg-rose-50 border-rose-200 text-rose-700'
              "
            >
              Total Bobot: {{ totalRubricWeight.toFixed(2) }}%
            </span>

            <button
              type="button"
              :disabled="!isWeightValid || savingRubrics"
              class="min-h-[46px] px-5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold text-sm flex items-center gap-2 transition"
              @click="saveAllRubrics"
            >
              <Loader2 v-if="savingRubrics" class="w-4 h-4 animate-spin" />
              <Save v-else class="w-4 h-4" />
              <span>{{ savingRubrics ? 'Menyimpan...' : 'Simpan Rubrik' }}</span>
            </button>
          </div>
        </div>

        <div class="space-y-4">
          <div
            v-for="item in editableRubrics"
            :key="item.id"
            class="bg-white rounded-3xl border border-slate-200 shadow-sm p-5 space-y-4"
          >
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
              <div class="md:col-span-2">
                <label class="block text-xs font-bold uppercase text-slate-400 mb-1">Halaman</label>
                <select
                  v-model.number="item.page_number"
                  class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-sm font-bold bg-white"
                >
                  <option :value="1">Hal 1 (DB & Desain)</option>
                  <option :value="2">Hal 2 (Koding)</option>
                  <option :value="3">Hal 3 (Testing & Docs)</option>
                </select>
              </div>

              <div class="md:col-span-8">
                <label class="block text-xs font-bold uppercase text-slate-400 mb-1">
                  Butir {{ item.item_order }} — Judul Aspek Penilaian
                </label>
                <input
                  v-model.trim="item.title"
                  type="text"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-bold"
                />
              </div>

              <div class="md:col-span-2">
                <label class="block text-xs font-bold uppercase text-slate-400 mb-1">Bobot (%)</label>
                <input
                  v-model.number="item.weight"
                  type="number"
                  min="0"
                  max="100"
                  step="0.5"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm font-black text-indigo-700"
                />
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold uppercase text-slate-400 mb-1">
                Indikator Penilaian (Pisahkan dengan koma)
              </label>
              <input
                v-model="item.indicatorsText"
                type="text"
                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-sm text-slate-700"
              />
            </div>
          </div>
        </div>
      </section>
    </main>

    <!-- Modals -->
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
import StudentFormModal from '@/components/admin/StudentFormModal.vue'
import StudentImportModal from '@/components/admin/StudentImportModal.vue'
import ExaminerFormModal from '@/components/admin/ExaminerFormModal.vue'
import PrintQrCardsModal from '@/components/admin/PrintQrCardsModal.vue'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'
import { exportAssessmentExcel } from '@/utils/excelExport'
import { formatDateTime, formatDuration, formatScore } from '@/utils/formatters'

const toast = useToast()

const tabs = [
  { key: 'monitoring', label: 'Monitoring & Ekspor', icon: Activity },
  { key: 'students', label: 'Siswa & Cetak QR', icon: Users },
  { key: 'examiners', label: 'Akun Penguji', icon: UserCog },
  { key: 'rubrics', label: 'Rubrik & Bobot', icon: ListChecks },
]

const activeTab = ref('monitoring')
const autoRefresh = ref(true)
let refreshInterval = null

const loadingDashboard = ref(false)
const dashboard = ref(null)
const classes = ref([])
const students = ref([])
const examiners = ref([])
const editableRubrics = ref([])

const exportClassId = ref('all')
const exporting = ref(false)

const showAddClassForm = ref(false)
const newClass = reactive({ name: '', academic_year: '2026/2027' })

const studentFilter = reactive({ class_id: 'all', search: '' })
const studentModalOpen = ref(false)
const editingStudent = ref(null)
const importModalOpen = ref(false)
const printModalOpen = ref(false)

const examinerModalOpen = ref(false)
const editingExaminer = ref(null)

const savingRubrics = ref(false)

const totalRubricWeight = computed(() =>
  editableRubrics.value.reduce((sum, r) => sum + (Number(r.weight) || 0), 0)
)
const isWeightValid = computed(() => Math.abs(totalRubricWeight.value - 100) < 0.01)

async function loadDashboard() {
  loadingDashboard.value = true
  try {
    const { data } = await api.get('/admin/dashboard')
    dashboard.value = data
  } catch (err) {
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
  const { data } = await api.get('/admin/rubrics')
  editableRubrics.value = data.rubrics.map((r) => ({
    ...r,
    indicatorsText: Array.isArray(r.indicators) ? r.indicators.join(', ') : '',
  }))
}

async function refreshAll() {
  await Promise.all([loadDashboard(), loadClasses(), loadStudents(), loadExaminers(), loadRubrics()])
}

async function createClass() {
  try {
    await api.post('/admin/classes', newClass)
    toast.success('Kelas baru berhasil ditambahkan.')
    newClass.name = ''
    showAddClassForm.value = false
    await Promise.all([loadClasses(), loadDashboard()])
  } catch (err) {
    toast.error(err.response?.data?.message || 'Gagal menambah kelas.')
  }
}

async function handleExportExcel() {
  exporting.value = true
  try {
    const { data } = await api.get(`/admin/export/${exportClassId.value}`)
    exportAssessmentExcel(data)
    toast.success('File Excel rekap nilai berhasil diunduh.')
  } catch (err) {
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
  await Promise.all([loadStudents(), loadDashboard()])
}

async function onStudentsImported() {
  importModalOpen.value = false
  await Promise.all([loadStudents(), loadDashboard(), loadClasses()])
}

async function deleteStudent(student) {
  if (!window.confirm(`Hapus siswa ${student.name} (${student.nis})?`)) return
  try {
    await api.delete(`/admin/students/${student.id}`)
    toast.success('Siswa berhasil dihapus.')
    await Promise.all([loadStudents(), loadDashboard()])
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
  await Promise.all([loadExaminers(), loadDashboard()])
}

async function deleteExaminer(examiner) {
  if (!window.confirm(`Hapus akun penguji ${examiner.name}?`)) return
  try {
    await api.delete(`/admin/examiners/${examiner.id}`)
    toast.success('Akun penguji berhasil dihapus.')
    await Promise.all([loadExaminers(), loadDashboard()])
  } catch (err) {
    toast.error(err.response?.data?.message || 'Gagal menghapus akun penguji.')
  }
}

async function saveAllRubrics() {
  if (!isWeightValid.value) {
    toast.error('Total bobot seluruh butir rubrik harus tepat 100%.')
    return
  }

  savingRubrics.value = true
  try {
    const payload = {
      rubrics: editableRubrics.value.map((r) => ({
        id: r.id,
        page_number: r.page_number,
        item_order: r.item_order,
        title: r.title,
        weight: Number(r.weight),
        indicators: r.indicatorsText
          .split(',')
          .map((s) => s.trim())
          .filter(Boolean),
      })),
    }

    await api.put('/admin/rubrics/bulk', payload)
    toast.success('Perubahan rubrik dan bobot berhasil disimpan.')
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
