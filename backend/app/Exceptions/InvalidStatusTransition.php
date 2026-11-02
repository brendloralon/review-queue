<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\ItemStatus;
use App\Models\Item;
use RuntimeException;

final class InvalidStatusTransition extends RuntimeException
{
    public static function for(Item $item, ItemStatus $target): self
    {
        $message = $item->status->isReviewed()
            ? "Item {$item->id} has already been {$item->status->value}."
            : "Item {$item->id} cannot move from {$item->status->value} to {$target->value}.";

        return new self($message);
    }
}
