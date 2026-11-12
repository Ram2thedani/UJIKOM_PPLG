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
            {{ isEdit ? 'Edit Akun Penguji' : 'Tambah Penguji Baru' }}
          </h3>
          <button type="button" class="p-2 rounded-xl hover:bg-white/10" @click="$emit('close')">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form class="p-6 space-y-4" @submit.prevent="submitForm">
          <div>
            <label class="block text-sm font-bold text-slate-700 mb-1">Nama Penguji</label>
            <input
              v-model.trim="form.name"
              type="text"
              required
              placeholder="Contoh: Budi Santoso, S.Kom"
              class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-indigo-500 outline-none text-sm"
            />
          </div>

          <div>
            <label class="block text-sm font-bold text-slate-700 mb-1">Email (Username Login)</label>
            <input
              v-model.trim="form.email"
              type="email"
              required
              placeholder="penguji2@ujikom.sch.id"
              class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-indigo-500 outline-none text-sm"
            />
          </div>

          <div>
            <label class="block text-sm font-bold text-slate-700 mb-1">
              {{ isEdit ? 'Password Baru (Opsional)' : 'Password' }}
            </label>
            <input
              v-model="form.password"
              type="password"
              :required="!isEdit"
              minlength="6"
              placeholder="Minimal 6 karakter"
              class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-indigo-500 outline-none text-sm"
            />
          </div>

          <div>
            <label class="block text-sm font-bold text-slate-700 mb-1">
              PIN Revisi 6-Digit {{ isEdit ? '(Kosongkan jika tetap)' : '' }}
            </label>
            <input
              v-model.trim="form.revision_pin"
              type="text"
              inputmode="numeric"
              pattern="[0-9]{6}"
              maxlength="6"
              :required="!isEdit"
              placeholder="Contoh: 123456"
              class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-indigo-500 outline-none text-sm font-mono tracking-widest"
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
              <span>{{ saving ? 'Menyimpan...' : 'Simpan Penguji' }}</span>
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
  examiner: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['close', 'saved'])
const toast = useToast()
const saving = ref(false)

const form = reactive({
  name: '',
  email: '',
  password: '',
  revision_pin: '',
})

const isEdit = computed(() => Boolean(props.examiner?.id))

watch(
  () => props.open,
  (val) => {
    if (val) {
      if (props.examiner) {
        form.name = props.examiner.name
        form.email = props.examiner.email || props.examiner.username
        form.password = ''
        form.revision_pin = ''
      } else {
        form.name = ''
        form.email = ''
        form.password = ''
        form.revision_pin = '123456'
      }
    }
  }
)

async function submitForm() {
  saving.value = true
  try {
    const payload = {
      name: form.name,
      email: form.email,
    }
    if (form.password) payload.password = form.password
    if (form.revision_pin) payload.revision_pin = form.revision_pin

    if (isEdit.value) {
      await api.put(`/admin/examiners/${props.examiner.id}`, payload)
      toast.success('Akun penguji berhasil diperbarui.')
    } else {
      await api.post('/admin/examiners', payload)
      toast.success('Akun penguji berhasil ditambahkan.')
    }
    emit('saved')
  } catch (err) {
    toast.error(err.response?.data?.message || 'Gagal menyimpan akun penguji.')
  } finally {
    saving.value = false
  }
}
</script>
