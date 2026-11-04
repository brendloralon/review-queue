<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, useTemplateRef } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ReviewForm from '@/components/ReviewForm.vue'
import RiskBadge from '@/components/RiskBadge.vue'
import StatusBadge from '@/components/StatusBadge.vue'
import { useItem } from '@/composables/useItem'
import type { ReviewPayload } from '@/types/item'
import { formatDateTime, formatFlag } from '@/utils/format'

const { id } = defineProps<{ id: number }>()
const emit = defineEmits<{ reviewed: [] }>()

const route = useRoute()
const router = useRouter()
const { item, isLoading, errorMessage, isReviewing, reviewErrorMessage, reload, review } = useItem(
  () => id,
)

// A native modal <dialog> makes the rest of the page inert (a real focus trap) and
// fires "cancel" on Esc, so no custom focus-trap code is needed.
const dialog = useTemplateRef<HTMLDialogElement>('dialog')
let previouslyFocused: HTMLElement | null = null

onMounted(() => {
  previouslyFocused = document.activeElement instanceof HTMLElement ? document.activeElement : null
  dialog.value?.showModal()
})

onBeforeUnmount(() => {
  dialog.value?.close()
  if (previouslyFocused?.isConnected) {
    previouslyFocused.focus()
  }
})

const isClosing = ref(false)

async function close(): Promise<void> {
  if (isClosing.value) {
    return
  }

  isClosing.value = true
  await nextTick()
  await waitForAnimations()
  // Keep the queue's filters when returning to it.
  await router.push({ name: 'queue', query: route.query })
}

// Navigating away unmounts the dialog at once, so the slide-out has to finish first.
// With reduced motion there are no animations and this resolves immediately.
async function waitForAnimations(): Promise<void> {
  const animations = dialog.value?.getAnimations() ?? []
  await Promise.allSettled(animations.map((animation) => animation.finished))
}

function onBackdropClick(event: MouseEvent): void {
  if (event.target === dialog.value) {
    close()
  }
}

// A decided item leaves the reviewer's current tab, so the drawer closes on success and the
// queue refreshes. On failure it stays open so the error (or the other reviewer's decision) shows.
async function onReview(payload: ReviewPayload): Promise<void> {
  const isReviewed = await review(payload)
  emit('reviewed')

  if (isReviewed) {
    await close()
  }
}
</script>

<template>
  <dialog
    ref="dialog"
    class="drawer"
    :class="{ 'drawer--closing': isClosing }"
    aria-labelledby="item-detail-heading"
    @cancel.prevent="close"
    @click="onBackdropClick"
  >
    <div class="drawer__panel">
      <header class="drawer__header">
        <h2 id="item-detail-heading">{{ item?.title ?? 'Item details' }}</h2>
        <button type="button" class="close" aria-label="Close details" @click="close">✕</button>
      </header>

      <p v-if="isLoading && !item" role="status">Loading item…</p>

      <div v-else-if="errorMessage && !item" class="error-state" role="alert">
        <p>{{ errorMessage }}</p>
        <button type="button" @click="reload">Try again</button>
      </div>

      <template v-else-if="item">
        <dl class="meta">
          <div>
            <dt>Submitted by</dt>
            <dd>{{ item.submitter_name }}</dd>
          </div>
          <div>
            <dt>Submitted</dt>
            <dd>
              <time :datetime="item.created_at">{{ formatDateTime(item.created_at) }}</time>
            </dd>
          </div>
          <div>
            <dt>Status</dt>
            <dd><StatusBadge :status="item.status" /></dd>
          </div>
          <div>
            <dt>Risk</dt>
            <dd>
              <RiskBadge :score="item.risk_score" :suggested-action="item.suggested_action" />
            </dd>
          </div>
        </dl>

        <section aria-labelledby="flags-heading" class="section">
          <h3 id="flags-heading">Moderation flags</h3>
          <ul v-if="item.flags.length > 0" class="flags">
            <li v-for="flag in item.flags" :key="flag">{{ formatFlag(flag) }}</li>
          </ul>
          <p v-else class="muted">No flags raised.</p>
        </section>

        <section aria-labelledby="body-heading" class="section">
          <h3 id="body-heading">Content</h3>
          <p class="body">{{ item.body }}</p>
        </section>

        <section class="section">
          <ReviewForm
            v-if="item.status === 'pending'"
            :suggested-action="item.suggested_action"
            :is-submitting="isReviewing"
            :error-message="reviewErrorMessage"
            @submit="onReview"
          />

          <div v-else>
            <h3>Review</h3>
            <p v-if="reviewErrorMessage" class="error" role="alert">{{ reviewErrorMessage }}</p>
            <p>
              <StatusBadge :status="item.status" />
              <span v-if="item.reviewed_at" class="muted">
                on
                <time :datetime="item.reviewed_at">{{ formatDateTime(item.reviewed_at) }}</time>
              </span>
            </p>
            <p v-if="item.review_note" class="body">{{ item.review_note }}</p>
            <p v-else class="muted">No note left.</p>
          </div>
        </section>
      </template>
    </div>
  </dialog>
</template>

<style scoped>
.drawer {
  margin: 0 0 0 auto;
  padding: 0;
  width: min(34rem, 100vw);
  max-width: 100vw;
  height: 100dvh;
  max-height: 100dvh;
  border: none;
  box-shadow: var(--shadow-panel);
  background: var(--color-surface);
  color: var(--color-text);
}

.drawer::backdrop {
  background: var(--color-overlay);
}

@media (prefers-reduced-motion: no-preference) {
  .drawer[open] {
    animation: drawer-slide-in 220ms ease-out;
  }

  .drawer[open]::backdrop {
    animation: backdrop-fade-in 220ms ease-out;
  }

  .drawer--closing[open] {
    animation: drawer-slide-out 180ms ease-in forwards;
  }

  .drawer--closing[open]::backdrop {
    animation: backdrop-fade-out 180ms ease-in forwards;
  }
}

@keyframes drawer-slide-in {
  from {
    transform: translateX(100%);
  }
}

@keyframes drawer-slide-out {
  to {
    transform: translateX(100%);
  }
}

@keyframes backdrop-fade-in {
  from {
    opacity: 0;
  }
}

@keyframes backdrop-fade-out {
  to {
    opacity: 0;
  }
}

.drawer__panel {
  height: 100%;
  overflow-y: auto;
  padding: 1.25rem 1.5rem 2rem;
}

.drawer__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
}

.close {
  flex-shrink: 0;
  padding: 0.25rem 0.625rem;
}

.meta {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem 1rem;
  margin: 0;
}

.meta dt {
  font-size: var(--font-size-small);
  color: var(--color-text-muted);
}

.meta dd {
  margin: 0;
}

.section {
  margin-top: 1.5rem;
}

.section h3 {
  font-size: 1rem;
  margin-bottom: 0.5rem;
}

.flags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin: 0;
  padding: 0;
  list-style: none;
}

.flags li {
  padding: 0.125rem 0.5rem;
  border-radius: var(--radius-pill);
  background: var(--color-background);
  border: 1px solid var(--color-border);
  font-size: var(--font-size-small);
}

.body {
  white-space: pre-wrap;
  overflow-wrap: anywhere;
  margin: 0;
}

.muted {
  color: var(--color-text-muted);
}

.error,
.error-state {
  color: var(--color-danger);
}
</style>
