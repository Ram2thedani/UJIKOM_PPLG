<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 flex items-center justify-center p-4 sm:p-6">
    <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden">
      <div class="bg-gradient-to-r from-emerald-700 via-teal-700 to-indigo-800 px-6 py-7 text-white text-center">
        <div class="w-20 h-20 mx-auto mb-3 rounded-2xl bg-white p-2 shadow-lg flex items-center justify-center overflow-hidden">
          <img
            :src="'/marhasupdate.webp'"
            alt="Logo Marhas Ujikom"
            class="w-full h-full object-contain"
          />
        </div>
        <h1 class="text-2xl font-extrabold tracking-tight">Marhas Ujikom</h1>
        <p class="text-emerald-100 text-sm mt-1">Sistem Penilaian Uji Kompetensi Keahlian (PPLG &amp; TMS)</p>
      </div>

      <div class="p-6 sm:p-8">
        <div
          v-if="errorMessage"
          class="mb-5 flex items-start gap-2.5 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-sm font-medium"
        >
          <AlertCircle class="w-5 h-5 shrink-0 mt-0.5" />
          <span>{{ errorMessage }}</span>
        </div>

        <form class="space-y-4" @submit.prevent="submitLogin">
          <div>
            <label for="username" class="block text-sm font-semibold text-slate-700 mb-1.5">
              Email atau NIS
            </label>
            <div class="relative">
              <User class="w-5 h-5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                id="username"
                v-model.trim="form.username"
                type="text"
                autocomplete="username"
                placeholder="Contoh: penguji@ujikom.sch.id atau 24251001"
                class="w-full pl-11 pr-4 py-3.5 rounded-2xl border border-slate-300 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 outline-none transition text-base"
                required
              />
            </div>
          </div>

          <div>
            <label for="password" class="block text-sm font-semibold text-slate-700 mb-1.5">
              Password
            </label>
            <div class="relative">
              <Lock class="w-5 h-5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="current-password"
                placeholder="Masukkan password (NIS untuk siswa)"
                class="w-full pl-11 pr-12 py-3.5 rounded-2xl border border-slate-300 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/15 outline-none transition text-base"
                required
              />
              <button
                type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 p-2 text-slate-400 hover:text-slate-600"
                @click="showPassword = !showPassword"
              >
                <EyeOff v-if="showPassword" class="w-5 h-5" />
                <Eye v-else class="w-5 h-5" />
              </button>
            </div>
          </div>

          <button
            type="submit"
            :disabled="auth.loading"
            class="w-full min-h-[52px] rounded-2xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white font-bold text-base shadow-lg shadow-indigo-600/25 flex items-center justify-center gap-2 transition active:scale-[0.99]"
          >
            <Loader2 v-if="auth.loading" class="w-5 h-5 animate-spin" />
            <LogIn v-else class="w-5 h-5" />
            <span>{{ auth.loading ? 'Memproses...' : 'Masuk ke Sistem' }}</span>
          </button>
        </form>

        
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  AlertCircle,
  ClipboardCheck,
  Eye,
  EyeOff,
  GraduationCap,
  Loader2,
  Lock,
  LogIn,
  ShieldCheck,
  User,
  UserCheck,
} from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'

const auth = useAuthStore()
const router = useRouter()
const toast = useToast()

const form = reactive({
  username: '',
  password: '',
})
const showPassword = ref(false)
const errorMessage = ref('')

async function submitLogin() {
  errorMessage.value = ''
  try {
    await auth.login(form.username, form.password)
    toast.success(`Selamat datang, ${auth.user.name}!`)
    router.replace(auth.roleHomePath)
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Gagal login. Periksa koneksi atau kredensial Anda.'
  }
}

async function quickLogin(username, password) {
  form.username = username
  form.password = password
  await submitLogin()
}
</script>
