<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\PostReportReason;
use Illuminate\Http\JsonResponse;

class PostReportReasonsController extends BaseApiController
{
    public function index(): JsonResponse
    {
        $items = PostReportReason::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'title', 'sort_order']);

        return $this->success([
            'items' => $items,
        ]);
    }
}
