<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Item
 */
class ItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'submitter_name' => $this->submitter_name,
            'status' => $this->status->value,
            'risk_score' => $this->risk_score,
            'flags' => $this->flags,
            'suggested_action' => $this->suggested_action->value,
            'review_note' => $this->review_note,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
