<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Categories\StoreCategoryRequest;
use App\Http\Requests\Admin\Categories\UpdateCategoryRequest;
use App\Imports\CategoriesImport;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $categories = Category::query()
            ->when($this->hierarchyColumnsAvailable(), function ($query) {
                $query->whereNull('parent_id')
                    ->where('level', 1)
                    ->when(Schema::hasColumn('categories', 'is_active'), fn ($q) => $q->where('is_active', true));
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('category_name', 'ILIKE', '%' . $search . '%')
                        ->orWhere('sector', 'ILIKE', '%' . $search . '%');
                });
            })
            ->orderBy('id')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.categories.index', [
            'categories' => $categories,
            'search' => $search,
        ]);
    }

    public function showHierarchy(Category $category): View
    {
        abort_unless($this->isMainCategory($category), 404);

        $level2Categories = $this->childrenQuery($category->id)->get();
        $level2Ids = $level2Categories->pluck('id');

        $level3Count = $level2Ids->isEmpty()
            ? 0
            : Category::query()->whereIn('parent_id', $level2Ids)->count();

        $level3Ids = $level2Ids->isEmpty()
            ? collect()
            : Category::query()->whereIn('parent_id', $level2Ids)->pluck('id');

        $level4Count = $level3Ids->isEmpty()
            ? 0
            : Category::query()->whereIn('parent_id', $level3Ids)->count();

        return view('admin.categories.view', [
            'category' => $category,
            'level2Categories' => $level2Categories,
            'counts' => [
                'level2' => $level2Categories->count(),
                'level3' => $level3Count,
                'level4' => $level4Count,
            ],
        ]);
    }

    public function children(Category $category, Request $request): JsonResponse
    {
        $items = $this->childrenQuery($category->id)
            ->when($request->filled('level'), fn ($query) => $query->where('level', (int) $request->query('level')))
            ->get()
            ->map(fn (Category $item) => [
                'id' => $item->id,
                'name' => $item->category_name,
                'level' => $item->level,
                'sector' => $item->sector,
                'remarks' => $item->remarks,
                'parent_name' => $category->category_name,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'message' => null,
            'data' => $items,
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.create', [
            'category' => new Category(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $payload = $this->filterCategoryPayload($request->validated());
        $payload = $this->applyMainCategoryDefaults($payload);

        Category::query()->create($payload);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', [
            'category' => $category,
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $payload = $this->filterCategoryPayload($request->validated());
        $payload = $this->preserveMainCategoryState($category, $payload);

        $category->update($payload);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        try {
            if (
                $category->circleMappings()->exists() ||
                (
                    DB::getSchemaBuilder()->hasColumn('event_galleries', 'circle_category_id') &&
                    DB::table('event_galleries')->where('circle_category_id', $category->id)->exists()
                )
            ) {
                return redirect()
                    ->route('admin.categories.index')
                    ->with('error', 'This category is in use and cannot be deleted.');
            }

            $category->delete();

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Category deleted successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        try {
            $search = trim((string) $request->query('q', ''));

            $categories = Category::query()
                ->select([
                    'id',
                    'category_name',
                    'sector',
                    'remarks',
                    'created_at',
                    'updated_at',
                ])
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($subQuery) use ($search) {
                        $subQuery
                            ->where('category_name', 'ILIKE', '%' . $search . '%')
                            ->orWhere('sector', 'ILIKE', '%' . $search . '%');
                    });
                })
                ->orderBy('id')
                ->get();

            return response()->streamDownload(
                function () use ($categories): void {
                    $handle = fopen('php://output', 'w');

                    if ($handle === false) {
                        throw new \RuntimeException('Could not open output stream for CSV export.');
                    }

                    fwrite($handle, "\xEF\xBB\xBF");
                    fputcsv($handle, ['ID', 'Category Name', 'Sector', 'Remarks', 'Created At', 'Updated At']);

                    foreach ($categories as $category) {
                        fputcsv($handle, [
                            $category->id,
                            (string) ($category->category_name ?? ''),
                            (string) ($category->sector ?? ''),
                            (string) ($category->remarks ?? ''),
                            (string) ($category->created_at ?? ''),
                            (string) ($category->updated_at ?? ''),
                        ]);
                    }

                    fclose($handle);
                },
                'categories.csv',
                [
                    'Content-Type' => 'text/csv; charset=UTF-8',
                ]
            );
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->with('error', 'Unable to export categories: ' . $e->getMessage());
        }
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt,xlsx',
        ]);

        try {
            $result = (new CategoriesImport())->import($request->file('file'));
        } catch (\Throwable $e) {
            return redirect()
                ->back()
                ->with('error', 'Unable to import categories: ' . $e->getMessage());
        }

        return redirect()
            ->back()
            ->with('success', "Categories import completed. Imported: {$result['imported_count']}, Skipped duplicates: {$result['skipped_duplicate_count']}, Skipped empty: {$result['skipped_empty_count']}")
            ->with('imported_count', $result['imported_count'])
            ->with('skipped_duplicate_count', $result['skipped_duplicate_count'])
            ->with('skipped_empty_count', $result['skipped_empty_count']);
    }

    private function filterCategoryPayload(array $payload): array
    {
        return collect($payload)
            ->filter(fn ($value, $key) => Schema::hasColumn('categories', $key))
            ->all();
    }

    private function childrenQuery(int $parentId)
    {
        return Category::query()
            ->where('parent_id', $parentId)
            ->when(Schema::hasColumn('categories', 'is_active'), fn ($query) => $query->where('is_active', true))
            ->orderByRaw(Schema::hasColumn('categories', 'sort_order') ? 'sort_order ASC NULLS LAST' : 'id ASC')
            ->orderBy('category_name');
    }

    private function hierarchyColumnsAvailable(): bool
    {
        return Schema::hasColumn('categories', 'parent_id') && Schema::hasColumn('categories', 'level');
    }

    private function isMainCategory(Category $category): bool
    {
        if (! $this->hierarchyColumnsAvailable()) {
            return true;
        }

        return $category->parent_id === null && (int) $category->level === 1;
    }

    private function applyMainCategoryDefaults(array $payload): array
    {
        if (! $this->hierarchyColumnsAvailable()) {
            return $payload;
        }

        if (! array_key_exists('parent_id', $payload)) {
            $payload['parent_id'] = null;
        }

        if (! array_key_exists('level', $payload) || $payload['level'] === null) {
            $payload['level'] = 1;
        }

        if (Schema::hasColumn('categories', 'is_active') && ! array_key_exists('is_active', $payload)) {
            $payload['is_active'] = true;
        }

        return $payload;
    }

    private function preserveMainCategoryState(Category $category, array $payload): array
    {
        if (! $this->isMainCategory($category)) {
            return $payload;
        }

        if (array_key_exists('parent_id', $payload) || array_key_exists('level', $payload)) {
            return $payload;
        }

        return array_merge($payload, [
            'parent_id' => null,
            'level' => 1,
        ]);
    }
}
