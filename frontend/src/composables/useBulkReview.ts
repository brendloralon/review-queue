import { ref, type Ref } from 'vue'
import { bulkReviewItems } from '@/api/items'
import type { BulkReviewPayload, BulkReviewResult } from '@/types/item'

export interface UseBulkReview {
  isSubmitting: Ref<boolean>
  errorMessage: Ref<string | null>
  result: Ref<BulkReviewResult | null>
  submit: (payload: BulkReviewPayload) => Promise<BulkReviewResult | null>
  reset: () => void
}

export function useBulkReview(): UseBulkReview {
  const isSubmitting = ref(false)
  const errorMessage = ref<string | null>(null)
  const result = ref<BulkReviewResult | null>(null)

  async function submit(payload: BulkReviewPayload): Promise<BulkReviewResult | null> {
    isSubmitting.value = true
    errorMessage.value = null
    result.value = null

    try {
      result.value = await bulkReviewItems(payload)
      return result.value
    } catch (error) {
      errorMessage.value = error instanceof Error ? error.message : 'Could not save the reviews.'
      return null
    } finally {
      isSubmitting.value = false
    }
  }

  function reset(): void {
    errorMessage.value = null
    result.value = null
  }

  return { isSubmitting, errorMessage, result, submit, reset }
}

/** One sentence for the reviewer: what was decided, and how many items need a second look. */
export function summarizeBulkReview(result: BulkReviewResult): string {
  const reviewedCount = result.reviewed.length
  const failedCount = result.failed.length
  const firstReviewed = result.reviewed[0]

  const reviewedPart = firstReviewed
    ? `${firstReviewed.status === 'approved' ? 'Approved' : 'Rejected'} ${countItems(reviewedCount)}.`
    : 'No items were reviewed.'
  const failedPart = failedCount > 0 ? ` ${countItems(failedCount)} could not be reviewed:` : ''

  return reviewedPart + failedPart
}

function countItems(count: number): string {
  return `${count} ${count === 1 ? 'item' : 'items'}`
}
