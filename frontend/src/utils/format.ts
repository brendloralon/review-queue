import type { ItemStatus, SuggestedAction } from '@/types/item'

export const STATUS_LABELS: Record<ItemStatus, string> = {
  pending: 'Pending',
  approved: 'Approved',
  rejected: 'Rejected',
}

export const SUGGESTED_ACTION_LABELS: Record<SuggestedAction, string> = {
  approve: 'Approve',
  review: 'Needs review',
  reject: 'Reject',
}

const FLAG_LABELS: Record<string, string> = {
  blocklisted_terms: 'Blocklisted terms',
  shouting: 'Shouting',
  contains_links: 'Contains links',
  many_links: 'Many links',
}

/** Unknown flags still read sensibly, so a new backend rule needs no frontend change. */
export function formatFlag(flag: string): string {
  return FLAG_LABELS[flag] ?? flag.replaceAll('_', ' ')
}

const dateTimeFormat = new Intl.DateTimeFormat(undefined, {
  dateStyle: 'medium',
  timeStyle: 'short',
})

export function formatDateTime(isoDate: string): string {
  return dateTimeFormat.format(new Date(isoDate))
}
