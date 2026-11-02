import { computed, type ComputedRef } from 'vue'
import { useRoute, useRouter, type LocationQuery, type LocationQueryRaw } from 'vue-router'
import type { ItemListParams, ItemSort, ItemStatusFilter } from '@/types/item'

export const STATUS_FILTERS = ['pending', 'reviewed', 'approved', 'rejected', 'all'] as const
export type StatusFilter = (typeof STATUS_FILTERS)[number]

export const SORT_OPTIONS = [
  'newest',
  'oldest',
  'highest_risk',
  'lowest_risk',
  'pending_first',
  'pending_last',
] as const satisfies readonly ItemSort[]

export interface QueueQuery {
  status: StatusFilter
  search: string
  sort: ItemSort
}

// A reviewer's queue starts with what still needs a decision.
export const DEFAULT_QUEUE_QUERY: QueueQuery = { status: 'pending', search: '', sort: 'newest' }

export interface UseQueueQuery {
  queueQuery: ComputedRef<QueueQuery>
  apiParams: ComputedRef<ItemListParams>
  updateQueueQuery: (changes: Partial<QueueQuery>) => void
}

/**
 * The only reader and writer of queue state in the URL, so filters survive reloads,
 * can be shared as links, and work with the browser's back button.
 */
export function useQueueQuery(): UseQueueQuery {
  const route = useRoute()
  const router = useRouter()

  const queueQuery = computed(() => parseQueueQuery(route.query))
  const apiParams = computed(() => toApiParams(queueQuery.value))

  function updateQueueQuery(changes: Partial<QueueQuery>): void {
    // replace() rather than push(): typing in search should not add a history entry per keystroke.
    router.replace({ query: toRouteQuery({ ...queueQuery.value, ...changes }) })
  }

  return { queueQuery, apiParams, updateQueueQuery }
}

export function parseQueueQuery(query: LocationQuery): QueueQuery {
  const status = firstValue(query.status)
  const sort = firstValue(query.sort)

  return {
    status: isOneOf(status, STATUS_FILTERS) ? status : DEFAULT_QUEUE_QUERY.status,
    search: firstValue(query.search) ?? DEFAULT_QUEUE_QUERY.search,
    sort: isOneOf(sort, SORT_OPTIONS) ? sort : DEFAULT_QUEUE_QUERY.sort,
  }
}

/** Defaults are left out so the plain queue URL stays clean. */
export function toRouteQuery(queueQuery: QueueQuery): LocationQueryRaw {
  const routeQuery: LocationQueryRaw = {}

  if (queueQuery.status !== DEFAULT_QUEUE_QUERY.status) {
    routeQuery.status = queueQuery.status
  }
  if (queueQuery.search !== DEFAULT_QUEUE_QUERY.search) {
    routeQuery.search = queueQuery.search
  }
  if (queueQuery.sort !== DEFAULT_QUEUE_QUERY.sort) {
    routeQuery.sort = queueQuery.sort
  }

  return routeQuery
}

export function toApiParams(queueQuery: QueueQuery): ItemListParams {
  const search = queueQuery.search.trim()
  const status: ItemStatusFilter | undefined =
    queueQuery.status === 'all' ? undefined : queueQuery.status

  return {
    status,
    search: search === '' ? undefined : search,
    sort: queueQuery.sort,
  }
}

function firstValue(value: LocationQuery[string] | undefined): string | undefined {
  const first = Array.isArray(value) ? value[0] : value
  return typeof first === 'string' ? first : undefined
}

function isOneOf<T extends string>(value: string | undefined, allowed: readonly T[]): value is T {
  return value !== undefined && (allowed as readonly string[]).includes(value)
}
