<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ReviewItemRequest;
use App\Http\Resources\V1\ItemResource;
use App\Models\Item;

class ReviewItemController extends Controller
{
    public function __invoke(ReviewItemRequest $request, Item $item): ItemResource
    {
        $item->review($request->decision(), $request->note());

        return ItemResource::make($item);
    }
}
