<script setup lang="ts">
import { computed } from 'vue'
import { RouterView } from 'vue-router'
import ItemTable from '@/components/ItemTable.vue'
import QueueFilters from '@/components/QueueFilters.vue'
import { useItems } from '@/composables/useItems'
import { DEFAULT_QUEUE_QUERY, useQueueQuery } from '@/composables/useQueueQuery'

const { queueQuery, apiParams, updateQueueQuery } = useQueueQuery()
const { items, isLoading, errorMessage, reload } = useItems(apiParams)

const isFirstLoad = computed(() => isLoading.value && items.value.length === 0)
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

    <p v-if="isFirstLoad" class="state" role="status">Loading items…</p>

    <div v-else-if="errorMessage" class="state state--error" role="alert">
      <p>{{ errorMessage }}</p>
      <button type="button" @click="reload">Try again</button>
    </div>

    <div v-else-if="items.length === 0" class="state" role="status">
      <p>No items match these filters.</p>
      <button v-if="hasActiveFilters" type="button" @click="clearFilters">Clear filters</button>
    </div>

    <div v-else :aria-busy="isLoading" :class="{ refreshing: isLoading }">
      <p class="result-count" role="status">
        {{ items.length }} {{ items.length === 1 ? 'item' : 'items' }}
      </p>
      <ItemTable :items="items" />
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

.result-count {
  margin: 0 0 0.5rem;
  font-size: var(--font-size-small);
  color: var(--color-text-muted);
}

.refreshing {
  opacity: 0.6;
  transition: opacity 0.15s;
}
</style>
