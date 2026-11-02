<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ItemStatus;
use App\Enums\SuggestedAction;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_items_are_pending_and_unreviewed(): void
    {
        $item = Item::create([
            'title' => 'Broken link on pricing page',
            'body' => 'The "Plans" link returns a 404.',
            'submitter_name' => 'Dana',
            'risk_score' => 0,
            'flags' => [],
            'suggested_action' => SuggestedAction::Approve,
        ]);

        $this->assertSame(ItemStatus::Pending, $item->status);
        $this->assertSame(ItemStatus::Pending, $item->fresh()->status);
        $this->assertNull($item->fresh()->reviewed_at);
    }

    public function test_review_fields_cannot_be_mass_assigned(): void
    {
        $item = Item::factory()->create();

        $item->fill(['status' => 'approved', 'review_note' => 'sneaky'])->save();

        $this->assertSame(ItemStatus::Pending, $item->fresh()->status);
        $this->assertNull($item->fresh()->review_note);
    }

    public function test_moderation_fields_round_trip_through_casts(): void
    {
        $item = Item::factory()->create([
            'risk_score' => 55,
            'flags' => ['contains_links', 'shouting'],
            'suggested_action' => SuggestedAction::Review,
        ])->fresh();

        $this->assertSame(55, $item->risk_score);
        $this->assertSame(['contains_links', 'shouting'], $item->flags);
        $this->assertSame(SuggestedAction::Review, $item->suggested_action);
    }
}
