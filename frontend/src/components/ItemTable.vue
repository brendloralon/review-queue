<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import StatusBadge from './StatusBadge.vue'
import RiskBadge from './RiskBadge.vue'
import type { Item, ItemSort } from '@/types/item'
import { formatDateTime } from '@/utils/format'
import { nextSort, sortDirectionOf, type SortableColumn } from '@/utils/queueSort'

const {
  items,
  sort,
  sortableColumns,
  selectable = false,
  selectedIds = [],
  loading = false,
} = defineProps<{
  items: Item[]
  sort: ItemSort
  sortableColumns: SortableColumn[]
  selectable?: boolean
  selectedIds?: number[]
  loading?: boolean
}>()
const emit = defineEmits<{ sort: [sort: ItemSort]; toggle: [id: number]; toggleAll: [] }>()

defineSlots<{ empty(): unknown }>()

const SORT_HEADERS: { column: SortableColumn; label: string }[] = [
  { column: 'status', label: 'Status' },
  { column: 'risk', label: 'Risk' },
  { column: 'submitted', label: 'Submitted' },
]

// Title and Submitted by, plus the sortable headers and the optional checkbox column.
const columnCount = computed(() => 2 + SORT_HEADERS.length + (selectable ? 1 : 0))

// Placeholder rows roughly match what is on screen, so the table height barely changes.
const MIN_SKELETON_ROWS = 3
const MAX_SKELETON_ROWS = 10
const skeletonRowCount = computed(() =>
  Math.min(Math.max(items.length, MIN_SKELETON_ROWS), MAX_SKELETON_ROWS),
)

// Varying title lengths keep the placeholders from looking like a grid of identical bars.
function skeletonTitleWidth(row: number): string {
  return `${55 + ((row * 17) % 35)}%`
}

const allSelected = computed(() => items.length > 0 && selectedIds.length === items.length)
const someSelected = computed(() => selectedIds.length > 0 && !allSelected.value)

function isSelected(item: Item): boolean {
  return selectedIds.includes(item.id)
}

const SORT_ARROWS = { ascending: '↑', descending: '↓' } as const

// Links carry the current filters so closing the drawer returns to the same queue view.
const route = useRoute()
</script>

<template>
  <div class="table-scroll">
    <table>
      <caption class="visually-hidden">
        Submitted items
      </caption>
      <!-- Fixed widths so columns don't shift between placeholder rows and real content. -->
      <colgroup>
        <col v-if="selectable" class="column-select" />
        <col />
        <col class="column-submitter" />
        <col class="column-status" />
        <col class="column-risk" />
        <col class="column-submitted" />
      </colgroup>
      <thead>
        <tr>
          <th v-if="selectable" scope="col" class="select-cell">
            <label class="select-target">
              <input
                type="checkbox"
                :checked="allSelected"
                :indeterminate="someSelected"
                :disabled="loading || items.length === 0"
                @change="emit('toggleAll')"
              />
              <span class="visually-hidden">Select all items</span>
            </label>
          </th>
          <th scope="col">Title</th>
          <th scope="col">Submitted by</th>
          <!-- aria-sort sits only on the active column, so screen readers announce the current order. -->
          <th
            v-for="header in SORT_HEADERS"
            :key="header.column"
            scope="col"
            :aria-sort="sortDirectionOf(header.column, sort)"
          >
            <button
              v-if="sortableColumns.includes(header.column)"
              type="button"
              class="sort-button"
              :class="{ 'sort-button--active': sortDirectionOf(header.column, sort) }"
              @click="emit('sort', nextSort(header.column, sort))"
            >
              {{ header.label }}
              <span class="sort-arrow" aria-hidden="true">
                {{ SORT_ARROWS[sortDirectionOf(header.column, sort) ?? 'descending'] }}
              </span>
            </button>
            <template v-else>{{ header.label }}</template>
          </th>
        </tr>
      </thead>
      <tbody v-if="loading" aria-hidden="true">
        <tr v-for="row in skeletonRowCount" :key="row" class="skeleton-row">
          <td v-if="selectable" class="select-cell skeleton-select">
            <span class="skeleton skeleton--checkbox"></span>
          </td>
          <td class="title-cell">
            <span class="skeleton" :style="{ width: skeletonTitleWidth(row) }"></span>
          </td>
          <td><span class="skeleton skeleton--name"></span></td>
          <td><span class="skeleton skeleton--badge"></span></td>
          <td><span class="skeleton skeleton--risk"></span></td>
          <td><span class="skeleton skeleton--date"></span></td>
        </tr>
      </tbody>
      <tbody v-else>
        <tr v-if="items.length === 0">
          <td :colspan="columnCount" class="empty-cell">
            <slot name="empty" />
          </td>
        </tr>
        <tr
          v-for="item in items"
          :key="item.id"
          class="item-row"
          :class="{ 'item-row--selected': selectable && isSelected(item) }"
        >
          <td v-if="selectable" class="select-cell">
            <label class="select-target">
              <input
                type="checkbox"
                :checked="isSelected(item)"
                @change="emit('toggle', item.id)"
              />
              <span class="visually-hidden">Select {{ item.title }}</span>
            </label>
          </td>
          <td class="title-cell">
            <RouterLink
              :to="{ name: 'item', params: { id: item.id }, query: route.query }"
              class="row-link"
            >
              {{ item.title }}
            </RouterLink>
          </td>
          <td>{{ item.submitter_name }}</td>
          <td><StatusBadge :status="item.status" /></td>
          <td><RiskBadge :score="item.risk_score" :suggested-action="item.suggested_action" /></td>
          <td class="date-cell">
            <time :datetime="item.created_at">{{ formatDateTime(item.created_at) }}</time>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.table-scroll {
  overflow-x: auto;
  border: 1px solid var(--color-border);
  border-radius: var(--radius);
  background: var(--color-surface);
}

