<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="no-print fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 backdrop-blur-sm p-4"
      @click.self="$emit('close')"
    >
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-slate-200 max-h-[90vh] flex flex-col">
        <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between shrink-0">
          <h3 class="font-bold text-lg">
            {{ isEdit ? 'Edit Akun Penguji & Kelas Uji' : 'Tambah Penguji Baru' }}
          </h3>
          <button type="button" class="p-2 rounded-xl hover:bg-white/10" @click="$emit('close')">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form class="p-6 space-y-4 overflow-y-auto" @submit.prevent="submitForm">
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

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
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
                PIN Revisi 6-Digit {{ isEdit ? '(Opsional)' : '' }}
              </label>
              <input
                v-model.trim="form.revision_pin"
                type="text"
                inputmode="numeric"
                pattern="[0-9]{6}"
                maxlength="6"
                :required="!isEdit"
                placeholder="123456"
                class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-indigo-500 outline-none text-sm font-mono tracking-widest"
              />
            </div>
          </div>

          <!-- Penugasan Kelas yang Diuji (Bisa 1, 2, atau lebih kelas dari PPLG / TMS) -->
          <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4 space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div>
                <p class="text-sm font-bold text-slate-800">Kelas yang Diuji</p>
                <p class="text-xs text-slate-500">Pilih 1, 2, atau lebih kelas sesuai jurusan penguji.</p>
              </div>
              <div class="flex gap-1.5">
                <button
                  type="button"
                  class="px-2.5 py-1 rounded-lg bg-indigo-100 hover:bg-indigo-200 text-indigo-800 text-[11px] font-bold"
                  @click="selectByMajor('PPLG')"
                >
                  + Semua PPLG
                </button>
                <button
                  type="button"
                  class="px-2.5 py-1 rounded-lg bg-amber-100 hover:bg-amber-200 text-amber-900 text-[11px] font-bold"
                  @click="selectByMajor('TMS')"
                >
                  + Semua TMS
                </button>
              </div>
            </div>

            <div v-if="classes.length === 0" class="text-xs text-slate-500 italic">
              Belum ada kelas terdaftar. Tambahkan kelas terlebih dahulu.
            </div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto pr-1">
              <label
                v-for="cls in classes"
                :key="cls.id"
                class="flex items-center gap-2.5 p-2.5 rounded-xl border cursor-pointer transition"
                :class="
                  form.class_ids.includes(cls.id)
                    ? 'bg-indigo-50 border-indigo-400 text-indigo-950'
                    : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-100'
                "
              >
                <input
                  v-model="form.class_ids"
                  type="checkbox"
                  :value="cls.id"
                  class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500"
                />
                <div class="min-w-0 flex-1">
                  <div class="flex items-center justify-between gap-1">
                    <span class="text-xs font-extrabold truncate">{{ cls.name }}</span>
                    <span
                      class="px-1.5 py-0.5 rounded text-[10px] font-extrabold uppercase shrink-0"
                      :class="cls.major === 'TMS' ? 'bg-amber-100 text-amber-800' : 'bg-indigo-100 text-indigo-800'"
                    >
                      {{ cls.major }}
                    </span>
                  </div>
                  <p class="text-[11px] text-slate-500">{{ cls.academic_year }}</p>
                </div>
              </label>
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
  email: '',
  password: '',
  revision_pin: '',
  class_ids: [],
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
        form.class_ids = [...(props.examiner.assigned_class_ids || [])]
      } else {
        form.name = ''
        form.email = ''
        form.password = ''
        form.revision_pin = '123456'
        form.class_ids = []
      }
    }
  }
)

function selectByMajor(major) {
  const ids = props.classes.filter((c) => c.major === major).map((c) => c.id)
  form.class_ids = [...new Set([...form.class_ids, ...ids])]
}

async function submitForm() {
  saving.value = true
  try {
    const payload = {
      name: form.name,
      email: form.email,
      class_ids: form.class_ids,
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
