import { afterEach, describe, expect, it, vi } from 'vitest'
import { effectScope } from 'vue'
import { useItem } from '../useItem'
import { ApiError } from '@/api/client'
import { getItem, reviewItem } from '@/api/items'
import type { Item } from '@/types/item'

vi.mock('@/api/items', () => ({
  getItem: vi.fn<typeof getItem>(),
  reviewItem: vi.fn<typeof reviewItem>(),
}))

function itemWithStatus(status: Item['status']): Item {
  return {
    id: 1,
    title: 'Item 1',
    body: '',
    submitter_name: 'Test',
    status,
    risk_score: 0,
    flags: [],
    suggested_action: 'approve',
    review_note: null,
    reviewed_at: status === 'pending' ? null : '2026-11-02T10:00:00+00:00',
    created_at: '2026-11-02T09:00:00+00:00',
  }
}

function setUpItem(): ReturnType<typeof useItem> {
  vi.mocked(getItem).mockResolvedValue(itemWithStatus('pending'))
  return effectScope().run(() => useItem(1))!
}

describe('useItem review', () => {
  afterEach(() => vi.resetAllMocks())

  it('reports success and shows the server decision', async () => {
    vi.mocked(reviewItem).mockResolvedValue(itemWithStatus('approved'))
    const { item, review } = setUpItem()

    expect(await review({ status: 'approved', note: null })).toBe(true)
    expect(item.value?.status).toBe('approved')
  })

  it('reports failure and keeps the error message', async () => {
    vi.mocked(reviewItem).mockRejectedValue(new ApiError('The note is too long.', 422))
    const { reviewErrorMessage, review } = setUpItem()

    expect(await review({ status: 'approved', note: null })).toBe(false)
    expect(reviewErrorMessage.value).toBe('The note is too long.')
  })

  it('reports failure and reloads the item when another reviewer decided first', async () => {
    vi.mocked(reviewItem).mockRejectedValue(new ApiError('Item 1 has already been rejected.', 409))
    const { item, review } = setUpItem()
    vi.mocked(getItem).mockResolvedValue(itemWithStatus('rejected'))

    expect(await review({ status: 'approved', note: null })).toBe(false)
    expect(item.value?.status).toBe('rejected')
  })
})
