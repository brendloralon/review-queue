<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\ItemStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReviewItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_pending_item_can_be_approved(): void
    {
        $item = Item::factory()->create();

        $this->postJson("/api/v1/items/{$item->id}/review", ['status' => 'approved'])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.review_note', null);

        $item->refresh();
        $this->assertSame(ItemStatus::Approved, $item->status);
        $this->assertNotNull($item->reviewed_at);
    }

    public function test_a_pending_item_can_be_rejected_with_a_note(): void
    {
        $item = Item::factory()->create();

        $this->postJson("/api/v1/items/{$item->id}/review", ['status' => 'rejected', 'note' => '  Spam link.  '])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.review_note', 'Spam link.');
    }

    public function test_a_whitespace_only_note_is_stored_as_null(): void
    {
        $item = Item::factory()->create();

        $this->postJson("/api/v1/items/{$item->id}/review", ['status' => 'approved', 'note' => "   \n "])
            ->assertOk()
            ->assertJsonPath('data.review_note', null);
    }

    public function test_reviewing_an_already_reviewed_item_returns_409_and_keeps_the_first_review(): void
    {
        $item = Item::factory()->create();
        $this->postJson("/api/v1/items/{$item->id}/review", ['status' => 'approved', 'note' => 'Looks fine']);

        $this->postJson("/api/v1/items/{$item->id}/review", ['status' => 'rejected', 'note' => 'Changed my mind'])
            ->assertConflict()
            ->assertExactJson(['message' => "Item {$item->id} has already been approved."]);

        $item->refresh();
        $this->assertSame(ItemStatus::Approved, $item->status);
        $this->assertSame('Looks fine', $item->review_note);
    }

    public function test_moving_a_pending_item_to_pending_returns_409(): void
    {
        $item = Item::factory()->create();

        $this->postJson("/api/v1/items/{$item->id}/review", ['status' => 'pending'])
            ->assertConflict()
            ->assertExactJson(['message' => "Item {$item->id} cannot move from pending to pending."]);
    }

    public function test_status_and_note_are_validated(): void
    {
        $item = Item::factory()->create();

        $this->postJson("/api/v1/items/{$item->id}/review", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        $this->postJson("/api/v1/items/{$item->id}/review", ['status' => 'escalated', 'note' => Str::repeat('a', 1001)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'note']);

        $this->assertSame(ItemStatus::Pending, $item->fresh()->status);
    }

    public function test_reviewing_an_unknown_item_returns_404(): void
    {
        $this->postJson('/api/v1/items/999/review', ['status' => 'approved'])->assertNotFound();
    }

    public function test_a_review_does_not_overwrite_one_made_after_the_item_was_loaded(): void
    {
        $item = Item::factory()->create();
        $staleCopy = Item::findOrFail($item->id);

        $item->review(ItemStatus::Rejected, 'First reviewer');

        try {
            $staleCopy->review(ItemStatus::Approved, 'Second reviewer');
            $this->fail('Expected the stale review to be rejected.');
        } catch (InvalidStatusTransition $exception) {
            $this->assertSame("Item {$item->id} has already been rejected.", $exception->getMessage());
        }

        $this->assertSame('First reviewer', $item->fresh()->review_note);
    }
}
