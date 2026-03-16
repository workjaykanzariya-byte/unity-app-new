<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Circulars\StoreCircularRequest;
use App\Http\Requests\Admin\Circulars\UpdateCircularRequest;
use App\Models\Circular;
use App\Models\Circle;
use App\Models\City;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CircularController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'title' => trim((string) $request->query('title', '')),
            'category' => trim((string) $request->query('category', '')),
            'priority' => trim((string) $request->query('priority', '')),
            'status' => trim((string) $request->query('status', '')),
            'audience_type' => trim((string) $request->query('audience_type', '')),
            'city_id' => trim((string) $request->query('city_id', '')),
            'circle_id' => trim((string) $request->query('circle_id', '')),
        ];

        $query = Circular::query()->with(['city:id,name', 'circle:id,name', 'creator:id,display_name,first_name,last_name']);

        if ($filters['title'] !== '') {
            $query->where('title', 'ILIKE', '%' . $filters['title'] . '%');
        }

        foreach (['category', 'priority', 'status', 'audience_type'] as $field) {
            if ($filters[$field] !== '') {
                $query->where($field, $filters[$field]);
            }
        }

        if ($filters['city_id'] !== '') {
            $query->where('city_id', $filters['city_id']);
        }

        if ($filters['circle_id'] !== '') {
            $query->where('circle_id', $filters['circle_id']);
        }

        $circulars = $query->latest()->paginate(20)->appends($request->query());

        return view('admin.circulars.index', [
            'circulars' => $circulars,
            'filters' => $filters,
            'cities' => City::query()->orderBy('name')->get(['id', 'name']),
            'circles' => Circle::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => Circular::statusOptions(),
            'categoryOptions' => Circular::CATEGORY_OPTIONS,
            'priorityOptions' => Circular::PRIORITY_OPTIONS,
            'audienceOptions' => Circular::AUDIENCE_OPTIONS,
        ]);
    }

    public function create(): View
    {
        return view('admin.circulars.create', $this->formData(new Circular()));
    }

    public function store(StoreCircularRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $circular = new Circular();
        $this->fillAndPersist($circular, $data, true, $request);

        return redirect()->route('admin.circulars.index')->with('success', 'Circular created successfully.');
    }

    public function show(Circular $circular): View
    {
        $circular->load(['city:id,name', 'circle:id,name', 'creator:id,display_name,first_name,last_name', 'updater:id,display_name,first_name,last_name']);

        return view('admin.circulars.show', compact('circular'));
    }

    public function edit(Circular $circular): View
    {
        return view('admin.circulars.edit', $this->formData($circular));
    }

    public function update(UpdateCircularRequest $request, Circular $circular): RedirectResponse
    {
        $data = $request->validated();
        $this->fillAndPersist($circular, $data, false, $request);

        return redirect()->route('admin.circulars.index')->with('success', 'Circular updated successfully.');
    }

    public function destroy(Circular $circular): RedirectResponse
    {
        $circular->delete();

        return redirect()->route('admin.circulars.index')->with('success', 'Circular deleted successfully.');
    }

    private function formData(Circular $circular): array
    {
        return [
            'circular' => $circular,
            'cities' => City::query()->orderBy('name')->get(['id', 'name']),
            'circles' => Circle::query()->orderBy('name')->get(['id', 'name']),
            'statusOptions' => Circular::statusOptions(),
            'categoryOptions' => Circular::CATEGORY_OPTIONS,
            'priorityOptions' => Circular::PRIORITY_OPTIONS,
            'audienceOptions' => Circular::AUDIENCE_OPTIONS,
        ];
    }

    private function fillAndPersist(Circular $circular, array $data, bool $isCreate, Request $request): void
    {
        $disk = config('filesystems.default', 'public');

        if ($request->hasFile('featured_image')) {
            $path = $request->file('featured_image')->store('circulars/featured-images', $disk);
            $data['featured_image_url'] = Storage::disk($disk)->url($path);
            $data['featured_image_file_id'] = null;
        }

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('circulars/attachments', $disk);
            $data['attachment_url'] = Storage::disk($disk)->url($path);
            $data['attachment_file_id'] = null;
        }

        if ($isCreate) {
            $data['created_by'] = Auth::id();
            $data['view_count'] = $data['view_count'] ?? 0;
        }

        $data['updated_by'] = Auth::id();

        $circular->fill($data);
        $circular->save();
    }
}
