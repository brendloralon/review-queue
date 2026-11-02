<script setup lang="ts">
import type { SuggestedAction } from '@/types/item'
import { SUGGESTED_ACTION_LABELS } from '@/utils/format'

// The colour follows the backend's suggested action, so risk thresholds live only on the server.
const { score, suggestedAction } = defineProps<{
  score: number
  suggestedAction: SuggestedAction
}>()
</script>

<template>
  <span class="risk" :class="`risk--${suggestedAction}`">
    <strong>{{ score }}</strong>
    <span class="risk__label">{{ SUGGESTED_ACTION_LABELS[suggestedAction] }}</span>
  </span>
</template>

<style scoped>
.risk {
  display: inline-flex;
  align-items: baseline;
  gap: 0.375rem;
  white-space: nowrap;
}

.risk strong {
  min-width: 2ch;
  text-align: right;
}

.risk__label {
  font-size: var(--font-size-small);
}

.risk--approve {
  color: var(--color-success);
}

.risk--review {
  color: var(--color-warning);
}

.risk--reject {
  color: var(--color-danger);
}
</style>
