import { nextTick, ref } from 'vue'
import { describe, expect, it } from 'vitest'
import { useItemSelection } from '../useItemSelection'

describe('useItemSelection', () => {
  it('toggles single items and keeps them in list order', () => {
    const selection = useItemSelection([3, 1, 2])

    selection.toggle(2)
    selection.toggle(3)
    expect(selection.selectedIds.value).toEqual([3, 2])

    selection.toggle(3)
    expect(selection.selectedIds.value).toEqual([2])
    expect(selection.isSelected(2)).toBe(true)
  })

  it('selects all, then clears all, from the header checkbox', () => {
    const selection = useItemSelection([1, 2])

    selection.toggle(1)
    expect(selection.someSelected.value).toBe(true)
    expect(selection.allSelected.value).toBe(false)

    selection.toggleAll()
    expect(selection.selectedIds.value).toEqual([1, 2])
    expect(selection.allSelected.value).toBe(true)

    selection.toggleAll()
    expect(selection.selectedIds.value).toEqual([])
  })

  it('is never "all selected" for an empty list', () => {
    expect(useItemSelection([]).allSelected.value).toBe(false)
  })

  it('drops items that are no longer visible', async () => {
    const visibleIds = ref([1, 2, 3])
    const selection = useItemSelection(visibleIds)
    selection.toggleAll()

    visibleIds.value = [2, 4]
    await nextTick()
    expect(selection.selectedIds.value).toEqual([2])

    // A dropped item does not come back selected when it reappears.
    visibleIds.value = [1, 2, 3]
    await nextTick()
    expect(selection.selectedIds.value).toEqual([2])
  })
})
