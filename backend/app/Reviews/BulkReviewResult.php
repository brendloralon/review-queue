<?php

declare(strict_types=1);

namespace App\Reviews;

use App\Models\Item;

final readonly class BulkReviewResult
{
    /**
     * @param  list<Item>  $reviewed
     * @param  array<int, string>  $failed  item id => reason it could not be reviewed
     */
    public function __construct(
        public array $reviewed,
        public array $failed,
    ) {}
}
