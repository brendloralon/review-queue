<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Reviews\BulkReviewResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property BulkReviewResult $resource
 */
class BulkReviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'reviewed' => ItemResource::collection($this->resource->reviewed),
            'failed' => $this->failedItems(),
        ];
    }

    /**
     * @return list<array{id: int, message: string}>
     */
    private function failedItems(): array
    {
        $failedItems = [];

        foreach ($this->resource->failed as $itemId => $message) {
            $failedItems[] = ['id' => $itemId, 'message' => $message];
        }

        return $failedItems;
    }
}
