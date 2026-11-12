<template>
  <header class="no-print sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-3">
      <div class="flex items-center gap-3 min-w-0">
        <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-sm shrink-0">
          <GraduationCap class="w-5 h-5" />
        </div>
        <div class="min-w-0">
          <div class="flex items-center gap-2">
            <h1 class="font-bold text-slate-900 text-base sm:text-lg truncate">
              UJIKOM PPLG
            </h1>
            <span
              class="px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider rounded-full"
              :class="roleBadgeClass"
            >
              {{ auth.userRole }}
            </span>
          </div>
          <p class="text-xs text-slate-500 truncate">{{ subtitle || auth.user?.name }}</p>
        </div>
      </div>

      <div class="flex items-center gap-2 sm:gap-3 shrink-0">
        <template v-if="auth.userRole === 'admin'">
          <router-link
            v-if="route.path !== '/admin/dashboard'"
            to="/admin/dashboard"
            class="px-3 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 transition"
          >
            Dashboard Admin
          </router-link>
          <router-link
            v-else
            to="/penguji/queue"
            class="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs sm:text-sm font-semibold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 transition"
          >
            <ClipboardCheck class="w-4 h-4" />
            Mode Penguji
          </router-link>
        </template>

        <div class="hidden md:flex flex-col text-right mr-1">
          <span class="text-sm font-semibold text-slate-800">{{ auth.user?.name }}</span>
          <span class="text-xs text-slate-500">{{ auth.user?.username }}</span>
        </div>

        <button
          type="button"
          class="inline-flex items-center gap-2 px-3.5 py-2.5 min-h-[44px] rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 font-semibold text-sm transition active:scale-95"
          @click="handleLogout"
        >
          <LogOut class="w-4 h-4" />
          <span class="hidden sm:inline">Keluar</span>
        </button>
      </div>
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { GraduationCap, LogOut, ClipboardCheck } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'

defineProps({
  subtitle: {
    type: String,
    default: '',
  },
})

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const roleBadgeClass = computed(() => {
  switch (auth.userRole) {
    case 'admin':
      return 'bg-purple-100 text-purple-700'
    case 'penguji':
      return 'bg-indigo-100 text-indigo-700'
    default:
      return 'bg-emerald-100 text-emerald-700'
  }
})

async function handleLogout() {
  await auth.logout()
  router.replace({ name: 'login' })
}
</script>
