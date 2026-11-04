<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
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
            'body' => Str::repeat('a', 501),
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

    /**
     * @return array<string, array{string, string}>
     */
    public static function disallowedInput(): array
    {
        return [
            'null byte in title' => ['title', "Broken\u{0000}title"],
            'line break in title' => ['title', "First line\nSecond line"],
            'escape sequence in body' => ['body', "Looks normal\u{001B}[2J"],
            'direction override in body' => ['body', "Invoice \u{202E}fdp.exe"],
            'zero-width space in title' => ['title', "Free\u{200B}money"],
            'digits in name' => ['submitter_name', 'Agent 007'],
            'symbols in name' => ['submitter_name', '<b>Dana</b>'],
            'name starting with a symbol' => ['submitter_name', '-Dana'],
        ];
    }

    #[DataProvider('disallowedInput')]
    public function test_control_invisible_and_unexpected_characters_are_rejected(string $field, string $value): void
    {
        $payload = ['title' => 'Typo', 'body' => 'Small typo.', 'submitter_name' => 'Dana', $field => $value];

        $this->postJson('/api/v1/items', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field]);

        $this->assertDatabaseCount('items', 0);
    }

    public function test_ordinary_text_in_any_language_is_accepted(): void
    {
        $this->postJson('/api/v1/items', [
            'title' => 'Héllo — שלום 👋',
            'body' => "Line one\nLine two\twith a tab.",
            'submitter_name' => "Anne-Marie O'Brien",
        ])->assertCreated();

        $this->postJson('/api/v1/items', [
            'title' => 'Dr. Levi',
            'body' => 'Text',
            'submitter_name' => 'נועה כהן',
        ])->assertCreated();
    }

    public function test_html_is_stored_and_returned_as_plain_text(): void
    {
        // Rejecting < and > would block real reports; output escaping is what prevents XSS.
        $title = 'Score < 30 shows <script>alert(1)</script>';

        $this->postJson('/api/v1/items', ['title' => $title, 'body' => 'Text', 'submitter_name' => 'Dana'])
            ->assertCreated()
            ->assertJsonPath('data.title', $title);
    }
}
