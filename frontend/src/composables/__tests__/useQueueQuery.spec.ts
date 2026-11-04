import { describe, expect, it } from 'vitest'
import {
  applyQueueChanges,
  DEFAULT_QUEUE_QUERY,
  parseQueueQuery,
  toApiParams,
  toRouteQuery,
} from '../useQueueQuery'

describe('parseQueueQuery', () => {
  it('uses defaults for an empty query: pending items, newest first', () => {
    expect(parseQueueQuery({})).toEqual({ status: 'pending', search: '', sort: 'newest' })
  })

  it('falls back to the default sort on every tab when no sort is given', () => {
    expect(parseQueueQuery({ status: 'approved' }).sort).toBe('newest')
    expect(parseQueueQuery({ status: 'pending' }).sort).toBe('newest')
  })

  it('replaces a status sort on a single-status tab with the default', () => {
    expect(parseQueueQuery({ status: 'approved', sort: 'pending_first' }).sort).toBe('newest')
    expect(parseQueueQuery({ sort: 'pending_last' }).sort).toBe('newest')
    expect(parseQueueQuery({ status: 'all', sort: 'pending_last' }).sort).toBe('pending_last')
  })

  it('reads valid values', () => {
    expect(parseQueueQuery({ status: 'reviewed', search: 'login', sort: 'highest_risk' })).toEqual({
      status: 'reviewed',
      search: 'login',
      sort: 'highest_risk',
    })
  })

  it('falls back to defaults for unknown values', () => {
    expect(parseQueueQuery({ status: 'archived', sort: 'title', color: 'blue' })).toEqual(
      DEFAULT_QUEUE_QUERY,
    )
  })

  it('uses the first value when a param is repeated', () => {
    expect(parseQueueQuery({ status: ['approved', 'rejected'] }).status).toBe('approved')
  })
})

describe('toRouteQuery', () => {
  it('omits defaults so the plain queue URL stays clean', () => {
    expect(toRouteQuery(DEFAULT_QUEUE_QUERY)).toEqual({})
  })

  it('omits the sort when it is the default', () => {
    expect(toRouteQuery({ status: 'all', search: '', sort: 'newest' })).toEqual({ status: 'all' })
    expect(toRouteQuery({ status: 'all', search: '', sort: 'highest_risk' })).toEqual({
      status: 'all',
      sort: 'highest_risk',
    })
  })

  it('round-trips through parseQueueQuery', () => {
    const queueQuery = { status: 'all', search: 'video', sort: 'oldest' } as const

    expect(parseQueueQuery(toRouteQuery(queueQuery) as Record<string, string>)).toEqual(queueQuery)
  })
})

describe('applyQueueChanges', () => {
  const sortedByAge = { status: 'all', search: 'video', sort: 'oldest' } as const

  it('switching tabs resets the sort to the default', () => {
    expect(applyQueueChanges(sortedByAge, { status: 'pending' })).toEqual({
      status: 'pending',
      search: 'video',
      sort: 'newest',
    })
  })

  it('keeps the sort when only the search changes', () => {
    expect(applyQueueChanges(sortedByAge, { search: 'login' }).sort).toBe('oldest')
  })

  it('keeps an explicitly requested sort', () => {
    expect(applyQueueChanges(sortedByAge, { status: 'pending', sort: 'oldest' }).sort).toBe(
      'oldest',
    )
  })
})

describe('toApiParams', () => {
  it('drops the "all" status and a blank search', () => {
    expect(toApiParams({ status: 'all', search: '   ', sort: 'newest' })).toEqual({
      status: undefined,
      search: undefined,
      sort: 'newest',
    })
  })

  it('trims the search term', () => {
    expect(toApiParams({ status: 'pending', search: '  video ', sort: 'newest' }).search).toBe(
      'video',
    )
  })
})
