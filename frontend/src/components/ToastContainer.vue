<template>
  <div class="no-print fixed top-4 right-4 z-[100] flex flex-col gap-2 max-w-sm w-full px-4 sm:px-0 pointer-events-none">
    <TransitionGroup
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 translate-y-2 scale-95"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="pointer-events-auto flex items-start gap-3 rounded-xl border p-4 shadow-lg backdrop-blur-sm"
        :class="{
          'bg-emerald-50/95 border-emerald-200 text-emerald-900': toast.type === 'success',
          'bg-rose-50/95 border-rose-200 text-rose-900': toast.type === 'error',
          'bg-sky-50/95 border-sky-200 text-sky-900': toast.type === 'info',
        }"
      >
        <CheckCircle2 v-if="toast.type === 'success'" class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" />
        <AlertCircle v-else-if="toast.type === 'error'" class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" />
        <Info v-else class="w-5 h-5 text-sky-600 shrink-0 mt-0.5" />

        <p class="text-sm font-medium flex-1 leading-snug">{{ toast.message }}</p>

        <button
          type="button"
          class="p-1 rounded-lg opacity-60 hover:opacity-100 transition"
          @click="remove(toast.id)"
        >
          <X class="w-4 h-4" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { CheckCircle2, AlertCircle, Info, X } from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'

const { toasts, remove } = useToast()
</script>
