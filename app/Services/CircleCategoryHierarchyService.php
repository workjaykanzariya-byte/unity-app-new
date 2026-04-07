<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;

class CircleCategoryHierarchyService
{
    public function getMainCircles(): Collection
    {
        return Category::query()
            ->active()
            ->whereNull('parent_id')
            ->where('level', 1)
            ->withCount('children')
            ->ordered()
            ->get();
    }

    public function getChildren(int $parentId): Collection
    {
        if (! Category::hierarchyColumnsAvailable()) {
            return new Collection();
        }

        return Category::query()
            ->active()
            ->where('parent_id', $parentId)
            ->withCount('children')
            ->ordered()
            ->get();
    }

    public function getTree(int $mainCategoryId): ?Category
    {
        $mainCircle = Category::query()
            ->active()
            ->when(Category::hierarchyColumnsAvailable(), fn ($query) => $query->withCount('children'))
            ->find($mainCategoryId);

        if (! $mainCircle) {
            return null;
        }

        $mainCircle->setRelation(
            'childrenRecursive',
            $this->buildChildrenTree($mainCircle->id, 2)
        );

        return $mainCircle;
    }

    public function getFinalCategories(?int $parentId): Collection
    {
        $query = Category::query()->active();

        if ($parentId !== null) {
            $query->where('parent_id', $parentId);
        } elseif (Category::hierarchyColumnsAvailable()) {
            $query->finalCategories();
        }

        if (Category::hierarchyColumnsAvailable()) {
            $query->withCount('children');
        }

        return $query->ordered()->get();
    }

    public function hierarchyReady(): bool
    {
        return Schema::hasColumn('categories', 'parent_id')
            && Schema::hasColumn('categories', 'level');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Category>
     */
    private function buildChildrenTree(int $parentId, int $expectedLevel): Collection
    {
        if (! Category::hierarchyColumnsAvailable()) {
            return new Collection();
        }

        $children = Category::query()
            ->active()
            ->where('parent_id', $parentId)
            ->when(
                Schema::hasColumn('categories', 'level'),
                fn ($query) => $query->where('level', $expectedLevel)
            )
            ->withCount('children')
            ->ordered()
            ->get();

        if ($children->isEmpty() || $expectedLevel >= 4) {
            return $children;
        }

        $children->each(function (Category $category) use ($expectedLevel): void {
            $category->setRelation(
                'childrenRecursive',
                $this->buildChildrenTree($category->id, $expectedLevel + 1)
            );
        });

        return $children;
    }
}
