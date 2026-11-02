<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\ItemStatus;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkReviewItemsTest extends TestCase
{
    use RefreshDatabase;

    private const string URL = '/api/v1/items/bulk-review';

    public function test_pending_items_are_reviewed_with_one_decision_and_note(): void
    {
        $items = Item::factory()->count(3)->create();

        $this->postJson(self::URL, ['ids' => $items->pluck('id')->all(), 'status' => 'rejected', 'note' => 'Spam wave'])
            ->assertOk()
            ->assertJsonCount(3, 'data.reviewed')
            ->assertJsonPath('data.reviewed.*.status', ['rejected', 'rejected', 'rejected'])
            ->assertJsonPath('data.reviewed.0.review_note', 'Spam wave')
            ->assertJsonPath('data.failed', []);

        foreach ($items as $item) {
            $this->assertSame(ItemStatus::Rejected, $item->refresh()->status);
            $this->assertNotNull($item->reviewed_at);
        }
    }

    public function test_items_that_cannot_move_are_reported_and_the_rest_are_still_reviewed(): void
    {
        $pending = Item::factory()->create();
        $alreadyApproved = Item::factory()->approved()->create();

        $this->postJson(self::URL, ['ids' => [$pending->id, $alreadyApproved->id], 'status' => 'rejected'])
            ->assertOk()
            ->assertJsonPath('data.reviewed.*.id', [$pending->id])
            ->assertJsonPath('data.failed', [
                ['id' => $alreadyApproved->id, 'message' => "Item {$alreadyApproved->id} has already been approved."],
            ]);

        $this->assertSame(ItemStatus::Rejected, $pending->refresh()->status);
        $this->assertSame(ItemStatus::Approved, $alreadyApproved->refresh()->status);
    }

    public function test_a_decision_that_is_not_an_allowed_transition_fails_every_item(): void
    {
        $item = Item::factory()->create();

        $this->postJson(self::URL, ['ids' => [$item->id], 'status' => 'pending'])
            ->assertOk()
            ->assertJsonPath('data.reviewed', [])
            ->assertJsonPath('data.failed.0.message', "Item {$item->id} cannot move from pending to pending.");
    }

    public function test_unknown_or_repeated_ids_make_the_request_invalid(): void
    {
        $item = Item::factory()->create();

        $this->postJson(self::URL, ['ids' => [$item->id, 99999], 'status' => 'approved'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ids.1');

        $this->postJson(self::URL, ['ids' => [$item->id, $item->id], 'status' => 'approved'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ids.0', 'ids.1']);

        $this->assertSame(ItemStatus::Pending, $item->refresh()->status);
    }

    public function test_the_number_of_ids_is_bounded(): void
    {
        $this->postJson(self::URL, ['ids' => [], 'status' => 'approved'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ids');

        $this->postJson(self::URL, ['ids' => range(1, 51), 'status' => 'approved'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ids');
    }

    public function test_status_is_required_and_must_be_known(): void
    {
        $item = Item::factory()->create();

        $this->postJson(self::URL, ['ids' => [$item->id]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->postJson(self::URL, ['ids' => [$item->id], 'status' => 'archived'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }
}
