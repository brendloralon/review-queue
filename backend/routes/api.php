<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\BulkReviewItemsController;
use App\Http\Controllers\Api\V1\ItemController;
use App\Http\Controllers\Api\V1\ReviewItemController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('items', [ItemController::class, 'index']);
    Route::post('items', [ItemController::class, 'store']);
    Route::post('items/bulk-review', BulkReviewItemsController::class);
    Route::get('items/{item}', [ItemController::class, 'show']);
    Route::post('items/{item}/review', ReviewItemController::class);
});
