<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\CategoryExport;
use App\Http\Requests\Admin\Categories\StoreCategoryRequest;
use App\Http\Requests\Admin\Categories\UpdateCategoryRequest;
use App\Imports\CategoryImport;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $categories = Category::query()
            ->when($search !== '', fn ($query) => $query->where('category_name', 'ILIKE', '%' . $search . '%'))
            ->orderBy('category_name')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.categories.index', [
            'categories' => $categories,
            'search' => $search,
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
        Category::query()->create($request->validated());

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
        $category->update($request->validated());

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

    public function export()
    {
        return Excel::download(new CategoryExport(), 'categories.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv',
        ]);

        Excel::import(new CategoryImport(), $request->file('file'));

        return redirect()
            ->back()
            ->with('success', 'Categories Imported Successfully');
    }
}
