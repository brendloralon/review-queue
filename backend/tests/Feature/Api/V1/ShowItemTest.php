<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_item_is_returned_with_its_full_shape(): void
    {
        $item = Item::factory()->create(['title' => 'Login button misaligned']);

        $this->getJson("/api/v1/items/{$item->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $item->id)
            ->assertJsonPath('data.title', 'Login button misaligned')
            ->assertJsonStructure(['data' => [
                'id', 'title', 'body', 'submitter_name', 'status', 'risk_score', 'flags',
                'suggested_action', 'review_note', 'reviewed_at', 'created_at',
            ]]);
    }

    public function test_an_unknown_item_returns_a_json_404(): void
    {
        $this->getJson('/api/v1/items/999')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }
}
