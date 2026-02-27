<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Requirement\RequirementTimelineResource;
use App\Models\Requirement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimelineRequirementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Requirement::query()
            ->with('user:id,first_name,last_name,display_name,company_name,city,profile_photo_file_id')
            ->where('status', 'open')
            ->whereNull('deleted_at')
            ->orderByDesc('created_at');

        if ($request->filled('region')) {
            $region = $request->string('region')->toString();
            $query->whereJsonContains('region_filter', $region);
        }

        if ($request->filled('category')) {
            $category = $request->string('category')->toString();
            $query->whereJsonContains('category_filter', $category);
        }

        $perPage = max(1, min((int) $request->query('per_page', 20), 100));
        $paginated = $query->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'Open requirements fetched successfully.',
            'data' => RequirementTimelineResource::collection($paginated->items()),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }
}
