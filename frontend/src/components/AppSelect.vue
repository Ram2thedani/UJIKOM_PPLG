<template>
  <div ref="rootRef" class="relative w-full">
    <button
      ref="triggerRef"
      type="button"
      :disabled="disabled"
      class="w-full flex items-center justify-between gap-2 text-left transition-all duration-150 select-none outline-none disabled:opacity-50 disabled:cursor-not-allowed"
      :class="[sizeClasses, variantClasses, isOpen ? activeRingClass : '']"
      @click="toggleDropdown"
    >
      <div class="flex items-center gap-2 min-w-0 flex-1">
        <span
          v-if="selectedOption?.dotClass"
          class="w-2.5 h-2.5 rounded-full shrink-0"
          :class="selectedOption.dotClass"
        ></span>

        <span
          class="truncate"
          :class="selectedOption ? 'font-bold' : 'opacity-65 font-medium'"
        >
          {{ selectedOption ? selectedOption.label : placeholder }}
        </span>

        <span
          v-if="selectedOption?.badge"
          class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wider shrink-0"
          :class="selectedOption.badgeClass || 'bg-indigo-100 text-indigo-800'"
        >
          {{ selectedOption.badge }}
        </span>
      </div>

      <ChevronDown
        class="w-4 h-4 shrink-0 transition-transform duration-200"
        :class="[
          isOpen ? 'rotate-180' : '',
          variant === 'glass' ? 'text-indigo-100' : 'text-slate-400',
        ]"
      />
    </button>

    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-150 ease-out"
        enter-from-class="opacity-0 scale-95 -translate-y-1"
        enter-to-class="opacity-100 scale-100 translate-y-0"
        leave-active-class="transition duration-100 ease-in"
        leave-from-class="opacity-100 scale-100 translate-y-0"
        leave-to-class="opacity-0 scale-95 -translate-y-1"
      >
        <div
          v-if="isOpen"
          ref="menuRef"
          :style="menuStyle"
          class="fixed z-[120] bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-slate-200/90 ring-1 ring-slate-900/5 overflow-hidden flex flex-col"
        >
          <!-- Search input inside combobox -->
          <div
            v-if="showSearch"
            class="p-2 border-b border-slate-100 bg-slate-50/70 shrink-0"
          >
            <div class="relative">
              <Search class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
              <input
                ref="searchInputRef"
                v-model.trim="searchQuery"
                type="text"
                :placeholder="searchPlaceholder"
                class="w-full pl-8 pr-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs text-slate-800 font-medium focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/15 outline-none"
                @keydown.esc.stop="closeDropdown"
              />
            </div>
          </div>

          <!-- Options List -->
          <div class="overflow-y-auto max-h-64 p-1.5 space-y-0.5">
            <div
              v-if="filteredOptions.length === 0"
              class="px-3 py-4 text-center text-xs text-slate-400 font-medium"
            >
              Tidak ada pilihan yang cocok.
            </div>

            <button
              v-for="opt in filteredOptions"
              :key="String(opt.value)"
              type="button"
              :disabled="opt.disabled"
              class="w-full px-3 py-2.5 rounded-xl text-left flex items-center justify-between gap-2.5 transition-colors select-none disabled:opacity-40 disabled:pointer-events-none"
              :class="
                isSelected(opt.value)
                  ? 'bg-indigo-600 text-white font-bold shadow-xs'
                  : 'text-slate-700 hover:bg-indigo-50/80 hover:text-indigo-950 font-medium'
              "
              @click="selectOption(opt)"
            >
              <div class="flex items-center gap-2.5 min-w-0 flex-1">
                <span
                  v-if="opt.dotClass"
                  class="w-2.5 h-2.5 rounded-full shrink-0"
                  :class="isSelected(opt.value) ? 'bg-white' : opt.dotClass"
                ></span>

                <div class="min-w-0 flex-1">
                  <div class="flex items-center gap-2">
                    <span class="text-xs sm:text-sm truncate">{{ opt.label }}</span>
                    <span
                      v-if="opt.badge"
                      class="px-1.5 py-0.5 rounded text-[10px] font-extrabold uppercase tracking-wider shrink-0"
                      :class="
                        isSelected(opt.value)
                          ? 'bg-white/20 text-white'
                          : opt.badgeClass || 'bg-indigo-100 text-indigo-800'
                      "
                    >
                      {{ opt.badge }}
                    </span>
                  </div>
                  <p
                    v-if="opt.sublabel"
                    class="text-[11px] truncate mt-0.5"
                    :class="isSelected(opt.value) ? 'text-indigo-100' : 'text-slate-400'"
                  >
                    {{ opt.sublabel }}
                  </p>
                </div>
              </div>

              <Check
                v-if="isSelected(opt.value)"
                class="w-4 h-4 shrink-0 text-white"
              />
            </button>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Check, ChevronDown, Search } from 'lucide-vue-next'

