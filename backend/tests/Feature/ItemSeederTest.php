<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ItemStatus;
use App\Enums\SuggestedAction;
use App\Models\Item;
use App\Moderation\Rules\BlocklistedTermsRule;
use App\Moderation\Rules\LinksRule;
use App\Moderation\Rules\ShoutingRule;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_items_trigger_every_rule_and_suggested_action(): void
    {
        $this->seed(DatabaseSeeder::class);

        $items = Item::all();
        $seededFlags = $items->pluck('flags')->flatten()->unique();

        foreach ([
            BlocklistedTermsRule::FLAG,
            ShoutingRule::FLAG,
            LinksRule::FLAG_SOME_LINKS,
            LinksRule::FLAG_MANY_LINKS,
        ] as $flag) {
            $this->assertContains($flag, $seededFlags, "No seeded item triggers {$flag}.");
        }

        foreach (SuggestedAction::cases() as $action) {
            $this->assertTrue($items->contains('suggested_action', $action), "No seeded item suggests {$action->value}.");
        }

        foreach (ItemStatus::cases() as $status) {
            $this->assertTrue($items->contains('status', $status), "No seeded item is {$status->value}.");
        }
    }
}
