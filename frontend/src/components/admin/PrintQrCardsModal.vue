<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 bg-slate-900/75 backdrop-blur-sm overflow-y-auto p-4 print:static print:bg-white print:p-0"
    >
      <div class="max-w-5xl mx-auto bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden print:shadow-none print:border-0 print:rounded-none">
        <!-- Toolbar (disembunyikan saat print) -->
        <div class="no-print px-6 py-4 bg-slate-900 text-white flex flex-wrap items-center justify-between gap-3 sticky top-0 z-10">
          <div>
            <h3 class="font-bold text-lg">Cetak Massal Kartu QR Peserta Ujikom</h3>
            <p class="text-xs text-slate-300">Total {{ students.length }} kartu peserta siap dicetak (A4 Grid)</p>
          </div>
          <div class="flex items-center gap-2.5">
            <button
              type="button"
              class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-sm flex items-center gap-2 transition"
              @click="triggerPrint"
            >
              <Printer class="w-4 h-4" />
              Cetak Sekarang
            </button>
            <button
              type="button"
              class="p-2.5 rounded-xl bg-white/10 hover:bg-white/20 transition"
              @click="$emit('close')"
            >
              <X class="w-5 h-5" />
            </button>
          </div>
        </div>

        <!-- Printable Grid -->
        <div class="p-6 print:p-2 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 print:grid-cols-3 gap-4 print:gap-3">
          <div
            v-for="student in students"
            :key="student.id"
            class="qr-print-card border-2 border-slate-300 rounded-2xl p-4 bg-white flex flex-col items-center text-center"
          >
            <div class="w-full border-b border-slate-200 pb-2 mb-3">
              <p class="text-[10px] font-extrabold uppercase tracking-wider text-indigo-700">
                Kartu Peserta Ujikom RPL/PPLG
              </p>
              <p class="font-extrabold text-slate-900 text-sm truncate mt-0.5">{{ student.name }}</p>
              <div class="flex items-center justify-center gap-2 text-[11px] text-slate-600 mt-0.5">
                <span class="font-mono font-bold">NIS: {{ student.nis }}</span>
                <span>•</span>
                <span>{{ student.class_name }}</span>
              </div>
              <span
                v-if="student.is_priority"
                class="inline-block mt-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-900 text-[10px] font-extrabold uppercase"
              >
                Prioritas
              </span>
            </div>

            <div class="p-2 bg-white border border-slate-200 rounded-xl">
              <QrcodeVue
                :value="student.qr_code_token"
                :size="120"
                level="M"
                render-as="svg"
              />
            </div>

            <p class="mt-2 font-mono text-[9px] text-slate-400 break-all">
              {{ student.qr_code_token }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import QrcodeVue from 'qrcode.vue'
import { Printer, X } from 'lucide-vue-next'

defineProps({
  open: Boolean,
  students: {
    type: Array,
    default: () => [],
  },
})

defineEmits(['close'])

function triggerPrint() {
  window.print()
}
</script>
