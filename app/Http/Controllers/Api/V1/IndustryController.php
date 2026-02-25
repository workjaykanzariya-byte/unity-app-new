<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\IndustryTreeResource;
use App\Models\Industry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndustryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Industry::query()->where('is_active', true);

        if ($request->filled('search')) {
            $query->where('name', 'ILIKE', '%' . $request->string('search') . '%');

            return response()->json([
                'status' => true,
                'message' => 'Industries fetched successfully.',
                'data' => IndustryTreeResource::collection($query->orderBy('name')->limit(100)->get()),
            ]);
        }

        if ((int) $request->query('tree') === 1) {
            $roots = $query
                ->whereNull('parent_id')
                ->with(['children' => fn ($q) => $q->where('is_active', true)->with(['children' => fn ($q2) => $q2->where('is_active', true)->orderBy('name')])->orderBy('name')])
                ->orderBy('name')
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Industry tree fetched successfully.',
                'data' => IndustryTreeResource::collection($roots),
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Industries fetched successfully.',
            'data' => IndustryTreeResource::collection($query->orderBy('name')->paginate(20)),
        ]);
    }
}
