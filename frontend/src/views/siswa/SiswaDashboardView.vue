<template>
  <div class="min-h-screen bg-slate-50 print:bg-white">
    <AppNavbar :subtitle="data?.profile ? `${data.profile.class_name} (${data.profile.major}) • NIS ${data.profile.nis}` : 'Dashboard Peserta Ujikom'" />

    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-6 print:p-0">
      <div class="no-print flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
          <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900">Kartu Peserta & Status Ujikom</h2>
          <p class="text-sm text-slate-500">Tunjukkan Kartu Peserta (NIS / QR Code) kepada penguji saat giliran Anda.</p>
        </div>
        <div class="flex items-center gap-2">
          <button
            v-if="data"
            type="button"
            class="inline-flex items-center gap-2 px-4 py-2.5 min-h-[44px] rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm shadow-sm transition active:scale-95"
            @click="printCard"
          >
            <Printer class="w-4 h-4" />
            <span>Cetak Kartu</span>
          </button>

          <button
            type="button"
            :disabled="loading"
            class="inline-flex items-center gap-2 px-4 py-2.5 min-h-[44px] rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 font-semibold text-sm shadow-sm transition active:scale-95"
            @click="loadDashboard"
          >
            <RefreshCw class="w-4 h-4" :class="{ 'animate-spin': loading }" />
            <span>Muat Ulang</span>
          </button>
        </div>
      </div>

      <div v-if="loading && !data" class="grid md:grid-cols-2 gap-6">
        <div class="h-96 rounded-3xl bg-white border border-slate-200 animate-pulse"></div>
        <div class="h-96 rounded-3xl bg-white border border-slate-200 animate-pulse"></div>
      </div>

      <div v-else-if="data" class="grid grid-cols-1 lg:grid-cols-12 gap-6 print:block">
        <!-- Kartu Peserta Resmi (Siap Scan Semi-OCR NIS & QR Code) -->
        <section class="lg:col-span-5 bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden print:max-w-md print:mx-auto print:border-2 print:border-slate-900 print:shadow-none">
          <div class="bg-gradient-to-r from-indigo-600 to-blue-600 px-6 py-5 text-white print:bg-slate-900">
            <div class="flex items-start justify-between gap-2">
              <div>
                <p class="text-xs font-bold uppercase tracking-wider text-indigo-100">
                  Kartu Peserta Ujikom • {{ data.profile.major }}
                </p>
                <h3 class="text-lg font-extrabold mt-0.5 leading-snug">{{ data.profile.name }}</h3>
              </div>
              <span
                v-if="data.profile.is_priority"
                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-400 text-amber-950 text-xs font-extrabold uppercase shrink-0"
              >
                <Star class="w-3.5 h-3.5 fill-amber-950" />
                Prioritas
              </span>
            </div>

            <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold">
              <span class="px-2.5 py-1 rounded-lg bg-white/15">{{ data.profile.class_name }}</span>
              <span class="px-2.5 py-1 rounded-lg bg-white/15">Jurusan {{ data.profile.major }}</span>
              <span class="px-2.5 py-1 rounded-lg bg-white/15">{{ data.profile.academic_year }}</span>
            </div>
          </div>

          <div class="p-6 flex flex-col items-center text-center">
            <!-- Kotak NIS Kontras Tinggi untuk Semi-OCR Kamera Penguji -->
            <div class="w-full mb-4 px-4 py-3 rounded-2xl bg-white border-2 border-slate-900 text-center">
              <p class="text-[10px] font-extrabold uppercase tracking-widest text-slate-500">
                Nomor Induk Siswa (Scan OCR)
              </p>
              <p class="text-3xl font-mono font-black tracking-[0.14em] text-slate-950 mt-0.5 select-all">
                {{ data.profile.nis }}
              </p>
            </div>

            <div
              v-if="data.profile.is_priority && data.profile.priority_note"
              class="w-full mb-4 px-3.5 py-2.5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs font-semibold flex items-center gap-2 text-left"
            >
              <AlertTriangle class="w-4 h-4 text-amber-600 shrink-0" />
              <span>Catatan Prioritas: {{ data.profile.priority_note }}</span>
            </div>

            <div class="p-4 bg-white rounded-3xl border-2 border-dashed border-slate-300 shadow-inner">
              <QrcodeVue
                :value="data.profile.qr_code_token"
                :size="190"
                level="M"
                render-as="svg"
              />
            </div>

            <p class="mt-3 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Token Peserta</p>
            <p class="mt-1 font-mono text-[11px] bg-slate-100 text-slate-700 px-3 py-1 rounded-lg break-all">
              {{ data.profile.qr_code_token }}
            </p>
          </div>
        </section>

        <!-- Kartu Status Ujikom (disembunyikan saat cetak kartu) -->
        <section class="no-print lg:col-span-7 space-y-5">
          <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
              <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <Award class="w-5 h-5 text-indigo-600" />
                Status Hasil Penilaian
              </h3>

              <span
                v-if="data.assessment.status === 'pending'"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-slate-100 text-slate-700 text-xs font-extrabold uppercase"
              >
                <Clock class="w-4 h-4" />
                Menunggu Giliran Penilaian
              </span>
              <span
                v-else-if="data.assessment.status === 'kompeten'"
                class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-emerald-100 text-emerald-800 text-xs font-extrabold uppercase"
              >
                <CheckCircle2 class="w-4 h-4" />
                KOMPETEN
              </span>
              <span
                v-else
                class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-rose-100 text-rose-800 text-xs font-extrabold uppercase"
              >
                <AlertOctagon class="w-4 h-4" />
                PERLU REMEDIAL
              </span>
            </div>

            <div v-if="data.assessment.status === 'pending'" class="rounded-2xl bg-slate-50 border border-slate-200 p-5">
              <p class="text-sm text-slate-700 font-medium leading-relaxed">
                Anda saat ini berada dalam antrean penilaian kelas <strong>{{ data.profile.class_name }}</strong> ({{ data.profile.major }}).
                Silakan siapkan perlengkapan dan tunjukkan Kartu Peserta ini saat dipanggil oleh penguji.
              </p>
              <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 gap-3">
                <div class="bg-white rounded-xl p-3 border border-slate-200">
                  <p class="text-xs text-slate-500">Posisi Antrean</p>
                  <p class="text-xl font-extrabold text-indigo-600">
                    #{{ data.queue_summary.my_queue_position || '-' }}
                  </p>
                </div>
                <div class="bg-white rounded-xl p-3 border border-slate-200">
                  <p class="text-xs text-slate-500">Sudah Dinilai</p>
                  <p class="text-xl font-extrabold text-slate-900">
                    {{ data.queue_summary.graded_count }} / {{ data.queue_summary.total_students }}
                  </p>
                </div>
                <div class="bg-white rounded-xl p-3 border border-slate-200 col-span-2 sm:col-span-1">
                  <p class="text-xs text-slate-500">Sisa Menunggu</p>
                  <p class="text-xl font-extrabold text-amber-600">
                    {{ data.queue_summary.waiting_count }} siswa
                  </p>
                </div>
              </div>
            </div>

            <div v-else class="space-y-5">
              <div
                class="rounded-2xl p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border"
                :class="
                  data.assessment.status === 'kompeten'
                    ? 'bg-emerald-50 border-emerald-200'
                    : 'bg-rose-50 border-rose-200'
                "
              >
                <div>
                  <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Nilai Akhir Berbobot</p>
                  <p
                    class="text-4xl font-black mt-1"
                    :class="data.assessment.status === 'kompeten' ? 'text-emerald-700' : 'text-rose-700'"
                  >
                    {{ formatScore(data.assessment.final_score) }}
                  </p>
                  <p class="text-xs text-slate-600 mt-1">
                    Ambang batas kelulusan Kompeten: <strong>80.00</strong>
                  </p>
                </div>

                <div class="text-sm text-slate-700 space-y-1 sm:text-right">
                  <p>
                    <span class="text-slate-500">Penguji:</span>
                    <strong class="ml-1">{{ data.assessment.examiner_name || '-' }}</strong>
                  </p>
                  <p>
                    <span class="text-slate-500">Waktu Dinilai:</span>
                    <strong class="ml-1">{{ formatDateTime(data.assessment.submitted_at) }}</strong>
                  </p>
                  <p v-if="data.assessment.revision_count > 0" class="text-xs text-amber-700 font-semibold">
                    Revisi penilaian ke-{{ data.assessment.revision_count }}
                  </p>
                </div>
              </div>

              <div v-if="data.assessment.quick_tags?.length">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">
                  Tag Evaluasi Penguji
                </p>
                <div class="flex flex-wrap gap-2">
                  <span
                    v-for="tag in data.assessment.quick_tags"
                    :key="tag"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold border"
                    :class="
                      data.assessment.status === 'kompeten'
                        ? 'bg-emerald-50 border-emerald-200 text-emerald-800'
                        : 'bg-amber-50 border-amber-200 text-amber-900'
                    "
                  >
                    {{ tag }}
                  </span>
                </div>
              </div>

              <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">
                  Catatan Perbaikan / Masukan Penguji
                </p>
                <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4 text-sm text-slate-700 whitespace-pre-line">
                  {{ data.assessment.examiner_note || 'Tidak ada catatan tambahan dari penguji.' }}
                </div>
              </div>
            </div>
          </div>

          <div
            v-if="data.assessment.status !== 'pending' && data.assessment.details?.length"
            class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6"
          >
            <h4 class="text-base font-bold text-slate-900 mb-4">Rincian Skor per Kriteria Rubrik</h4>
            <div class="space-y-2.5">
              <div
                v-for="item in data.assessment.details"
                :key="item.rubric_item_id"
                class="flex items-center justify-between gap-3 p-3 rounded-2xl bg-slate-50 border border-slate-100"
              >
                <div class="min-w-0">
                  <p class="text-sm font-semibold text-slate-800">
                    {{ item.item_order }}. {{ item.title }}
                  </p>
                  <p class="text-xs text-slate-500">Bobot: {{ item.weight }}%</p>
                </div>
                <div class="text-right shrink-0">
                  <span
                    class="inline-block px-2.5 py-1 rounded-lg text-sm font-extrabold"
                    :class="item.raw_score >= 80 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                  >
                    {{ item.raw_score }}
                  </span>
                  <p class="text-[11px] text-slate-500 mt-0.5">
                    Berbobot: {{ formatScore(item.weighted_score) }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </main>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import QrcodeVue from 'qrcode.vue'
import {
  AlertOctagon,
  AlertTriangle,
  Award,
  CheckCircle2,
  Clock,
  Printer,
  RefreshCw,
  Star,
} from 'lucide-vue-next'
import AppNavbar from '@/components/AppNavbar.vue'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'
import { formatDateTime, formatScore } from '@/utils/formatters'

const toast = useToast()
const loading = ref(false)
const data = ref(null)

async function loadDashboard() {
  loading.value = true
  try {
    const res = await api.get('/student/dashboard')
    data.value = res.data
  } catch (err) {
    toast.error(err.response?.data?.message || 'Gagal memuat dashboard siswa.')
  } finally {
    loading.value = false
  }
}

function printCard() {
  window.print()
}

onMounted(loadDashboard)
</script>
