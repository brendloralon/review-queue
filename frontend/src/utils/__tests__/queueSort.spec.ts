import { describe, expect, it } from 'vitest'
import {
  defaultSortFor,
  isSortAvailable,
  nextSort,
  sortableColumnsFor,
  sortDirectionOf,
} from '../queueSort'

describe('sortDirectionOf', () => {
  it('reports the direction of the active column', () => {
    expect(sortDirectionOf('submitted', 'newest')).toBe('descending')
    expect(sortDirectionOf('submitted', 'oldest')).toBe('ascending')
    expect(sortDirectionOf('risk', 'highest_risk')).toBe('descending')
    expect(sortDirectionOf('status', 'pending_first')).toBe('ascending')
  })

  it('is undefined for columns the queue is not sorted by', () => {
    expect(sortDirectionOf('risk', 'newest')).toBeUndefined()
    expect(sortDirectionOf('status', 'lowest_risk')).toBeUndefined()
  })
})

describe('nextSort', () => {
  it('starts each column at the direction a reviewer most likely wants', () => {
    expect(nextSort('status', 'newest')).toBe('pending_first')
    expect(nextSort('risk', 'newest')).toBe('highest_risk')
    expect(nextSort('submitted', 'highest_risk')).toBe('newest')
  })

  it('flips the direction when the active column is clicked again', () => {
    expect(nextSort('submitted', 'newest')).toBe('oldest')
    expect(nextSort('submitted', 'oldest')).toBe('newest')
    expect(nextSort('risk', 'highest_risk')).toBe('lowest_risk')
    expect(nextSort('status', 'pending_first')).toBe('pending_last')
  })
})

describe('sortableColumnsFor', () => {
  it('offers the status sort only on tabs that mix statuses', () => {
    expect(sortableColumnsFor('all')).toEqual(['status', 'risk', 'submitted'])
    expect(sortableColumnsFor('reviewed')).toEqual(['status', 'risk', 'submitted'])
    expect(sortableColumnsFor('pending')).toEqual(['risk', 'submitted'])
    expect(sortableColumnsFor('approved')).toEqual(['risk', 'submitted'])
  })

  it('rejects a status sort on a single-status tab', () => {
    expect(isSortAvailable('pending_first', 'all')).toBe(true)
    expect(isSortAvailable('pending_first', 'rejected')).toBe(false)
    expect(isSortAvailable('oldest', 'rejected')).toBe(true)
  })
})

describe('defaultSortFor', () => {
  it('triages pending items by risk and shows other tabs newest first', () => {
    expect(defaultSortFor('pending')).toBe('highest_risk')
    expect(defaultSortFor('reviewed')).toBe('newest')
    expect(defaultSortFor('all')).toBe('newest')
  })
})
