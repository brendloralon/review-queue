<script setup lang="ts">
import { onBeforeUnmount, ref, useId, watch } from 'vue'
import {
  STATUS_FILTERS,
  SORT_OPTIONS,
  type QueueQuery,
  type StatusFilter,
} from '@/composables/useQueueQuery'
import type { ItemSort } from '@/types/item'

const { query } = defineProps<{ query: QueueQuery }>()
const emit = defineEmits<{ change: [changes: Partial<QueueQuery>] }>()

const STATUS_FILTER_LABELS: Record<StatusFilter, string> = {
  pending: 'Pending',
  reviewed: 'Reviewed',
  approved: 'Approved',
  rejected: 'Rejected',
  all: 'All',
}

const SORT_LABELS: Record<ItemSort, string> = {
  newest: 'Newest first',
  oldest: 'Oldest first',
  highest_risk: 'Highest risk first',
  lowest_risk: 'Lowest risk first',
  pending_first: 'Pending first',
  pending_last: 'Pending last',
}

const SEARCH_DEBOUNCE_MS = 300

const searchId = useId()
const sortId = useId()
const searchText = ref(query.search)
let searchTimer: ReturnType<typeof setTimeout> | undefined

// Keep the input in sync when the URL changes from outside (back button, "Clear filters").
watch(
  () => query.search,
  (search) => {
    if (search !== searchText.value) {
      searchText.value = search
    }
  },
)

function onSearchInput(): void {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => emit('change', { search: searchText.value }), SEARCH_DEBOUNCE_MS)
}

function onSortChange(event: Event): void {
  emit('change', { sort: (event.target as HTMLSelectElement).value as ItemSort })
}

onBeforeUnmount(() => clearTimeout(searchTimer))
</script>

<template>
  <form class="filters" role="search" @submit.prevent>
    <fieldset class="status-filter">
      <legend class="visually-hidden">Status</legend>
      <label v-for="status in STATUS_FILTERS" :key="status" class="status-option">
        <input
          type="radio"
          name="status"
          :value="status"
          :checked="query.status === status"
          @change="emit('change', { status })"
        />
        <span>{{ STATUS_FILTER_LABELS[status] }}</span>
      </label>
    </fieldset>

    <div class="field">
      <label :for="searchId">Search</label>
      <input
        :id="searchId"
        v-model="searchText"
        type="search"
        placeholder="Title, text or submitter"
        maxlength="100"
        @input="onSearchInput"
      />
    </div>

    <div class="field">
      <label :for="sortId">Sort</label>
      <select :id="sortId" :value="query.sort" @change="onSortChange">
        <option v-for="sort in SORT_OPTIONS" :key="sort" :value="sort">
          {{ SORT_LABELS[sort] }}
        </option>
      </select>
    </div>
  </form>
</template>

<style scoped>
.filters {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

/*
 * Same height as the search input and sort select, so the row lines up. A fixed height can't wrap,
 * so on narrow screens the options scroll sideways instead.
 */
.status-filter {
  display: flex;
  max-width: 100%;
  overflow-x: auto;
  height: var(--control-height);
  border: 1px solid var(--color-border);
  border-radius: var(--radius);
  padding: 0.1875rem;
  margin: 0;
  background: var(--color-surface);
}

.status-option {
  position: relative;
  display: flex;
}

.status-option input {
  position: absolute;
  opacity: 0;
}

.status-option span {
  display: flex;
  align-items: center;
  padding: 0 0.75rem;
  border-radius: calc(var(--radius) - 2px);
  cursor: pointer;
}

.status-option input:checked + span {
  background: var(--color-accent);
  color: var(--color-on-accent);
}

.status-option input:focus-visible + span {
  outline: 2px solid var(--color-focus);
  outline-offset: 2px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.field label {
  font-size: var(--font-size-small);
  color: var(--color-text-muted);
}

.field input {
  min-width: 16rem;
}
</style>
