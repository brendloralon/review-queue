<script setup lang="ts">
import { reactive, useId } from 'vue'
import { useRouter } from 'vue-router'
import { useSubmitItem } from '@/composables/useSubmitItem'
import type { NewItem } from '@/types/item'
// Used as maxlength too, so users hit the limits while typing, not on submit.
import { NEW_ITEM_MAX_LENGTHS as MAX_LENGTHS } from '@/utils/inputValidation'

const router = useRouter()
const { isSubmitting, errorMessage, fieldErrors, submit } = useSubmitItem()

const form = reactive<NewItem>({ title: '', body: '', submitter_name: '' })
const fieldIds: Record<keyof NewItem, string> = {
  title: useId(),
  body: useId(),
  submitter_name: useId(),
}

function errorFor(field: keyof NewItem): string | undefined {
  return fieldErrors.value[field]?.[0]
}

function errorId(field: keyof NewItem): string {
  return `${fieldIds[field]}-error`
}

async function onSubmit(): Promise<void> {
  const item = await submit({ ...form })

  if (item) {
    // Open the new item over the queue so the stored moderation result is visible right away.
    await router.push({ name: 'item', params: { id: item.id } })
  }
}
</script>

<template>
  <section aria-labelledby="submit-heading" class="submit">
    <h1 id="submit-heading">Submit an item</h1>
    <p class="intro">Items are checked automatically and then wait in the queue for a reviewer.</p>

    <form novalidate @submit.prevent="onSubmit">
      <div class="field">
        <label :for="fieldIds.title">Title</label>
        <input
          :id="fieldIds.title"
          v-model="form.title"
          type="text"
          required
          :maxlength="MAX_LENGTHS.title"
          :aria-invalid="errorFor('title') ? 'true' : undefined"
          :aria-describedby="errorFor('title') ? errorId('title') : undefined"
        />
        <p v-if="errorFor('title')" :id="errorId('title')" class="field-error">
          {{ errorFor('title') }}
        </p>
      </div>

      <div class="field">
        <label :for="fieldIds.body">Content</label>
        <textarea
          :id="fieldIds.body"
          v-model="form.body"
          rows="8"
          required
          :maxlength="MAX_LENGTHS.body"
          :aria-invalid="errorFor('body') ? 'true' : undefined"
          :aria-describedby="errorFor('body') ? errorId('body') : undefined"
        ></textarea>
        <p v-if="errorFor('body')" :id="errorId('body')" class="field-error">
          {{ errorFor('body') }}
        </p>
      </div>

      <div class="field">
        <label :for="fieldIds.submitter_name">Your name</label>
        <input
          :id="fieldIds.submitter_name"
          v-model="form.submitter_name"
          type="text"
          autocomplete="name"
          required
          :maxlength="MAX_LENGTHS.submitter_name"
          :aria-invalid="errorFor('submitter_name') ? 'true' : undefined"
          :aria-describedby="errorFor('submitter_name') ? errorId('submitter_name') : undefined"
        />
        <p v-if="errorFor('submitter_name')" :id="errorId('submitter_name')" class="field-error">
          {{ errorFor('submitter_name') }}
        </p>
      </div>

      <p v-if="errorMessage" class="form-error" role="alert">{{ errorMessage }}</p>

      <button type="submit" class="primary" :disabled="isSubmitting">
        {{ isSubmitting ? 'Submitting…' : 'Submit item' }}
      </button>
    </form>
  </section>
</template>

<style scoped>
.submit {
  max-width: 40rem;
}

.intro {
  color: var(--color-text-muted);
  margin: 0.5rem 0 1.5rem;
}

form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.field label {
  font-weight: 600;
}

textarea {
  resize: vertical;
}

[aria-invalid='true'] {
  border-color: var(--color-danger);
}

.field-error,
.form-error {
  margin: 0;
  color: var(--color-danger);
  font-size: var(--font-size-small);
}

.primary {
  align-self: flex-start;
  background: var(--color-accent);
  border-color: var(--color-accent);
  color: var(--color-on-accent);
}
</style>
