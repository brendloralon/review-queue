<?php

declare(strict_types=1);

namespace App\Enums;

enum ItemStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /**
     * Exhaustive match on purpose: adding a status forces a decision on whether
     * it counts as reviewed (used by the queue's "reviewed" filter).
     */
    public function isReviewed(): bool
    {
        return match ($this) {
            self::Pending => false,
            self::Approved, self::Rejected => true,
        };
    }

    /**
     * @return list<self>
     */
    public static function reviewedStatuses(): array
    {
        return array_values(array_filter(self::cases(), fn (self $status): bool => $status->isReviewed()));
    }

    /**
     * The whole review flow lives here: reviewed items are final.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Approved, self::Rejected],
            self::Approved, self::Rejected => [],
        };
    }
}
