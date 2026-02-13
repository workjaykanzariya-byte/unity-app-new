<?php

namespace App\Http\Controllers\Api\V1\Circles;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\V1\CircleResource;
use App\Models\Circle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CircleController extends BaseApiController
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'type' => ['nullable', Rule::in(Circle::TYPE_OPTIONS)],
            'stage' => ['nullable', Rule::in(Circle::STAGE_OPTIONS)],
            'city_id' => ['nullable', 'uuid'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Circle::query()->with(['founderUser:id,display_name', 'directorUser:id,display_name', 'industryDirectorUser:id,display_name', 'dedUser:id,display_name']);

        if ($q = trim((string) ($validated['q'] ?? ''))) {
            $query->where(function ($sub) use ($q) {
                $like = '%' . $q . '%';
                $sub->where('name', 'ILIKE', $like)->orWhere('slug', 'ILIKE', $like);
            });
        }

        if (isset($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (isset($validated['stage'])) {
            $query->where('stage', $validated['stage']);
        }

        if (isset($validated['city_id'])) {
            $query->where('city_id', $validated['city_id']);
        }

        $paginator = $query->orderBy('name')->paginate((int) ($validated['per_page'] ?? 20));

        return $this->success([
            'items' => CircleResource::collection($paginator->items()),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
