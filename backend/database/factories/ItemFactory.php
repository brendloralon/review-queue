<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ItemStatus;
use App\Enums\SuggestedAction;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'submitter_name' => fake()->name(),
            'status' => ItemStatus::Pending,
            'risk_score' => 0,
            'flags' => [],
            'suggested_action' => SuggestedAction::Approve,
        ];
    }
}
