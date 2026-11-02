<?php

declare(strict_types=1);

namespace App\Reviews;

use App\Enums\ItemStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Models\Item;

/**
 * Reviews several items with one decision by calling Item::review() for each, so the
 * transition rules and the protection against concurrent reviews stay in one place.
 *
 * Items are independent rather than all-or-nothing: if another reviewer already decided
 * one item, the others are still reviewed and that item is reported as failed.
 */
final class BulkReviewer
{
    /**
     * @param  list<int>  $itemIds
     */
    public function review(array $itemIds, ItemStatus $decision, ?string $note): BulkReviewResult
    {
        $reviewed = [];
        $failed = [];

        foreach (Item::query()->findMany($itemIds)->sortBy('id') as $item) {
            try {
                $item->review($decision, $note);
                $reviewed[] = $item;
            } catch (InvalidStatusTransition $exception) {
                $failed[$item->id] = $exception->getMessage();
            }
        }

        return new BulkReviewResult($reviewed, $failed);
    }
}
