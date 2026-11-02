<script setup lang="ts">
import { ref, useId } from 'vue'
import type { ReviewDecision, ReviewPayload, SuggestedAction } from '@/types/item'
import { SUGGESTED_ACTION_LABELS } from '@/utils/format'

const { suggestedAction, isSubmitting, errorMessage } = defineProps<{
  suggestedAction: SuggestedAction
  isSubmitting: boolean
  errorMessage: string | null
}>()
const emit = defineEmits<{ submit: [payload: ReviewPayload] }>()

const NOTE_MAX_LENGTH = 1000

const noteId = useId()
const note = ref('')

function submit(decision: ReviewDecision): void {
  // Blank notes are sent as-is; the API stores whitespace-only notes as null.
  emit('submit', { status: decision, note: note.value === '' ? null : note.value })
}
</script>

<template>
  <form class="review-form" @submit.prevent>
    <h3>Your decision</h3>
    <p class="suggestion">
      Suggested: <strong>{{ SUGGESTED_ACTION_LABELS[suggestedAction] }}</strong>
    </p>

    <label :for="noteId">Note (optional)</label>
    <textarea
      :id="noteId"
      v-model="note"
      rows="3"
      :maxlength="NOTE_MAX_LENGTH"
      :disabled="isSubmitting"
    ></textarea>

    <p v-if="errorMessage" class="error" role="alert">{{ errorMessage }}</p>

    <div class="actions">
      <button type="button" class="approve" :disabled="isSubmitting" @click="submit('approved')">
        Approve
      </button>
      <button type="button" class="reject" :disabled="isSubmitting" @click="submit('rejected')">
        Reject
      </button>
    </div>
  </form>
</template>

<style scoped>
.review-form {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.suggestion {
  margin: 0;
  color: var(--color-text-muted);
}

label {
  font-size: var(--font-size-small);
  color: var(--color-text-muted);
}

textarea {
  resize: vertical;
}

.error {
  margin: 0;
  color: var(--color-danger);
}

.actions {
  display: flex;
  gap: 0.75rem;
}

.approve {
  background: var(--color-success);
  border-color: var(--color-success);
  color: var(--color-on-accent);
}

.reject {
  background: var(--color-danger);
  border-color: var(--color-danger);
  color: var(--color-on-accent);
}
</style>
