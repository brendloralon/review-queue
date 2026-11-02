import { computed, onScopeDispose, ref, toValue, watch, type MaybeRefOrGetter, type Ref } from 'vue'
import { isAbortError } from '@/api/client'
import { listItems, toItemListQuery } from '@/api/items'
import type { Item, ItemListParams } from '@/types/item'

export interface UseItems {
  items: Ref<Item[]>
  isLoading: Ref<boolean>
  errorMessage: Ref<string | null>
  reload: () => Promise<void>
}

export function useItems(params: MaybeRefOrGetter<ItemListParams>): UseItems {
  const items = ref<Item[]>([])
  const isLoading = ref(false)
  const errorMessage = ref<string | null>(null)
  let activeRequest: AbortController | null = null

  async function reload(): Promise<void> {
    // Abort the previous request so a slow, outdated response never replaces a newer one.
    activeRequest?.abort()
    const request = new AbortController()
    activeRequest = request

    isLoading.value = true
    errorMessage.value = null

    try {
      items.value = await listItems(toValue(params), request.signal)
    } catch (error) {
      if (!isAbortError(error)) {
        errorMessage.value = error instanceof Error ? error.message : 'Could not load items.'
      }
    } finally {
      if (activeRequest === request) {
        isLoading.value = false
      }
    }
  }

  // Watch the serialized query, not the params object: route changes such as opening
  // the item drawer rebuild the object without changing any value, and must not refetch.
  const queryKey = computed(() => toItemListQuery(toValue(params)))
  watch(queryKey, reload, { immediate: true })
  onScopeDispose(() => activeRequest?.abort())

  return { items, isLoading, errorMessage, reload }
}
