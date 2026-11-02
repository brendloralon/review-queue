<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ListItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_without_params_all_items_are_listed_newest_first(): void
    {
        $older = Item::factory()->create(['created_at' => now()->subDay()]);
        $newer = Item::factory()->approved()->create(['created_at' => now()]);

        $this->getJson('/api/v1/items')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$newer->id, $older->id]);
    }

    public function test_items_can_be_filtered_by_status(): void
    {
        $pending = Item::factory()->create();
        $approved = Item::factory()->approved()->create();
        $rejected = Item::factory()->rejected()->create();

        $this->assertListedIds('?status=pending', [$pending->id]);
        $this->assertListedIds('?status=approved', [$approved->id]);
        $this->assertListedIds('?status=rejected', [$rejected->id]);
        $this->assertListedIds('?status=reviewed', [$rejected->id, $approved->id]);
    }

    public function test_an_invalid_status_is_ignored(): void
    {
        Item::factory()->count(2)->create();
        Item::factory()->approved()->create();

        $this->getJson('/api/v1/items?status=archived')->assertOk()->assertJsonCount(3, 'data');
        $this->getJson('/api/v1/items?status[]=pending')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_search_matches_title_body_and_submitter_name(): void
    {
        $byTitle = Item::factory()->create(['title' => 'Checkout crashes']);
        $byBody = Item::factory()->create(['body' => 'The checkout page is blank.']);
        $bySubmitter = Item::factory()->create(['submitter_name' => 'Checkout Team']);
        Item::factory()->create(['title' => 'Unrelated', 'body' => 'Nothing here.', 'submitter_name' => 'Dana']);

        $this->getJson('/api/v1/items?search=checkout')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment(['id' => $byTitle->id])
            ->assertJsonFragment(['id' => $byBody->id])
            ->assertJsonFragment(['id' => $bySubmitter->id]);
    }

    public function test_search_combines_with_the_status_filter(): void
    {
        $pendingMatch = Item::factory()->create(['title' => 'Checkout crashes']);
        Item::factory()->approved()->create(['title' => 'Checkout typo']);

        $this->assertListedIds('?status=pending&search=checkout', [$pendingMatch->id]);
    }

    public function test_blank_or_overlong_search_is_ignored(): void
    {
        Item::factory()->count(2)->create();

        $this->getJson('/api/v1/items?search=%20%20')->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/items?search='.Str::repeat('a', 101))->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_items_can_be_sorted_by_date_and_risk(): void
    {
        $lowRiskOld = Item::factory()->create(['risk_score' => 10, 'created_at' => now()->subDays(2)]);
        $highRiskMid = Item::factory()->create(['risk_score' => 90, 'created_at' => now()->subDay()]);
        $midRiskNew = Item::factory()->create(['risk_score' => 50, 'created_at' => now()]);

        $this->assertListedIds('?sort=newest', [$midRiskNew->id, $highRiskMid->id, $lowRiskOld->id]);
        $this->assertListedIds('?sort=oldest', [$lowRiskOld->id, $highRiskMid->id, $midRiskNew->id]);
        $this->assertListedIds('?sort=highest_risk', [$highRiskMid->id, $midRiskNew->id, $lowRiskOld->id]);
        $this->assertListedIds('?sort=lowest_risk', [$lowRiskOld->id, $midRiskNew->id, $highRiskMid->id]);
    }

    public function test_an_invalid_sort_falls_back_to_newest(): void
    {
        $older = Item::factory()->create(['created_at' => now()->subDay()]);
        $newer = Item::factory()->create(['created_at' => now()]);

        $this->assertListedIds('?sort=title;drop table items', [$newer->id, $older->id]);
    }

    public function test_equal_sort_values_are_ordered_by_id(): void
    {
        $sameTime = now();
        $first = Item::factory()->create(['risk_score' => 40, 'created_at' => $sameTime]);
        $second = Item::factory()->create(['risk_score' => 40, 'created_at' => $sameTime]);

        $this->assertListedIds('?sort=newest', [$second->id, $first->id]);
        $this->assertListedIds('?sort=oldest', [$first->id, $second->id]);
        $this->assertListedIds('?sort=highest_risk', [$second->id, $first->id]);
    }

    public function test_unknown_params_are_ignored(): void
    {
        Item::factory()->count(2)->create();

        $this->getJson('/api/v1/items?color=blue&limit=1')->assertOk()->assertJsonCount(2, 'data');
    }

    /**
     * @param  list<int>  $expectedIds
     */
    private function assertListedIds(string $queryString, array $expectedIds): void
    {
        $this->getJson('/api/v1/items'.$queryString)
            ->assertOk()
            ->assertJsonPath('data.*.id', $expectedIds);
    }
}
