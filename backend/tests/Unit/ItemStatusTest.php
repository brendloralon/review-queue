<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\ItemStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ItemStatusTest extends TestCase
{
    /**
     * @return array<string, array{ItemStatus, ItemStatus, bool}>
     */
    public static function transitions(): array
    {
        return [
            'pending to approved' => [ItemStatus::Pending, ItemStatus::Approved, true],
            'pending to rejected' => [ItemStatus::Pending, ItemStatus::Rejected, true],
            'pending to pending' => [ItemStatus::Pending, ItemStatus::Pending, false],
            'approved to rejected' => [ItemStatus::Approved, ItemStatus::Rejected, false],
            'approved to pending' => [ItemStatus::Approved, ItemStatus::Pending, false],
            'approved to approved' => [ItemStatus::Approved, ItemStatus::Approved, false],
            'rejected to approved' => [ItemStatus::Rejected, ItemStatus::Approved, false],
            'rejected to pending' => [ItemStatus::Rejected, ItemStatus::Pending, false],
            'rejected to rejected' => [ItemStatus::Rejected, ItemStatus::Rejected, false],
        ];
    }

    #[DataProvider('transitions')]
    public function test_transition_rules(ItemStatus $from, ItemStatus $to, bool $allowed): void
    {
        $this->assertSame($allowed, $from->canTransitionTo($to));
    }

    public function test_approved_and_rejected_count_as_reviewed(): void
    {
        $this->assertSame([ItemStatus::Approved, ItemStatus::Rejected], ItemStatus::reviewedStatuses());
    }

    public function test_workflow_position_puts_pending_before_reviewed_statuses(): void
    {
        $this->assertSame(0, ItemStatus::Pending->workflowPosition());
        $this->assertSame(1, ItemStatus::Approved->workflowPosition());
        $this->assertSame(2, ItemStatus::Rejected->workflowPosition());
    }
}
