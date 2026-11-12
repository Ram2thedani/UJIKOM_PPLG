<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="no-print fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 backdrop-blur-sm p-4"
      @click.self="$emit('close')"
    >
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden border border-slate-200 max-h-[90vh] flex flex-col">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between shrink-0">
          <div class="flex items-center gap-2">
            <FileSpreadsheet class="w-5 h-5 text-emerald-400" />
            <h3 class="font-bold text-lg">Impor Data Siswa (Excel / CSV)</h3>
          </div>
          <button type="button" class="p-2 rounded-xl hover:bg-white/10" @click="$emit('close')">
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="p-6 space-y-4 overflow-y-auto">
          <div class="flex flex-wrap items-center justify-between gap-3 bg-slate-50 border border-slate-200 rounded-2xl p-4">
            <div class="text-xs text-slate-600 space-y-1">
              <p class="font-bold text-slate-800 text-sm">Format Kolom File</p>
              <p>Gunakan kolom: <code class="bg-white px-1.5 py-0.5 rounded border">nis</code>, <code class="bg-white px-1.5 py-0.5 rounded border">name</code>, <code class="bg-white px-1.5 py-0.5 rounded border">is_priority</code> (0/1), <code class="bg-white px-1.5 py-0.5 rounded border">priority_note</code>.</p>
            </div>
            <button
              type="button"
              class="px-3.5 py-2 rounded-xl bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 text-xs font-bold flex items-center gap-1.5"
              @click="downloadTemplate"
            >
              <Download class="w-4 h-4 text-indigo-600" />
              Unduh Template .xlsx
            </button>
          </div>

          <div>
            <label class="block text-sm font-bold text-slate-700 mb-1">Target Kelas</label>
            <select
              v-model="targetClassId"
              class="w-full px-4 py-3 rounded-xl border border-slate-300 bg-white text-sm font-semibold"
            >
              <option v-for="cls in classes" :key="cls.id" :value="cls.id">
                {{ cls.name }} ({{ cls.academic_year }})
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-bold text-slate-700 mb-1.5">Pilih File (.xlsx, .xls, atau .csv)</label>
            <input
              type="file"
              accept=".xlsx,.xls,.csv"
              class="block w-full text-sm text-slate-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-300 rounded-xl p-2"
              @change="handleFileChange"
            />
          </div>

          <div v-if="parsedRows.length > 0" class="border border-slate-200 rounded-2xl overflow-hidden">
            <div class="bg-slate-100 px-4 py-2.5 text-xs font-bold text-slate-700 flex justify-between">
              <span>Pratinjau Data ({{ parsedRows.length }} siswa siap diimpor)</span>
            </div>
            <div class="max-h-52 overflow-y-auto">
              <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 text-slate-500 uppercase border-b">
                  <tr>
                    <th class="px-3 py-2">NIS</th>
                    <th class="px-3 py-2">Nama</th>
                    <th class="px-3 py-2">Prioritas</th>
                    <th class="px-3 py-2">Catatan</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="(row, idx) in parsedRows.slice(0, 30)" :key="idx">
                    <td class="px-3 py-2 font-mono font-bold">{{ row.nis }}</td>
                    <td class="px-3 py-2">{{ row.name }}</td>
                    <td class="px-3 py-2">{{ row.is_priority ? 'Ya' : 'Tidak' }}</td>
                    <td class="px-3 py-2 text-slate-500">{{ row.priority_note || '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex gap-3 shrink-0">
          <button
            type="button"
            class="flex-1 min-h-[48px] rounded-xl border border-slate-300 text-slate-700 font-bold text-sm hover:bg-slate-100 transition"
            @click="$emit('close')"
          >
            Batal
          </button>
          <button
            type="button"
            :disabled="importing || parsedRows.length === 0"
            class="flex-1 min-h-[48px] rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold text-sm flex items-center justify-center gap-2 transition"
            @click="submitImport"
          >
            <Loader2 v-if="importing" class="w-4 h-4 animate-spin" />
            <Upload v-else class="w-4 h-4" />
            <span>{{ importing ? 'Mengimpor...' : `Impor ${parsedRows.length} Siswa` }}</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'
import * as XLSX from 'xlsx'
import { Download, FileSpreadsheet, Loader2, Upload, X } from 'lucide-vue-next'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  open: Boolean,
  classes: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['close', 'imported'])
const toast = useToast()

const targetClassId = ref(null)
const parsedRows = ref([])
const importing = ref(false)

watch(
  () => props.open,
  (val) => {
    if (val) {
      targetClassId.value = props.classes[0]?.id || null
      parsedRows.value = []
    }
  }
)

async function handleFileChange(event) {
  const file = event.target.files?.[0]
  if (!file) return

  try {
    const buffer = await file.arrayBuffer()
    const wb = XLSX.read(buffer, { type: 'array' })
    const sheet = wb.Sheets[wb.SheetNames[0]]
    const raw = XLSX.utils.sheet_to_json(sheet, { defval: '' })

    parsedRows.value = raw
      .map((r) => {
        const normalized = {}
        Object.keys(r).forEach((k) => {
          normalized[k.trim().toLowerCase()] = r[k]
        })
        const nis = String(normalized.nis ?? '').trim()
        const name = String(normalized.name ?? normalized.nama ?? '').trim()
        const prioRaw = String(normalized.is_priority ?? normalized.prioritas ?? '').trim().toLowerCase()
        const isPriority = ['1', 'true', 'ya', 'yes'].includes(prioRaw)
        const priorityNote = String(normalized.priority_note ?? normalized.catatan_prioritas ?? '').trim()

        return {
          nis,
          name,
          class_id: targetClassId.value,
          is_priority: isPriority,
          priority_note: priorityNote || null,
        }
      })
      .filter((r) => r.nis && r.name)

    if (parsedRows.value.length === 0) {
      toast.error('Tidak ditemukan baris dengan kolom nis & name yang valid.')
    }
  } catch {
    toast.error('Gagal membaca file Excel/CSV.')
  }
}

function downloadTemplate() {
  const sample = [
    { nis: '24251037', name: 'Contoh Siswa Reguler', is_priority: 0, priority_note: '' },
    { nis: '24251038', name: 'Contoh Siswa Prioritas', is_priority: 1, priority_note: 'Dispensasi LKS' },
  ]
  const wb = XLSX.utils.book_new()
  const ws = XLSX.utils.json_to_sheet(sample)
  XLSX.utils.book_append_sheet(wb, ws, 'Template Siswa')
  XLSX.writeFile(wb, 'Template_Impor_Siswa_Ujikom.xlsx')
}

async function submitImport() {
  if (parsedRows.value.length === 0) return
  importing.value = true
  try {
    const payload = {
      class_id: targetClassId.value,
      students: parsedRows.value.map((r) => ({ ...r, class_id: targetClassId.value })),
    }
    const { data } = await api.post('/admin/students/import', payload)
    toast.success(data.message)
    emit('imported')
  } catch (err) {
    toast.error(err.response?.data?.message || 'Gagal mengimpor data siswa.')
  } finally {
    importing.value = false
  }
}
</script>
