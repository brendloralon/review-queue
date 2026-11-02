// Mirrors App\Http\Resources\V1\ItemResource and the request payloads exactly,
// including snake_case field names, so nothing is renamed at the API boundary.

export type ItemStatus = 'pending' | 'approved' | 'rejected'

export type SuggestedAction = 'approve' | 'review' | 'reject'

export interface Item {
  id: number
  title: string
  body: string
  submitter_name: string
  status: ItemStatus
  risk_score: number
  flags: string[]
  suggested_action: SuggestedAction
  review_note: string | null
  reviewed_at: string | null
  created_at: string
}

export type ItemStatusFilter = ItemStatus | 'reviewed'

export type ItemSort =
  'newest' | 'oldest' | 'highest_risk' | 'lowest_risk' | 'pending_first' | 'pending_last'

export interface ItemListParams {
  status?: ItemStatusFilter
  search?: string
  sort?: ItemSort
}

export interface NewItem {
  title: string
  body: string
  submitter_name: string
}

export type ReviewDecision = Exclude<ItemStatus, 'pending'>

export interface ReviewPayload {
  status: ReviewDecision
  note: string | null
}
