import type { StatusFilter } from '@/composables/useQueueQuery'
import type { ItemSort } from '@/types/item'

export type SortableColumn = 'status' | 'risk' | 'submitted'
export type SortDirection = 'ascending' | 'descending'

// Only these tabs mix statuses; on a single-status tab every row has the same status.
const MIXED_STATUS_FILTERS: readonly StatusFilter[] = ['all', 'reviewed']

interface ColumnSorts {
  ascending: ItemSort
  descending: ItemSort
  firstDirection: SortDirection
}

/**
 * Each sortable column maps to the two API sorts for it. The first click uses the direction a
 * reviewer most likely wants: pending items first, riskiest first, newest first.
 */
const COLUMN_SORTS: Record<SortableColumn, ColumnSorts> = {
  status: { ascending: 'pending_first', descending: 'pending_last', firstDirection: 'ascending' },
  risk: { ascending: 'lowest_risk', descending: 'highest_risk', firstDirection: 'descending' },
  submitted: { ascending: 'oldest', descending: 'newest', firstDirection: 'descending' },
}

/** Risk and submission time help prioritise on every tab; status only where statuses are mixed. */
export function sortableColumnsFor(status: StatusFilter): SortableColumn[] {
  return MIXED_STATUS_FILTERS.includes(status)
    ? ['status', 'risk', 'submitted']
    : ['risk', 'submitted']
}

export function isSortAvailable(sort: ItemSort, status: StatusFilter): boolean {
  return sortableColumnsFor(status).some((column) => sortDirectionOf(column, sort) !== undefined)
}

/**
 * The default follows the reviewer's job on each tab. Pending is triage: riskiest first, and the
 * API lists the oldest first among equal scores. Other tabs are history: newest first.
 */
export function defaultSortFor(status: StatusFilter): ItemSort {
  return status === 'pending' ? 'highest_risk' : 'newest'
}

/** The column's current direction, or undefined when the queue is sorted by another column. */
export function sortDirectionOf(
  column: SortableColumn,
  currentSort: ItemSort,
): SortDirection | undefined {
  const sorts = COLUMN_SORTS[column]

  if (currentSort === sorts.ascending) {
    return 'ascending'
  }
  if (currentSort === sorts.descending) {
    return 'descending'
  }
  return undefined
}

/** Clicking the active column flips its direction; clicking another column starts at its first direction. */
export function nextSort(column: SortableColumn, currentSort: ItemSort): ItemSort {
  const sorts = COLUMN_SORTS[column]
  const currentDirection = sortDirectionOf(column, currentSort)

  if (currentDirection === undefined) {
    return sorts[sorts.firstDirection]
  }
  return currentDirection === 'ascending' ? sorts.descending : sorts.ascending
}
