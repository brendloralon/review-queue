import { computed, ref, toValue, watch, type ComputedRef, type MaybeRefOrGetter } from 'vue'

export interface UseItemSelection {
  selectedIds: ComputedRef<number[]>
  allSelected: ComputedRef<boolean>
  someSelected: ComputedRef<boolean>
  isSelected: (id: number) => boolean
  toggle: (id: number) => void
  toggleAll: () => void
  clear: () => void
}

/**
 * Checkbox selection over the items currently shown. When the list changes (search, refetch),
 * ids that are no longer visible are dropped, so a bulk action never touches hidden items.
 */
export function useItemSelection(visibleIds: MaybeRefOrGetter<number[]>): UseItemSelection {
  const selected = ref(new Set<number>())

  const selectedIds = computed(() => toValue(visibleIds).filter((id) => selected.value.has(id)))
  const allSelected = computed(
    () => toValue(visibleIds).length > 0 && selectedIds.value.length === toValue(visibleIds).length,
  )
  const someSelected = computed(() => selectedIds.value.length > 0)

  function isSelected(id: number): boolean {
    return selected.value.has(id)
  }

  function toggle(id: number): void {
    const next = new Set(selected.value)

    if (next.has(id)) {
      next.delete(id)
    } else {
      next.add(id)
    }
    selected.value = next
  }

  function toggleAll(): void {
    selected.value = allSelected.value ? new Set() : new Set(toValue(visibleIds))
  }

  function clear(): void {
    selected.value = new Set()
  }

  watch(
    () => toValue(visibleIds),
    (ids) => {
      selected.value = new Set(ids.filter((id) => selected.value.has(id)))
    },
  )

  return { selectedIds, allSelected, someSelected, isSelected, toggle, toggleAll, clear }
}
