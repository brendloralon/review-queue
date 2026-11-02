<script setup lang="ts">
import type { ItemStatus } from '@/types/item'
import { STATUS_LABELS } from '@/utils/format'

const { status } = defineProps<{ status: ItemStatus }>()
</script>

<template>
  <span class="badge" :class="`badge--${status}`">{{ STATUS_LABELS[status] }}</span>
</template>

<style scoped>
.badge {
  display: inline-block;
  padding: 0.125rem 0.5rem;
  border: 1px solid currentColor;
  border-radius: var(--radius-pill);
  font-size: var(--font-size-small);
  font-weight: 600;
  white-space: nowrap;
}

.badge--pending {
  color: var(--color-warning);
}

.badge--approved {
  color: var(--color-success);
}

.badge--rejected {
  color: var(--color-danger);
}
</style>
