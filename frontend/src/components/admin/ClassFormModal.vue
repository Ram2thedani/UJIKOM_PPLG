<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="no-print fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 backdrop-blur-sm p-4"
      @click.self="$emit('close')"
    >
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-200">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
          <h3 class="font-bold text-lg">
            {{ isEdit ? 'Edit Data Kelas' : 'Tambah Kelas Baru' }}
          </h3>
          <button type="button" class="p-2 rounded-xl hover:bg-white/10" @click="$emit('close')">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form class="p-6 space-y-4" @submit.prevent="submitForm">
          <div>
            <label class="block text-sm font-bold text-slate-700 mb-1">Jurusan</label>
            <div class="grid grid-cols-2 gap-2.5">
              <button
                v-for="m in ['PPLG', 'TMS']"
                :key="m"
                type="button"
                class="min-h-[46px] rounded-xl border-2 font-extrabold text-sm transition"
                :class="
                  form.major === m
                    ? 'bg-indigo-600 border-indigo-600 text-white'
                    : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'
                "
                @click="form.major = m"
              >
                {{ m }}
              </button>
            </div>
          </div>

          <div>
            <label class="block text-sm font-bold text-slate-700 mb-1">Nama Kelas</label>
            <input
              v-model.trim="form.name"
              type="text"
              required
              placeholder="Contoh: XII PPLG 2 atau XII TMS 1"
              class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-indigo-500 outline-none text-sm"
            />
          </div>

          <div>
            <label class="block text-sm font-bold text-slate-700 mb-1">Tahun Ajaran</label>
            <input
              v-model.trim="form.academic_year"
              type="text"
              required
              placeholder="Contoh: 2026/2027"
              class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-indigo-500 outline-none text-sm"
            />
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
              <span>{{ saving ? 'Menyimpan...' : 'Simpan Kelas' }}</span>
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
  schoolClass: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['close', 'saved'])
const toast = useToast()
const saving = ref(false)

const form = reactive({
  name: '',
  major: 'PPLG',
  academic_year: '2026/2027',
})

const isEdit = computed(() => Boolean(props.schoolClass?.id))

watch(
  () => props.open,
  (val) => {
    if (val) {
      if (props.schoolClass) {
        form.name = props.schoolClass.name
        form.major = props.schoolClass.major || 'PPLG'
        form.academic_year = props.schoolClass.academic_year || '2026/2027'
      } else {
        form.name = ''
        form.major = 'PPLG'
        form.academic_year = '2026/2027'
      }
    }
  }
)

async function submitForm() {
  saving.value = true
  try {
    if (isEdit.value) {
      await api.put(`/admin/classes/${props.schoolClass.id}`, form)
      toast.success('Data kelas berhasil diperbarui.')
    } else {
      await api.post('/admin/classes', form)
      toast.success('Kelas baru berhasil ditambahkan.')
    }
    emit('saved')
  } catch (err) {
    toast.error(err.response?.data?.message || 'Gagal menyimpan data kelas.')
  } finally {
    saving.value = false
  }
}
</script>
