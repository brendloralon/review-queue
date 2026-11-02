import { describe, expect, it } from 'vitest'
import { DEFAULT_QUEUE_QUERY, parseQueueQuery, toApiParams, toRouteQuery } from '../useQueueQuery'

describe('parseQueueQuery', () => {
  it('uses defaults for an empty query', () => {
    expect(parseQueueQuery({})).toEqual(DEFAULT_QUEUE_QUERY)
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

  it('round-trips through parseQueueQuery', () => {
    const queueQuery = { status: 'all', search: 'video', sort: 'oldest' } as const

    expect(parseQueueQuery(toRouteQuery(queueQuery) as Record<string, string>)).toEqual(queueQuery)
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
