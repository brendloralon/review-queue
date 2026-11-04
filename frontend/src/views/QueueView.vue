<script setup lang="ts">
import { computed, useTemplateRef, watch } from 'vue'
import { RouterView } from 'vue-router'
import BulkReviewBar from '@/components/BulkReviewBar.vue'
import ItemTable from '@/components/ItemTable.vue'
import QueueFilters from '@/components/QueueFilters.vue'
import { summarizeBulkReview, useBulkReview } from '@/composables/useBulkReview'
import { useItemSelection } from '@/composables/useItemSelection'
import { useItems } from '@/composables/useItems'
import { DEFAULT_QUEUE_QUERY, useQueueQuery, type StatusFilter } from '@/composables/useQueueQuery'
import type { ReviewPayload } from '@/types/item'
import { sortableColumnsFor } from '@/utils/queueSort'

const heading = useTemplateRef<HTMLHeadingElement>('heading')
const { queueQuery, apiParams, updateQueueQuery } = useQueueQuery()
const { items, isLoading, errorMessage, reload } = useItems(apiParams)

const sortableColumns = computed(() => sortableColumnsFor(queueQuery.value.status))

// Bulk review is a triage tool, so it lives on the Pending tab, where every row can be reviewed.
const isBulkReviewAvailable = computed(() => queueQuery.value.status === 'pending')
const selectableIds = computed(() =>
  isBulkReviewAvailable.value ? items.value.map((item) => item.id) : [],
)
const selection = useItemSelection(selectableIds)
const bulkReview = useBulkReview()
const bulkReviewResult = bulkReview.result

watch(() => queueQuery.value.status, bulkReview.reset)

async function onBulkReview(payload: ReviewPayload): Promise<void> {
  const result = await bulkReview.submit({ ...payload, ids: selection.selectedIds.value })

  if (result) {
    selection.clear()
    await reload()
  }
}

const hasActiveFilters = computed(
  () =>
    queueQuery.value.status !== DEFAULT_QUEUE_QUERY.status ||
    queueQuery.value.search !== DEFAULT_QUEUE_QUERY.search,
)

const EMPTY_TAB_MESSAGES: Record<StatusFilter, string> = {
  pending: 'No pending items.',
  reviewed: 'No reviewed items.',
  approved: 'No approved items.',
  rejected: 'No rejected items.',
  all: 'No items yet.',
}

// Without a search, an empty tab is a real state (e.g. the queue is cleared), not a filter miss.
const emptyMessage = computed(() =>
  queueQuery.value.search === ''
    ? EMPTY_TAB_MESSAGES[queueQuery.value.status]
    : 'No items match these filters.',
)

function clearFilters(): void {
  updateQueueQuery({ status: DEFAULT_QUEUE_QUERY.status, search: DEFAULT_QUEUE_QUERY.search })
}
</script>

<template>
  <section aria-labelledby="queue-heading">
    <header class="page-header">
      <!-- tabindex="-1" lets the drawer return focus here when the row that opened it is gone. -->
      <h1 id="queue-heading" ref="heading" tabindex="-1">Review queue</h1>
    </header>

    <QueueFilters :query="queueQuery" @change="updateQueueQuery" />

    <!-- Outside the list states, so the summary stays visible when a batch empties the queue. -->
    <div role="status">
      <div v-if="bulkReviewResult" class="bulk-summary">
        <p>{{ summarizeBulkReview(bulkReviewResult) }}</p>
        <ul v-if="bulkReviewResult.failed.length > 0">
          <li v-for="failure in bulkReviewResult.failed" :key="failure.id">
            {{ failure.message }}
          </li>
        </ul>
      </div>
    </div>

    <div v-if="errorMessage" class="state state--error" role="alert">
      <p>{{ errorMessage }}</p>
      <button type="button" @click="reload">Try again</button>
    </div>

    <!-- Loading shows placeholder rows inside the table, so the headers and layout never jump. -->
    <div v-else :aria-busy="isLoading">
      <p class="result-count" role="status">
        <template v-if="isLoading">Loading items…</template>
        <template v-else>{{ items.length }} {{ items.length === 1 ? 'item' : 'items' }}</template>
      </p>
      <BulkReviewBar
        v-if="isBulkReviewAvailable"
        :selected-count="selection.selectedIds.value.length"
        :is-submitting="bulkReview.isSubmitting.value"
        :error-message="bulkReview.errorMessage.value"
        @submit="onBulkReview"
        @clear="selection.clear"
      />
      <ItemTable
        :items="items"
        :sort="queueQuery.sort"
        :sortable-columns="sortableColumns"
        :selectable="isBulkReviewAvailable"
        :selected-ids="selection.selectedIds.value"
        :loading="isLoading"
        @sort="updateQueueQuery({ sort: $event })"
        @toggle="selection.toggle"
        @toggle-all="selection.toggleAll"
      >
        <!-- Inside the table, so the headers and sort controls stay in place while searching. -->
        <template #empty>
          <div class="empty-state" role="status">
            <p>{{ emptyMessage }}</p>
            <button v-if="hasActiveFilters" type="button" @click="clearFilters">
              Clear filters
            </button>
          </div>
        </template>
      </ItemTable>
    </div>

    <RouterView :fallback-focus-target="heading" @reviewed="reload" />
  </section>
</template>

<style scoped>
.page-header {
  margin-bottom: 1.25rem;
}

.state {
  padding: 2rem;
  text-align: center;
  border: 1px dashed var(--color-border);
  border-radius: var(--radius);
  color: var(--color-text-muted);
}

.state button {
  margin-top: 0.75rem;
}

.state--error {
  border-style: solid;
  border-color: var(--color-danger);
  color: var(--color-danger);
}

.empty-state p {
  margin: 0;
}

.empty-state button {
  margin-top: 0.75rem;
}

.result-count {
  margin: 0 0 0.5rem;
  font-size: var(--font-size-small);
  color: var(--color-text-muted);
}

.bulk-summary {
  margin-bottom: 1rem;
  padding: 0.75rem 1rem;
  border-left: 4px solid var(--color-accent);
  border-radius: var(--radius);
  background: var(--color-surface);
}

.bulk-summary p,
.bulk-summary ul {
  margin: 0;
}

.bulk-summary ul {
  margin-top: 0.25rem;
  padding-left: 1.25rem;
  color: var(--color-danger);
}
</style>
