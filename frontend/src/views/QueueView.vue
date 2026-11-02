<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { RouterView } from 'vue-router'
import BulkReviewBar from '@/components/BulkReviewBar.vue'
import ItemTable from '@/components/ItemTable.vue'
import QueueFilters from '@/components/QueueFilters.vue'
import { summarizeBulkReview, useBulkReview } from '@/composables/useBulkReview'
import { useItemSelection } from '@/composables/useItemSelection'
import { useItems } from '@/composables/useItems'
import { DEFAULT_QUEUE_QUERY, useQueueQuery } from '@/composables/useQueueQuery'
import type { ReviewPayload } from '@/types/item'
import { sortableColumnsFor } from '@/utils/queueSort'

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

// Only the very first load replaces the table; later loads (even onto an empty result) keep
// the table and its headers on screen and fade it, so typing a search doesn't flicker.
const hasLoadedItems = ref(false)
watch(isLoading, (loading) => {
  if (!loading && !errorMessage.value) {
    hasLoadedItems.value = true
  }
})
const isFirstLoad = computed(() => isLoading.value && !hasLoadedItems.value)
const hasActiveFilters = computed(
  () =>
    queueQuery.value.status !== DEFAULT_QUEUE_QUERY.status ||
    queueQuery.value.search !== DEFAULT_QUEUE_QUERY.search,
)

function clearFilters(): void {
  updateQueueQuery({ status: DEFAULT_QUEUE_QUERY.status, search: DEFAULT_QUEUE_QUERY.search })
}
</script>

<template>
  <section aria-labelledby="queue-heading">
    <header class="page-header">
      <h1 id="queue-heading">Review queue</h1>
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

    <p v-if="isFirstLoad" class="state" role="status">Loading items…</p>

    <div v-else-if="errorMessage" class="state state--error" role="alert">
      <p>{{ errorMessage }}</p>
      <button type="button" @click="reload">Try again</button>
    </div>

    <div v-else :aria-busy="isLoading" :class="{ refreshing: isLoading }">
      <p class="result-count" role="status">
        {{ items.length }} {{ items.length === 1 ? 'item' : 'items' }}
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
        @sort="updateQueueQuery({ sort: $event })"
        @toggle="selection.toggle"
        @toggle-all="selection.toggleAll"
      >
        <!-- Inside the table, so the headers and sort controls stay in place while searching. -->
        <template #empty>
          <div class="empty-state" role="status">
            <p>No items match these filters.</p>
            <button v-if="hasActiveFilters" type="button" @click="clearFilters">
              Clear filters
            </button>
          </div>
        </template>
      </ItemTable>
    </div>

    <RouterView @reviewed="reload" />
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

.refreshing {
  opacity: 0.6;
  transition: opacity 0.15s;
}
</style>
