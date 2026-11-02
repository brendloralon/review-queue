<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\ItemStatus;
use App\Models\Item;
use Illuminate\Database\Eloquent\Builder;

/**
 * The single whitelist for queue filters, search and sort. Unknown or invalid
 * values fall back to defaults instead of failing, so a stale or hand-edited
 * URL still shows a sensible queue.
 */
final class ItemQuery
{
    public const string REVIEWED_FILTER = 'reviewed';

    public const string DEFAULT_SORT = 'newest';

    public const int MAX_SEARCH_LENGTH = 100;

    /**
     * Sort name => [column, direction]. `id` is appended in the same direction as
     * a tie-breaker so items with equal values keep a stable order.
     */
    private const array SORTS = [
        'newest' => ['created_at', 'desc'],
        'oldest' => ['created_at', 'asc'],
        'highest_risk' => ['risk_score', 'desc'],
        'lowest_risk' => ['risk_score', 'asc'],
        'pending_first' => ['status', 'asc'],
        'pending_last' => ['status', 'desc'],
    ];

    /**
     * @param  array<string, mixed>  $params
     * @return Builder<Item>
     */
    public function apply(Builder $query, array $params): Builder
    {
        $this->filterByStatus($query, $params['status'] ?? null);
        $this->search($query, $params['search'] ?? null);
        $this->sort($query, $params['sort'] ?? null);

        return $query;
    }

    /**
     * @param  Builder<Item>  $query
     */
    private function filterByStatus(Builder $query, mixed $status): void
    {
        if ($status === self::REVIEWED_FILTER) {
            $query->whereIn('status', ItemStatus::reviewedStatuses());

            return;
        }

        $knownStatus = is_string($status) ? ItemStatus::tryFrom($status) : null;

        if ($knownStatus !== null) {
            $query->where('status', $knownStatus);
        }
    }

    /**
     * @param  Builder<Item>  $query
     */
    private function search(Builder $query, mixed $search): void
    {
        if (! is_string($search)) {
            return;
        }

        $term = trim($search);

        if ($term === '' || mb_strlen($term) > self::MAX_SEARCH_LENGTH) {
            return;
        }

        $query->where(function (Builder $matches) use ($term): void {
            $matches->where('title', 'like', "%{$term}%")
                ->orWhere('body', 'like', "%{$term}%")
                ->orWhere('submitter_name', 'like', "%{$term}%");
        });
    }

    /**
     * @param  Builder<Item>  $query
     */
    private function sort(Builder $query, mixed $sort): void
    {
        $sortName = is_string($sort) && array_key_exists($sort, self::SORTS) ? $sort : self::DEFAULT_SORT;
        [$column, $direction] = self::SORTS[$sortName];

        if ($column === 'status') {
            $this->orderByWorkflowPosition($query, $direction);
        } else {
            $query->orderBy($column, $direction);
        }

        $query->orderBy('id', $direction);
    }

    /**
     * Status is stored as a string, so ordering the column directly would be alphabetical
     * (approved, pending, rejected). A CASE maps each status to its workflow position instead.
     *
     * @param  Builder<Item>  $query
     */
    private function orderByWorkflowPosition(Builder $query, string $direction): void
    {
        $cases = ItemStatus::cases();
        $whenClauses = implode(' ', array_fill(0, count($cases), 'WHEN ? THEN ?'));
        $bindings = [];

        foreach ($cases as $status) {
            array_push($bindings, $status->value, $status->workflowPosition());
        }

        // $direction comes from the SORTS whitelist, never from the request.
        $query->orderByRaw("CASE status {$whenClauses} END {$direction}", $bindings);
    }
}
