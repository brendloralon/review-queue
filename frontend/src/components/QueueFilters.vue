<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, useId, useTemplateRef, watch } from 'vue'
import { STATUS_FILTERS, type QueueQuery, type StatusFilter } from '@/composables/useQueueQuery'

const { query } = defineProps<{ query: QueueQuery }>()
const emit = defineEmits<{ change: [changes: Partial<QueueQuery>] }>()

const STATUS_FILTER_LABELS: Record<StatusFilter, string> = {
  pending: 'Pending',
  reviewed: 'Reviewed',
  approved: 'Approved',
  rejected: 'Rejected',
  all: 'All',
}

const SEARCH_DEBOUNCE_MS = 300

const statusLabelId = useId()
const searchId = useId()
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

/*
 * One highlight sits behind the options and moves to the selected one, so switching tabs
 * slides instead of jumping. Its position is measured because option widths depend on
 * label text and font, which CSS alone can't follow.
 */
const statusFilter = useTemplateRef<HTMLFieldSetElement>('statusFilter')
const indicatorStyle = ref({ width: '0px', transform: 'translateX(0px)' })
const isIndicatorAnimated = ref(false)
let resizeObserver: ResizeObserver | undefined

function moveIndicator(): void {
  const options = statusFilter.value?.querySelectorAll<HTMLElement>('.status-option')
  const selected = options?.[STATUS_FILTERS.indexOf(query.status)]

  if (selected) {
    indicatorStyle.value = {
      width: `${selected.offsetWidth}px`,
      transform: `translateX(${selected.offsetLeft}px)`,
    }
  }
}

watch(
  () => query.status,
  () => nextTick(moveIndicator),
)

onMounted(() => {
  moveIndicator()
  // Enable the slide only after the first position has been rendered, so it doesn't animate in
  // from the left. One frame is not enough: its callback runs before that frame's style update,
  // so the position and the transition would land together and still animate.
  requestAnimationFrame(() => requestAnimationFrame(() => (isIndicatorAnimated.value = true)))

  if (statusFilter.value) {
    resizeObserver = new ResizeObserver(moveIndicator)
    resizeObserver.observe(statusFilter.value)
  }
})

onBeforeUnmount(() => {
  clearTimeout(searchTimer)
  resizeObserver?.disconnect()
})
</script>

<template>
  <form class="filters" role="search" @submit.prevent>
    <div class="field">
      <!-- A <legend> is drawn on the fieldset border, so a separate label matches the search one. -->
      <span :id="statusLabelId" class="field-label">Status</span>
      <fieldset ref="statusFilter" class="status-filter" :aria-labelledby="statusLabelId">
        <span
          class="status-indicator"
          :class="{ 'status-indicator--animated': isIndicatorAnimated }"
          :style="indicatorStyle"
          aria-hidden="true"
        ></span>
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
    </div>

    <div class="field">
      <label :for="searchId" class="field-label">Search</label>
      <input
        :id="searchId"
        v-model="searchText"
        type="search"
        class="search-input"
        placeholder="Title, text or submitter"
        maxlength="100"
        @input="onSearchInput"
      />
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
 * Same height as the search input, so the row lines up. A fixed height can't wrap,
 * so on narrow screens the options scroll sideways instead.
 */
.status-filter {
  position: relative;
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

.status-indicator {
  position: absolute;
  top: 0.1875rem;
  bottom: 0.1875rem;
  left: 0;
  border-radius: calc(var(--radius) - 2px);
  background: var(--color-accent);
}

.status-option {
  position: relative;
  z-index: 1;
  display: flex;
}

@media (prefers-reduced-motion: no-preference) {
  .status-indicator--animated {
    transition:
      transform 220ms ease-out,
      width 220ms ease-out;
  }

  .status-option span {
    transition: color 220ms ease-out;
  }
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

.field-label {
  font-size: var(--font-size-small);
  color: var(--color-text-muted);
}

.search-input {
  min-width: 16rem;
}
</style>
