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

function counterId(field: keyof NewItem): string {
  return `${fieldIds[field]}-counter`
}

function counterDescribedBy(field: keyof NewItem): string {
  return errorFor(field) ? `${errorId(field)} ${counterId(field)}` : counterId(field)
}

// Counts like maxlength (UTF-16 units, an emoji is 2), since that is what stops typing.
// The API counts an emoji as 1, so it never rejects what the counter allowed.
function characterCounter(field: keyof NewItem): string {
  return `${form[field].length}/${MAX_LENGTHS[field]}`
}

async function onSubmit(): Promise<void> {
  const item = await submit({ ...form })

  if (item) {
    // Back to the queue rather than the new item: submitting is done, and newest first puts it on top.
    await router.push({ name: 'queue' })
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
          :aria-describedby="counterDescribedBy('title')"
        />
        <p :id="counterId('title')" class="character-counter">{{ characterCounter('title') }}</p>
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
          :aria-describedby="counterDescribedBy('body')"
        ></textarea>
        <p :id="counterId('body')" class="character-counter">{{ characterCounter('body') }}</p>
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

.character-counter {
  margin: 0;
  color: var(--color-text-muted);
  font-size: var(--font-size-small);
  text-align: right;
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
