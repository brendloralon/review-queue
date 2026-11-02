<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StoreItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_submitting_an_item_stores_it_as_pending_with_moderation_results(): void
    {
        $response = $this->postJson('/api/v1/items', [
            'title' => 'Free money inside',
            'body' => 'Details at https://example.test',
            'submitter_name' => 'Dana',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Free money inside')
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.risk_score', 55)
            ->assertJsonPath('data.flags', ['blocklisted_terms', 'contains_links'])
            ->assertJsonPath('data.suggested_action', 'review')
            ->assertJsonPath('data.review_note', null)
            ->assertJsonPath('data.reviewed_at', null);

        $this->assertDatabaseHas('items', [
            'id' => $response->json('data.id'),
            'status' => 'pending',
            'risk_score' => 55,
            'suggested_action' => 'review',
        ]);
    }

    public function test_required_fields_are_validated(): void
    {
        $this->postJson('/api/v1/items', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'body', 'submitter_name']);

        $this->assertSame(0, Item::count());
    }

    public function test_whitespace_only_fields_count_as_missing(): void
    {
        $this->postJson('/api/v1/items', ['title' => '   ', 'body' => "\n\t", 'submitter_name' => ' '])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'body', 'submitter_name']);
    }

    public function test_field_lengths_are_limited(): void
    {
        $this->postJson('/api/v1/items', [
            'title' => Str::repeat('a', 121),
            'body' => Str::repeat('a', 5001),
            'submitter_name' => Str::repeat('a', 81),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'body', 'submitter_name']);
    }

    public function test_review_fields_in_the_payload_are_ignored(): void
    {
        $response = $this->postJson('/api/v1/items', [
            'title' => 'Typo on homepage',
            'body' => 'Small typo in the footer.',
            'submitter_name' => 'Dana',
            'status' => 'approved',
            'risk_score' => 0,
        ]);

        $response->assertCreated()->assertJsonPath('data.status', 'pending');
    }
}