/*
 * Fixed layout: column widths come from the <col> elements, not from the content, so they stay
 * the same while loading. The title column takes the remaining space; the minimum width keeps
 * it readable and lets narrow screens scroll the table sideways instead.
 */
table {
  width: 100%;
  min-width: 56rem;
  table-layout: fixed;
  border-collapse: collapse;
}

.column-select {
  width: 2.75rem;
}

.column-submitter {
  width: 11rem;
}

.column-status {
  width: 8rem;
}

.column-risk {
  width: 10rem;
}

.column-submitted {
  width: 13rem;
}

/* Taller than the status badge row, so real and placeholder rows are exactly the same height. */
.item-row,
.skeleton-row {
  height: 3.5rem;
}

th,
td {
  padding: 0.75rem 1rem;
  text-align: left;
  border-bottom: 1px solid var(--color-border);
  /* Fixed columns can't grow, so a long unbroken title or name wraps instead of overflowing. */
  overflow-wrap: anywhere;
}

th {
  font-size: var(--font-size-small);
  color: var(--color-text-muted);
  font-weight: 600;
}

.sort-button {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0;
  border: none;
  background: none;
  font-weight: inherit;
  color: inherit;
  white-space: nowrap;
}

.sort-button:hover,
.sort-button--active {
  color: var(--color-heading);
}

/* Inactive columns show a faint arrow so it is clear the header can be clicked. */
.sort-arrow {
  opacity: 0.35;
}

.sort-button--active .sort-arrow {
  opacity: 1;
}

tbody tr:last-child td {
  border-bottom: none;
}

/*
 * The title link is stretched over the whole row (its ::after covers the <tr>), so clicking
 * anywhere opens the drawer. A real link stays the single interactive element, which keeps
 * keyboard, screen reader, middle-click and "open in new tab" behaviour without a JS row handler.
 */
.item-row {
  position: relative;
  cursor: pointer;
}

.item-row:hover {
  background: var(--color-highlight);
}

.item-row--selected {
  background: var(--color-highlight);
}

/*
 * The checkbox label fills its cell and sits above the stretched row link, so clicking near
 * the checkbox selects the row instead of opening the drawer.
 */
.select-cell {
  padding: 0;
}

.select-target {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  padding: 0.75rem 0.5rem 0.75rem 1rem;
  cursor: pointer;
}

.select-target input {
  width: 1.125rem;
  height: 1.125rem;
  margin: 0;
  cursor: pointer;
  accent-color: var(--color-accent);
}

.row-link {
  color: inherit;
  text-decoration: none;
}

.row-link::after {
  content: '';
  position: absolute;
  inset: 0;
}

.row-link:focus-visible {
  outline: none;
}

.row-link:focus-visible::after {
  outline: 2px solid var(--color-focus);
  outline-offset: -2px;
}

.skeleton {
  display: inline-block;
  width: 100%;
  height: 0.875rem;
  vertical-align: middle;
  border-radius: 4px;
  background-color: color-mix(in srgb, var(--color-border) 45%, transparent);
}

.skeleton--checkbox {
  width: 1.125rem;
  height: 1.125rem;
}

.skeleton--name {
  width: 6rem;
}

.skeleton--badge {
  width: 4.5rem;
  height: 1.25rem;
  border-radius: 999px;
}

.skeleton--risk {
  width: 5rem;
}

.skeleton--date {
  width: 7.5rem;
}

/* Same padding as the checkbox label, so the placeholder sits where the real checkbox will. */
.skeleton-select {
  padding: 0.75rem 0.5rem 0.75rem 1rem;
}

@media (prefers-reduced-motion: no-preference) {
  .skeleton {
    background-image: linear-gradient(
      90deg,
      transparent,
      color-mix(in srgb, var(--color-surface) 70%, transparent),
      transparent
    );
    background-size: 50% 100%;
    background-repeat: no-repeat;
    animation: skeleton-shimmer 1.2s ease-in-out infinite;
  }
}

@keyframes skeleton-shimmer {
  from {
    background-position: -50% 0;
  }

  to {
    background-position: 150% 0;
  }
}

.empty-cell {
  padding: 2rem 1rem;
  text-align: center;
  color: var(--color-text-muted);
}

.title-cell {
  font-weight: 600;
}

.date-cell {
  white-space: nowrap;
  color: var(--color-text-muted);
}
</style>
