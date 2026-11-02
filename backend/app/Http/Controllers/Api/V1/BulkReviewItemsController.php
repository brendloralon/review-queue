<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\BulkReviewItemsRequest;
use App\Http\Resources\V1\BulkReviewResource;
use App\Reviews\BulkReviewer;

class BulkReviewItemsController extends Controller
{
    /**
     * Always 200 for a valid request: items that could not be reviewed are listed under
     * `failed` with the same message the single review endpoint returns with its 409.
     */
    public function __invoke(BulkReviewItemsRequest $request, BulkReviewer $bulkReviewer): BulkReviewResource
    {
        $result = $bulkReviewer->review($request->itemIds(), $request->decision(), $request->note());

        return BulkReviewResource::make($result);
    }
}
