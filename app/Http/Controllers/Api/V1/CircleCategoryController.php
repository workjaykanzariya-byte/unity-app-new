<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CircleCategory;
use Illuminate\Http\JsonResponse;

class CircleCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $items = CircleCategory::query()
            ->select([
                'id',
                'name',
                'slug',
                'circle_key',
                'level',
                'sort_order',
                'is_active',
            ])
            ->where('level', 1)
            ->where('is_active', true)
            ->withCount([
                'level2Categories as child_level2_count',
                'level3Categories as child_level3_count',
                'level4Categories as child_level4_count',
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => null,
            'data' => [
                'items' => $items,
            ],
        ]);
    }

    public function show(string $idOrSlug): JsonResponse
    {
        $categoryQuery = CircleCategory::query()->where('level', 1);

        if (ctype_digit($idOrSlug)) {
            $categoryQuery->where('id', (int) $idOrSlug);
        } else {
            $categoryQuery->where('slug', $idOrSlug);
        }

        $category = $categoryQuery->first();

        if (! $category) {
            return response()->json([
                'success' => false,
                'message' => 'Circle category not found.',
                'data' => null,
            ], 404);
        }

        $level2Categories = $category->level2Categories()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $level3Categories = $category->level3Categories()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $level4Categories = $category->level4Categories()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $level2Count = $level2Categories->count();
        $level3Count = $level3Categories->count();
        $level4Count = $level4Categories->count();
        $children = $this->buildNestedChildren($level2Categories, $level3Categories, $level4Categories);

        return response()->json([
            'success' => true,
            'message' => null,
            'data' => [
                'id' => $category->id,
                'parent_id' => $category->parent_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'circle_key' => $category->circle_key,
                'level' => $category->level,
                'sort_order' => $category->sort_order,
                'is_active' => (bool) $category->is_active,
                'created_at' => $category->created_at,
                'updated_at' => $category->updated_at,
                'counts' => [
                    'level2' => $level2Count,
                    'level3' => $level3Count,
                    'level4' => $level4Count,
                    'total_children' => $level2Count + $level3Count + $level4Count,
                ],
                'level2_categories' => $level2Categories,
                'level3_categories' => $level3Categories,
                'level4_categories' => $level4Categories,
                'children' => $children,
            ],
        ]);
    }

    private function buildNestedChildren($level2Categories, $level3Categories, $level4Categories): array
    {
        $level4ByLevel3 = [];
        foreach ($level4Categories as $level4) {
            $level3Id = $level4->level3_id ?? $level4->circle_category_level3_id ?? null;
            if ($level3Id === null) {
                continue;
            }

            $level4ByLevel3[$level3Id][] = [
                'id' => $level4->id,
                'name' => $level4->name,
                'level' => 4,
            ];
        }

        $level3ByLevel2 = [];
        foreach ($level3Categories as $level3) {
            $level2Id = $level3->level2_id ?? $level3->circle_category_level2_id ?? null;
            if ($level2Id === null) {
                continue;
            }

            $level3ByLevel2[$level2Id][] = [
                'id' => $level3->id,
                'name' => $level3->name,
                'level' => 3,
                'children' => $level4ByLevel3[$level3->id] ?? [],
            ];
        }

        $children = [];
        foreach ($level2Categories as $level2) {
            $children[] = [
                'id' => $level2->id,
                'name' => $level2->name,
                'level' => 2,
                'children' => $level3ByLevel2[$level2->id] ?? [],
            ];
        }

        return $children;
    }
}
