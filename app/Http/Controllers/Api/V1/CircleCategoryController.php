<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\CircleCategoryNodeResource;
use App\Http\Resources\CircleCategoryResource;
use App\Models\Category;
use App\Services\CircleCategoryHierarchyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CircleCategoryController extends BaseApiController
{
    public function __construct(
        private readonly CircleCategoryHierarchyService $hierarchyService
    ) {
    }

    public function main()
    {
        $data = $this->hierarchyService->getMainCircles()
            ->map(static function (Category $category): array {
                $childrenCount = (int) ($category->children_count ?? 0);

                return [
                    'id' => $category->id,
                    'name' => $category->category_name,
                    'parent_id' => $category->parent_id,
                    'level' => (int) $category->level,
                    'has_children' => $childrenCount > 0,
                    'children_count' => $childrenCount,
                ];
            })
            ->values();

        return $this->success($data);
    }

    public function children(int $id)
    {
        if (! Category::query()->whereKey($id)->exists()) {
            return $this->error('Category not found', 404);
        }

        $categories = $this->hierarchyService->getChildren($id);

        return $this->success(CircleCategoryNodeResource::collection($categories));
    }

    public function tree(int $id)
    {
        $tree = $this->hierarchyService->getTree($id);

        if (! $tree) {
            return $this->error('Category not found', 404);
        }

        return $this->success(new CircleCategoryNodeResource($tree));
    }

    public function final(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $parentId = $request->integer('parent_id');
        $categories = $this->hierarchyService->getFinalCategories($request->has('parent_id') ? $parentId : null);

        return $this->success(CircleCategoryResource::collection($categories));
    }
}
