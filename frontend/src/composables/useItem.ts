import { onScopeDispose, ref, toValue, watch, type MaybeRefOrGetter, type Ref } from 'vue'
import { ApiError, isAbortError } from '@/api/client'
import { getItem, reviewItem } from '@/api/items'
import type { Item, ReviewPayload } from '@/types/item'

export interface UseItem {
  item: Ref<Item | null>
  isLoading: Ref<boolean>
  errorMessage: Ref<string | null>
  isReviewing: Ref<boolean>
  reviewErrorMessage: Ref<string | null>
  reload: () => Promise<void>
  review: (payload: ReviewPayload) => Promise<boolean>
}

export function useItem(id: MaybeRefOrGetter<number>): UseItem {
  const item = ref<Item | null>(null)
  const isLoading = ref(false)
  const errorMessage = ref<string | null>(null)
  const isReviewing = ref(false)
  const reviewErrorMessage = ref<string | null>(null)
  let activeRequest: AbortController | null = null

  async function reload(): Promise<void> {
    activeRequest?.abort()
    const request = new AbortController()
    activeRequest = request

    isLoading.value = true
    errorMessage.value = null

    try {
      item.value = await getItem(toValue(id), request.signal)
    } catch (error) {
      if (!isAbortError(error)) {
        errorMessage.value = messageFrom(error, 'Could not load this item.')
      }
    } finally {
      if (activeRequest === request) {
        isLoading.value = false
      }
    }
  }

  async function review(payload: ReviewPayload): Promise<boolean> {
    isReviewing.value = true
    reviewErrorMessage.value = null

    try {
      item.value = await reviewItem(toValue(id), payload)
      return true
    } catch (error) {
      reviewErrorMessage.value = messageFrom(error, 'Could not save the review.')

      // 409 means someone else reviewed it first: show their decision instead of the stale form.
      if (error instanceof ApiError && error.status === 409) {
        await reload()
      }
      return false
    } finally {
      isReviewing.value = false
    }
  }

  watch(
    () => toValue(id),
    () => {
      reviewErrorMessage.value = null
      return reload()
    },
    { immediate: true },
  )
  onScopeDispose(() => activeRequest?.abort())

  return { item, isLoading, errorMessage, isReviewing, reviewErrorMessage, reload, review }
}

function messageFrom(error: unknown, fallback: string): string {
  return error instanceof Error ? error.message : fallback
}
