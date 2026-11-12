<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="no-print fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 backdrop-blur-sm p-4"
      @click.self="$emit('close')"
    >
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-200">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
          <h3 class="font-bold text-lg">
            {{ isEdit ? 'Edit Data Siswa' : 'Tambah Siswa Baru' }}
          </h3>
          <button type="button" class="p-2 rounded-xl hover:bg-white/10" @click="$emit('close')">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form class="p-6 space-y-4" @submit.prevent="submitForm">
          <div>
            <label class="block text-sm font-bold text-slate-700 mb-1">Nama Lengkap</label>
            <input
              v-model.trim="form.name"
              type="text"
              required
              placeholder="Contoh: Aditya Pratama Wijaya"
              class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-indigo-500 outline-none text-sm"
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-bold text-slate-700 mb-1">NIS (Username Login)</label>
              <input
                v-model.trim="form.nis"
                type="text"
                required
                placeholder="24251037"
                class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-indigo-500 outline-none text-sm font-mono"
              />
            </div>

            <div>
              <label class="block text-sm font-bold text-slate-700 mb-1">Kelas</label>
              <select
                v-model="form.class_id"
                required
                class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-indigo-500 outline-none text-sm bg-white"
              >
                <option v-for="cls in classes" :key="cls.id" :value="cls.id">
                  {{ cls.name }} ({{ cls.academic_year }})
                </option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-sm font-bold text-slate-700 mb-1">
              {{ isEdit ? 'Password Baru (Kosongkan jika tidak diubah)' : 'Password (Default sama dengan NIS jika kosong)' }}
            </label>
            <input
              v-model="form.password"
              type="password"
              placeholder="Minimal 4 karakter"
              class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-indigo-500 outline-none text-sm"
            />
          </div>

          <div class="rounded-2xl bg-amber-50 border border-amber-200 p-4 space-y-3">
            <label class="flex items-center gap-3 cursor-pointer">
              <input
                v-model="form.is_priority"
                type="checkbox"
                class="w-5 h-5 rounded text-amber-600 focus:ring-amber-500"
              />
              <div>
                <span class="text-sm font-bold text-amber-950">Tandai sebagai Siswa Prioritas</span>
                <p class="text-xs text-amber-800">Ditampilkan dengan highlight khusus pada antrean penguji.</p>
              </div>
            </label>

            <div v-if="form.is_priority">
              <label class="block text-xs font-bold text-amber-900 mb-1">Catatan Prioritas</label>
              <input
                v-model.trim="form.priority_note"
                type="text"
                placeholder="Contoh: Dispensasi Lomba / Tes PTN / Medis"
                class="w-full px-3.5 py-2.5 rounded-xl border border-amber-300 bg-white focus:border-amber-500 outline-none text-sm"
              />
            </div>
          </div>

          <div class="pt-2 flex gap-3">
            <button
              type="button"
              class="flex-1 min-h-[48px] rounded-xl border border-slate-300 text-slate-700 font-bold text-sm hover:bg-slate-100 transition"
              @click="$emit('close')"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="flex-1 min-h-[48px] rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white font-bold text-sm flex items-center justify-center gap-2 transition"
            >
              <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
              <span>{{ saving ? 'Menyimpan...' : 'Simpan Data' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { Loader2, X } from 'lucide-vue-next'
import api from '@/services/api'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  open: Boolean,
  student: {
    type: Object,
    default: null,
  },
  classes: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['close', 'saved'])
const toast = useToast()
const saving = ref(false)

const form = reactive({
  name: '',
  nis: '',
  class_id: null,
  password: '',
  is_priority: false,
  priority_note: '',
})

const isEdit = computed(() => Boolean(props.student?.id))

watch(
  () => props.open,
  (val) => {
    if (val) {
      if (props.student) {
        form.name = props.student.name
        form.nis = props.student.nis
        form.class_id = props.student.class_id
        form.password = ''
        form.is_priority = Boolean(props.student.is_priority)
        form.priority_note = props.student.priority_note || ''
      } else {
        form.name = ''
        form.nis = ''
        form.class_id = props.classes[0]?.id || null
        form.password = ''
        form.is_priority = false
        form.priority_note = ''
      }
    }
  }
)

async function submitForm() {
  saving.value = true
  try {
    const payload = {
      name: form.name,
      nis: form.nis,
      class_id: form.class_id,
      is_priority: form.is_priority,
      priority_note: form.is_priority ? form.priority_note : null,
    }
    if (form.password) {
      payload.password = form.password
    }

    if (isEdit.value) {
      await api.put(`/admin/students/${props.student.id}`, payload)
      toast.success('Data siswa berhasil diperbarui.')
    } else {
      await api.post('/admin/students', payload)
      toast.success('Siswa baru berhasil ditambahkan.')
    }
    emit('saved')
  } catch (err) {
    const msg = err.response?.data?.message || 'Gagal menyimpan data siswa.'
    toast.error(msg)
  } finally {
    saving.value = false
  }
}
</script>