const props = defineProps({
  modelValue: {
    type: [String, Number, Boolean, null],
    default: null,
  },
  options: {
    type: Array,
    default: () => [],
  },
  placeholder: {
    type: String,
    default: 'Pilih...',
  },
  size: {
    type: String,
    default: 'md', // 'xs' | 'sm' | 'md' | 'lg'
  },
  variant: {
    type: String,
    default: 'default', // 'default' | 'subtle' | 'glass'
  },
  searchable: {
    type: Boolean,
    default: false,
  },
  searchPlaceholder: {
    type: String,
    default: 'Cari pilihan...',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'change'])

const rootRef = ref(null)
const triggerRef = ref(null)
const menuRef = ref(null)
const searchInputRef = ref(null)

const isOpen = ref(false)
const searchQuery = ref('')
const menuStyle = ref({})

const normalizedOptions = computed(() =>
  props.options.map((opt) => {
    if (typeof opt === 'object' && opt !== null) {
      return opt
    }
    return { label: String(opt), value: opt }
  })
)

const selectedOption = computed(() =>
  normalizedOptions.value.find((opt) => String(opt.value) === String(props.modelValue))
)

const showSearch = computed(() => props.searchable)

const filteredOptions = computed(() => {
  if (!searchQuery.value) return normalizedOptions.value
  const q = searchQuery.value.toLowerCase()
  return normalizedOptions.value.filter(
    (opt) =>
      String(opt.label || '').toLowerCase().includes(q) ||
      String(opt.sublabel || '').toLowerCase().includes(q) ||
      String(opt.badge || '').toLowerCase().includes(q)
  )
})

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'xs':
      return 'min-h-[34px] px-2.5 py-1 rounded-xl text-xs'
    case 'sm':
      return 'min-h-[40px] px-3 py-1.5 rounded-xl text-xs sm:text-sm'
    case 'lg':
      return 'min-h-[50px] px-4 py-2.5 rounded-2xl text-sm sm:text-base'
    default:
      return 'min-h-[46px] px-3.5 py-2 rounded-xl text-sm'
  }
})

const variantClasses = computed(() => {
  switch (props.variant) {
    case 'glass':
      return 'bg-white/15 hover:bg-white/25 border border-white/30 text-white backdrop-blur-md shadow-sm'
    case 'subtle':
      return 'bg-slate-50 hover:bg-white border border-slate-200 hover:border-indigo-300 text-slate-800 shadow-2xs'
    default:
      return 'bg-white hover:bg-slate-50/80 border border-slate-300 hover:border-indigo-400 text-slate-800 shadow-2xs'
  }
})

const activeRingClass = computed(() =>
  props.variant === 'glass'
    ? 'ring-3 ring-white/30 border-white/60'
    : 'ring-4 ring-indigo-500/15 border-indigo-500 bg-white'
)

function isSelected(val) {
  return String(val) === String(props.modelValue)
}

function updateMenuPosition() {
  if (!triggerRef.value) return
  const rect = triggerRef.value.getBoundingClientRect()
  const viewportHeight = window.innerHeight
  const viewportWidth = window.innerWidth
  const spaceBelow = viewportHeight - rect.bottom
  const estimatedHeight = Math.min(310, normalizedOptions.value.length * 44 + (showSearch.value ? 52 : 16))

  const minWidth = Math.max(rect.width, 190)
  const left = Math.min(rect.left, viewportWidth - minWidth - 12)

  if (spaceBelow < estimatedHeight && rect.top > estimatedHeight) {
    menuStyle.value = {
      width: `${minWidth}px`,
      left: `${Math.max(8, left)}px`,
      bottom: `${viewportHeight - rect.top + 6}px`,
    }
  } else {
    menuStyle.value = {
      width: `${minWidth}px`,
      left: `${Math.max(8, left)}px`,
      top: `${rect.bottom + 6}px`,
    }
  }
}

async function toggleDropdown() {
  if (props.disabled) return
  if (isOpen.value) {
    closeDropdown()
  } else {
    searchQuery.value = ''
    isOpen.value = true
    updateMenuPosition()
    await nextTick()
    updateMenuPosition()
    if (showSearch.value && searchInputRef.value) {
      searchInputRef.value.focus()
    }
  }
}

function closeDropdown() {
  isOpen.value = false
  searchQuery.value = ''
}

function selectOption(opt) {
  if (opt.disabled) return
  emit('update:modelValue', opt.value)
  emit('change', opt.value)
  closeDropdown()
}

function handleClickOutside(event) {
  if (!isOpen.value) return
  const target = event.target
  if (
    triggerRef.value?.contains(target) ||
    menuRef.value?.contains(target)
  ) {
    return
  }
  closeDropdown()
}

function handleScrollOrResize(event) {
  if (!isOpen.value) return
  if (menuRef.value && menuRef.value.contains(event.target)) {
    return
  }
  updateMenuPosition()
}

watch(
  () => props.options,
  () => {
    if (isOpen.value) updateMenuPosition()
  }
)

onMounted(() => {
  document.addEventListener('mousedown', handleClickOutside)
  window.addEventListener('resize', handleScrollOrResize, true)
  window.addEventListener('scroll', handleScrollOrResize, true)
})

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', handleClickOutside)
  window.removeEventListener('resize', handleScrollOrResize, true)
  window.removeEventListener('scroll', handleScrollOrResize, true)
})
</script>
