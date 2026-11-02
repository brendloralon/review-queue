<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreItemRequest;
use App\Http\Resources\V1\ItemResource;
use App\Models\Item;
use App\Moderation\ModerationService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ItemController extends Controller
{
    public function store(StoreItemRequest $request, ModerationService $moderation): JsonResponse
    {
        $validated = $request->validated();
        $moderationResult = $moderation->moderate($validated['title'], $validated['body']);

        $item = Item::create([...$validated, ...$moderationResult->toAttributes()]);

        return ItemResource::make($item)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
