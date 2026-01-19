<?php

namespace App\Http\Controllers\Admin\Circles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Circles\StoreCircleRequest;
use App\Http\Requests\Admin\Circles\UpdateCircleRequest;
use App\Models\Circle;
use App\Models\CircleMember;
use App\Models\City;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CircleController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->input('q', $request->input('search'));
        $status = $request->input('status');
        $cityId = $request->input('city_id');
        $type = $request->input('type');

        $query = Circle::query()->with(['founder', 'city'])->withCount('members');

        if ($search) {
            $query->where('name', 'ILIKE', "%{$search}%");
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($cityId) {
            $query->where('city_id', $cityId);
        }

        if ($type) {
            $query->where('type', $type);
        }

        $circles = $query->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $statuses = Circle::query()
            ->select('status')
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        $cities = City::query()->orderBy('name')->get();

        return view('admin.circles.index', [
            'circles' => $circles,
            'statuses' => $statuses,
            'cities' => $cities,
            'types' => Circle::TYPE_OPTIONS,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'city_id' => $cityId,
                'type' => $type,
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $defaultFounder = $this->defaultFounderUser();
        $countries = $this->countriesList();
        $selectedCountry = $request->input('country', $countries->first() ?? 'India');

        $cities = City::query()
            ->when($selectedCountry, fn ($query) => $query->where('country', $selectedCountry))
            ->orderBy('name')
            ->get();

        return view('admin.circles.create', [
            'circle' => new Circle(),
            'countries' => $countries,
            'selectedCountry' => $selectedCountry,
            'cities' => $cities,
            'statuses' => Circle::STATUS_OPTIONS,
            'types' => Circle::TYPE_OPTIONS,
            'defaultFounder' => $defaultFounder,
            'defaultFounderLabel' => $this->founderLabel($defaultFounder),
        ]);
    }

    public function store(StoreCircleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['industry_tags'] = $this->normalizeIndustryTags($data['industry_tags'] ?? null);

        if (empty($data['status'])) {
            unset($data['status']);
        }

        $circle = new Circle($data);
        $circle->save();

        return redirect()
            ->route('admin.circles.show', $circle)
            ->with('success', 'Circle created successfully.');
    }

    public function show(Request $request, Circle $circle): View
    {
        $circle->load(['city', 'founder', 'members.user']);

        $memberSearch = $request->input('member_search');
        $memberCandidates = collect();

        if ($memberSearch) {
            $existingMemberIds = $circle->members->pluck('user_id');
            $memberCandidates = User::query()
                ->where(function ($query) use ($memberSearch) {
                    $query->where('display_name', 'ILIKE', "%{$memberSearch}%")
                        ->orWhere('first_name', 'ILIKE', "%{$memberSearch}%")
                        ->orWhere('last_name', 'ILIKE', "%{$memberSearch}%")
                        ->orWhere('email', 'ILIKE', "%{$memberSearch}%");
                })
                ->when($existingMemberIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $existingMemberIds))
                ->orderBy('display_name')
                ->limit(25)
                ->get();
        }

        return view('admin.circles.show', [
            'circle' => $circle,
            'memberCandidates' => $memberCandidates,
            'memberSearch' => $memberSearch,
            'roles' => CircleMember::roleOptions(),
        ]);
    }

    public function edit(Request $request, Circle $circle): View
    {
        $circle->load('city');

        $defaultFounder = $circle->founder ?? $this->defaultFounderUser();
        $countries = $this->countriesList();
        $selectedCountry = $request->input('country', $circle->city?->country ?? $countries->first() ?? 'India');

        $cities = City::query()
            ->when($selectedCountry, fn ($query) => $query->where('country', $selectedCountry))
            ->orderBy('name')
            ->get();

        return view('admin.circles.edit', [
            'circle' => $circle,
            'countries' => $countries,
            'selectedCountry' => $selectedCountry,
            'cities' => $cities,
            'statuses' => Circle::STATUS_OPTIONS,
            'types' => Circle::TYPE_OPTIONS,
            'defaultFounder' => $defaultFounder,
            'defaultFounderLabel' => $this->founderLabel($defaultFounder),
        ]);
    }

    public function update(UpdateCircleRequest $request, Circle $circle): RedirectResponse
    {
        $data = $request->validated();
        $data['industry_tags'] = $this->normalizeIndustryTags($data['industry_tags'] ?? null);

        $originalName = $circle->name;

        $circle->fill($data);

        if ($circle->name !== $originalName) {
            $circle->slug = Circle::generateUniqueSlug($circle->name, $circle->id);
        }

        $circle->save();

        return redirect()
            ->route('admin.circles.show', $circle)
            ->with('success', 'Circle updated successfully.');
    }

    private function normalizeIndustryTags(null|string|array $tags): ?array
    {
        if (is_array($tags)) {
            return array_values(array_filter(array_map('trim', $tags)));
        }

        if (is_string($tags)) {
            $trimmed = array_filter(array_map('trim', explode(',', $tags)));
            return $trimmed ? array_values($trimmed) : null;
        }

        return null;
    }

    private function countriesList()
    {
        $countries = City::query()
            ->select('country')
            ->whereNotNull('country')
            ->distinct()
            ->orderBy('country')
            ->pluck('country');

        if (!$countries->contains('India')) {
            $countries->prepend('India');
        }

        return $countries->unique()->values();
    }

    private function defaultFounderUser(): ?User
    {
        $admin = Auth::guard('admin')->user();

        if (! $admin) {
            return null;
        }

        return User::query()->where('email', $admin->email)->first();
    }

    private function founderLabel(?User $user): string
    {
        if (! $user) {
            return '';
        }

        $name = $user->display_name
            ?? trim($user->first_name . ' ' . ($user->last_name ?? ''));

        $label = trim($name);

        if ($user->email) {
            $label = $label !== '' ? $label . " ({$user->email})" : $user->email;
        }

        return $label;
    }
}
