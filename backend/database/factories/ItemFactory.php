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

    public function approved(): static
    {
        return $this->reviewedAs(ItemStatus::Approved);
    }

    public function rejected(): static
    {
        return $this->reviewedAs(ItemStatus::Rejected);
    }

    private function reviewedAs(ItemStatus $status): static
    {
        return $this->state([
            'status' => $status,
            'reviewed_at' => now(),
        ]);
    }
}
