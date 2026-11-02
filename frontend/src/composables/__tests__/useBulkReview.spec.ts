import { describe, expect, it } from 'vitest'
import { summarizeBulkReview } from '../useBulkReview'
import type { Item } from '@/types/item'

function reviewedItem(id: number, status: Item['status']): Item {
  return {
    id,
    title: `Item ${id}`,
    body: '',
    submitter_name: 'Test',
    status,
    risk_score: 0,
    flags: [],
    suggested_action: 'approve',
    review_note: null,
    reviewed_at: '2026-11-02T10:00:00+00:00',
    created_at: '2026-11-02T09:00:00+00:00',
  }
}

describe('summarizeBulkReview', () => {
  it('reports how many items were decided', () => {
    expect(
      summarizeBulkReview({
        reviewed: [reviewedItem(1, 'approved'), reviewedItem(2, 'approved')],
        failed: [],
      }),
    ).toBe('Approved 2 items.')
    expect(summarizeBulkReview({ reviewed: [reviewedItem(1, 'rejected')], failed: [] })).toBe(
      'Rejected 1 item.',
    )
  })

  it('adds how many items could not be reviewed', () => {
    const failure = { id: 2, message: 'Item 2 has already been approved.' }

    expect(
      summarizeBulkReview({ reviewed: [reviewedItem(1, 'rejected')], failed: [failure] }),
    ).toBe('Rejected 1 item. 1 item could not be reviewed:')
    expect(summarizeBulkReview({ reviewed: [], failed: [failure] })).toBe(
      'No items were reviewed. 1 item could not be reviewed:',
    )
  })
})
