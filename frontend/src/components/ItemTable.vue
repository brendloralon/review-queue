<script setup lang="ts">
import { RouterLink, useRoute } from 'vue-router'
import StatusBadge from './StatusBadge.vue'
import RiskBadge from './RiskBadge.vue'
import type { Item } from '@/types/item'
import { formatDateTime } from '@/utils/format'

const { items } = defineProps<{ items: Item[] }>()

// Links carry the current filters so closing the drawer returns to the same queue view.
const route = useRoute()
</script>

<template>
  <div class="table-scroll">
    <table>
      <caption class="visually-hidden">
        Submitted items
      </caption>
      <thead>
        <tr>
          <th scope="col">Title</th>
          <th scope="col">Submitted by</th>
          <th scope="col">Status</th>
          <th scope="col">Risk</th>
          <th scope="col">Submitted</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in items" :key="item.id" class="item-row">
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

table {
  width: 100%;
  border-collapse: collapse;
}

th,
td {
  padding: 0.75rem 1rem;
  text-align: left;
  border-bottom: 1px solid var(--color-border);
}

th {
  font-size: var(--font-size-small);
  color: var(--color-text-muted);
  font-weight: 600;
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
  background: var(--color-background);
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

.title-cell {
  font-weight: 600;
  min-width: 14rem;
}

.date-cell {
  white-space: nowrap;
  color: var(--color-text-muted);
}
</style>
