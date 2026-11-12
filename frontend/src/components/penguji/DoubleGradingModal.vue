<template>
  <Teleport to="body">
    <div
      v-if="open && student"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/75 backdrop-blur-sm p-4"
      @click.self="$emit('close')"
    >
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-200">
        <div
          class="px-6 py-5 text-white flex items-start justify-between gap-3"
          :class="isKompeten ? 'bg-emerald-600' : 'bg-rose-600'"
        >
          <div>
            <div class="flex items-center gap-2">
              <ShieldAlert class="w-5 h-5" />
              <span class="text-xs font-extrabold uppercase tracking-wider">
                Siswa Sudah Dinilai
              </span>
            </div>
            <h3 class="text-xl font-extrabold mt-1">{{ student.name }}</h3>
            <p class="text-sm opacity-90">NIS: {{ student.nis }}</p>
          </div>
          <button
            type="button"
            class="p-2 rounded-xl hover:bg-white/15 transition"
            @click="$emit('close')"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="p-6 space-y-5">
          <!-- Ringkasan Nilai Saat Ini -->
          <div class="rounded-2xl bg-slate-50 border border-slate-200 p-4 flex items-center justify-between gap-4">
            <div>
              <p class="text-xs font-bold uppercase text-slate-400">Status Saat Ini</p>
              <span
                class="inline-flex items-center gap-1 mt-1 px-3 py-1 rounded-full text-xs font-extrabold uppercase"
                :class="isKompeten ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
              >
                {{ isKompeten ? 'KOMPETEN' : 'REMEDIAL' }}
              </span>
              <p class="text-xs text-slate-500 mt-2">
                Penguji: <strong class="text-slate-700">{{ student.assessment?.examiner_name || '-' }}</strong>
              </p>
              <p v-if="student.assessment?.revision_count > 0" class="text-xs text-amber-700 font-semibold">
                Sudah direvisi {{ student.assessment.revision_count }}x
              </p>
            </div>

            <div class="text-right">
              <p class="text-xs font-bold uppercase text-slate-400">Nilai Akhir</p>
              <p
                class="text-3xl font-black"
                :class="isKompeten ? 'text-emerald-700' : 'text-rose-700'"
              >
                {{ formatScore(student.assessment?.final_score) }}
              </p>
            </div>
          </div>

          <!-- Opsi 1: Mode Baca -->
          <div v-if="!showPinInput" class="space-y-3">
            <p class="text-sm text-slate-600 text-center">
              Untuk mencegah penilaian ganda tanpa sengaja, pilih mode akses di bawah ini:
            </p>

            <button
              type="button"
              class="w-full min-h-[52px] px-4 py-3 rounded-2xl border-2 border-slate-200 hover:border-slate-300 bg-white hover:bg-slate-50 text-slate-800 font-bold text-sm flex items-center justify-center gap-2 transition active:scale-[0.99]"
              @click="$emit('read-only', student)"
            >
              <Eye class="w-5 h-5 text-slate-600" />
              Lihat Mode Baca (Read-Only)
            </button>

            <button
              type="button"
              class="w-full min-h-[52px] px-4 py-3 rounded-2xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-sm flex items-center justify-center gap-2 shadow-md shadow-amber-500/20 transition active:scale-[0.99]"
              @click="showPinInput = true"
            >
              <KeyRound class="w-5 h-5" />
              Buka Kunci Revisi (Input 6-digit PIN)
            </button>
          </div>

          <!-- Opsi 2: Input 6-digit PIN -->
          <form v-else class="space-y-4" @submit.prevent="verifyAndUnlock">
            <div>
              <label class="block text-sm font-bold text-slate-800 mb-1.5">
                Masukkan 6-Digit PIN Revisi Penguji
              </label>
              <input
                v-model.trim="pin"
                type="password"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="6"
                placeholder="••••••"
                autofocus
                class="w-full text-center tracking-[0.6em] text-2xl font-mono font-bold py-3.5 px-4 rounded-2xl border-2 border-slate-300 focus:border-amber-500 focus:ring-4 focus:ring-amber-500/20 outline-none"
              />
              <p v-if="pinError" class="mt-2 text-xs font-semibold text-rose-600 flex items-center gap-1">
                <AlertCircle class="w-4 h-4 shrink-0" />
                {{ pinError }}
              </p>
            </div>

            <div class="flex gap-2.5">
              <button
                type="button"
                class="flex-1 min-h-[48px] rounded-2xl border border-slate-300 text-slate-700 font-bold text-sm hover:bg-slate-100 transition"
                @click="resetPinStep"
              >
                Kembali
              </button>
              <button
                type="submit"
                :disabled="verifying || pin.length !== 6"
                class="flex-1 min-h-[48px] rounded-2xl bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-white font-bold text-sm flex items-center justify-center gap-2 transition"
              >
                <Loader2 v-if="verifying" class="w-4 h-4 animate-spin" />
                <span>{{ verifying ? 'Memeriksa...' : 'Buka Kunci' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { AlertCircle, Eye, KeyRound, Loader2, ShieldAlert, X } from 'lucide-vue-next'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { formatScore } from '@/utils/formatters'

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  student: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['close', 'read-only', 'unlocked'])

const auth = useAuthStore()
const showPinInput = ref(false)
const pin = ref('')
const pinError = ref('')
const verifying = ref(false)

const isKompeten = computed(() => props.student?.assessment?.status === 'kompeten')

function resetPinStep() {
  showPinInput.value = false
  pin.value = ''
  pinError.value = ''
}

async function verifyAndUnlock() {
  if (pin.value.length !== 6) {
    pinError.value = 'PIN harus terdiri dari 6 digit angka.'
    return
  }

  verifying.value = true
  pinError.value = ''

  try {
    await api.post('/assessments/verify-pin', {
      revision_pin: pin.value,
    })
    auth.setStudentRevisionPin(props.student.id, pin.value)
    emit('unlocked', props.student)
  } catch (err) {
    pinError.value = err.response?.data?.message || 'PIN revisi tidak valid.'
  } finally {
    verifying.value = false
  }
}

watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen) {
      resetPinStep()
    }
  }
)
</script>
