<script setup lang="ts">
import { computed, ref, useId, watch } from 'vue'
import type { ReviewDecision, ReviewPayload } from '@/types/item'

const { selectedCount, isSubmitting, errorMessage } = defineProps<{
  selectedCount: number
  isSubmitting: boolean
  errorMessage: string | null
}>()
const emit = defineEmits<{ submit: [payload: ReviewPayload]; clear: [] }>()

const NOTE_MAX_LENGTH = 1000

const noteId = useId()
const note = ref('')
const isDisabled = computed(() => selectedCount === 0 || isSubmitting)

// A finished or abandoned batch starts the next one with an empty note.
watch(
  () => selectedCount,
  (count) => {
    if (count === 0) {
      note.value = ''
    }
  },
)

function submit(decision: ReviewDecision): void {
  emit('submit', { status: decision, note: note.value === '' ? null : note.value })
}
</script>

<template>
  <form class="bulk-bar" aria-label="Review selected items" @submit.prevent>
    <p class="count" aria-live="polite">{{ selectedCount }} selected</p>

    <div class="note">
      <label :for="noteId">Note for all (optional)</label>
      <input
        :id="noteId"
        v-model="note"
        type="text"
        :maxlength="NOTE_MAX_LENGTH"
        :disabled="isDisabled"
      />
    </div>

    <div class="actions">
      <button type="button" class="approve" :disabled="isDisabled" @click="submit('approved')">
        Approve selected
      </button>
      <button type="button" class="reject" :disabled="isDisabled" @click="submit('rejected')">
        Reject selected
      </button>
      <button type="button" :disabled="isDisabled" @click="emit('clear')">Clear</button>
    </div>

    <p v-if="errorMessage" class="error" role="alert">{{ errorMessage }}</p>
  </form>
</template>

<style scoped>
.bulk-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: 0.75rem 1rem;
  margin-bottom: 0.75rem;
  padding: 0.75rem 1rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius);
  background: var(--color-highlight);
}

.count {
  margin: 0;
  min-width: 6rem;
  align-self: center;
  font-weight: 600;
  color: var(--color-heading);
}

.note {
  display: flex;
  flex: 1 1 14rem;
  flex-direction: column;
  gap: 0.25rem;
}

.note label {
  font-size: var(--font-size-small);
  color: var(--color-text-muted);
}

.actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.actions button {
  height: var(--control-height);
}

.approve:not(:disabled) {
  background: var(--color-success);
  border-color: var(--color-success);
  color: var(--color-on-accent);
}

.reject:not(:disabled) {
  background: var(--color-danger);
  border-color: var(--color-danger);
  color: var(--color-on-accent);
}

.error {
  flex-basis: 100%;
  margin: 0;
  color: var(--color-danger);
}
</style>
