<template>
  <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
    <!-- Top Bar: Page Size Selector (5, 10, 25, 50, 100, Semua) + Custom Toolbar Slots -->
    <div class="px-5 py-3.5 bg-slate-50/70 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3">
      <div class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2 text-xs font-bold text-slate-600">
          <span>Tampilkan</span>
          <div class="w-28">
            <AppSelect
              v-model="pageSize"
              size="xs"
              :options="pageSizeOptions"
              @change="currentPage = 1"
            />
          </div>
          <span>data</span>
        </div>

        <slot name="toolbar-left" />
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <slot name="toolbar-right" />
      </div>
    </div>

    <!-- Table Wrapper -->
    <div class="overflow-x-auto">
      <table class="w-full text-sm text-left border-collapse">
        <thead>
          <tr class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 border-b border-slate-200">
            <th
              v-for="col in columns"
              :key="col.key"
              class="px-4 py-3.5 font-extrabold select-none"
              :class="[
                col.align === 'center' ? 'text-center' : col.align === 'right' ? 'text-right' : 'text-left',
                col.headerClass || '',
              ]"
            >
              <button
                v-if="col.sortable !== false"
                type="button"
                class="inline-flex items-center gap-1.5 hover:text-indigo-600 transition group"
                :class="{
                  'text-indigo-600': sortKey === col.key,
                  'mx-auto': col.align === 'center',
                  'ml-auto': col.align === 'right',
                }"
                @click="toggleSort(col.key)"
              >
                <span>{{ col.label }}</span>
                <ArrowUp
                  v-if="sortKey === col.key && sortOrder === 'asc'"
                  class="w-3.5 h-3.5 text-indigo-600 shrink-0"
                />
                <ArrowDown
                  v-else-if="sortKey === col.key && sortOrder === 'desc'"
                  class="w-3.5 h-3.5 text-indigo-600 shrink-0"
                />
                <ArrowUpDown
                  v-else
                  class="w-3.5 h-3.5 text-slate-300 group-hover:text-indigo-400 shrink-0"
                />
              </button>
              <span v-else>{{ col.label }}</span>
            </th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-100">
          <tr v-if="paginatedRows.length === 0">
            <td :colspan="columns.length" class="px-6 py-10 text-center text-slate-500">
              <p class="font-bold text-slate-700">{{ emptyText }}</p>
            </td>
          </tr>

          <tr
            v-for="(row, rowIdx) in paginatedRows"
            :key="row[rowKey] ?? rowIdx"
            class="hover:bg-slate-50/90 transition-colors"
          >
            <td
              v-for="col in columns"
              :key="col.key"
              class="px-4 py-3.5"
              :class="[
                col.align === 'center' ? 'text-center' : col.align === 'right' ? 'text-right' : 'text-left',
                col.cellClass || '',
              ]"
            >
              <slot
                :name="`cell-${col.key}`"
                :row="row"
                :value="extractValue(row, col)"
                :index="(currentPage - 1) * numericPageSize + rowIdx"
              >
                {{ extractValue(row, col) ?? '-' }}
              </slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Bottom Pagination Bar -->
    <div class="px-5 py-3.5 bg-slate-50/70 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-600">
      <div class="font-semibold">
        Menampilkan
        <strong class="text-slate-900">{{ startItemIndex }}</strong>–<strong class="text-slate-900">{{ endItemIndex }}</strong>
        dari <strong class="text-indigo-600">{{ sortedRows.length }}</strong> data
      </div>

      <div v-if="totalPages > 1" class="flex items-center gap-1.5">
        <button
          type="button"
          :disabled="currentPage <= 1"
          class="min-h-[34px] px-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 disabled:opacity-40 font-bold text-slate-700 flex items-center gap-1 transition"
          @click="currentPage--"
        >
          <ChevronLeft class="w-3.5 h-3.5" />
          <span>Sebelumnya</span>
        </button>

        <button
          v-for="page in visiblePageNumbers"
          :key="page"
          type="button"
          class="min-h-[34px] min-w-[34px] px-2.5 rounded-xl font-extrabold transition"
          :class="
            currentPage === page
              ? 'bg-indigo-600 text-white shadow-xs'
              : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'
          "
          @click="currentPage = page"
        >
          {{ page }}
        </button>

        <button
          type="button"
          :disabled="currentPage >= totalPages"
          class="min-h-[34px] px-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 disabled:opacity-40 font-bold text-slate-700 flex items-center gap-1 transition"
          @click="currentPage++"
        >
          <span>Berikutnya</span>
          <ChevronRight class="w-3.5 h-3.5" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import {
  ArrowDown,
  ArrowUp,
  ArrowUpDown,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next'
import AppSelect from '@/components/AppSelect.vue'

const props = defineProps({
  rows: {
    type: Array,
    default: () => [],
  },
  columns: {
    type: Array,
    default: () => [],
  },
  rowKey: {
    type: String,
    default: 'id',
  },
  defaultPageSize: {
    type: [Number, String],
    default: 10,
  },
  emptyText: {
    type: String,
    default: 'Tidak ada data yang ditemukan.',
  },
})

const pageSizeOptions = [
  { label: '5 baris', value: 5 },
  { label: '10 baris', value: 10 },
  { label: '25 baris', value: 25 },
  { label: '50 baris', value: 50 },
  { label: '100 baris', value: 100 },
  { label: 'Semua', value: 'all' },
]

const pageSize = ref(props.defaultPageSize)
const currentPage = ref(1)
const sortKey = ref('')
const sortOrder = ref('asc')

function extractValue(row, col) {
  if (typeof col.accessor === 'function') {
    return col.accessor(row)
  }
  return row?.[col.key]
}

function toggleSort(key) {
  if (sortKey.value !== key) {
    sortKey.value = key
    sortOrder.value = 'asc'
  } else if (sortOrder.value === 'asc') {
    sortOrder.value = 'desc'
  } else {
    sortKey.value = ''
    sortOrder.value = 'asc'
  }
}

const sortedRows = computed(() => {
  if (!sortKey.value) return props.rows

  const activeCol = props.columns.find((c) => c.key === sortKey.value)
  if (!activeCol) return props.rows

  return [...props.rows].sort((a, b) => {
    const va = extractValue(a, activeCol)
    const vb = extractValue(b, activeCol)

    if (va === null || va === undefined || va === '-') return 1
    if (vb === null || vb === undefined || vb === '-') return -1

    const numA = Number(va)
    const numB = Number(vb)
    if (!Number.isNaN(numA) && !Number.isNaN(numB) && String(va).trim() !== '' && String(vb).trim() !== '') {
      return sortOrder.value === 'asc' ? numA - numB : numB - numA
    }

    const cmp = String(va).localeCompare(String(vb), 'id', { numeric: true, sensitivity: 'base' })
    return sortOrder.value === 'asc' ? cmp : -cmp
  })
})

const numericPageSize = computed(() =>
  pageSize.value === 'all'
    ? Math.max(1, sortedRows.value.length)
    : Number(pageSize.value) || 10
)

const totalPages = computed(() =>
  pageSize.value === 'all'
    ? 1
    : Math.max(1, Math.ceil(sortedRows.value.length / numericPageSize.value))
)

watch(totalPages, (maxPage) => {
  if (currentPage.value > maxPage) {
    currentPage.value = maxPage
  }
})

const paginatedRows = computed(() => {
  if (pageSize.value === 'all') return sortedRows.value
  const start = (currentPage.value - 1) * numericPageSize.value
  return sortedRows.value.slice(start, start + numericPageSize.value)
})

const startItemIndex = computed(() =>
  sortedRows.value.length === 0 ? 0 : (currentPage.value - 1) * numericPageSize.value + 1
)

const endItemIndex = computed(() =>
  Math.min(sortedRows.value.length, currentPage.value * numericPageSize.value)
)

const visiblePageNumbers = computed(() => {
  const total = totalPages.value
  const cur = currentPage.value
  const delta = 2
  const pages = []
  for (let i = Math.max(1, cur - delta); i <= Math.min(total, cur + delta); i++) {
    pages.push(i)
  }
  return pages
})
</script>
