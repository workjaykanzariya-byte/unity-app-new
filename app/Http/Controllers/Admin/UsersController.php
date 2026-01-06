<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\City;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UsersController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()
            ->select([
                'id',
                'email',
                'phone',
                'first_name',
                'last_name',
                'display_name',
                'designation',
                'company_name',
                'profile_photo_url',
                'short_bio',
                'long_bio_html',
                'business_type',
                'industry_tags',
                'turnover_range',
                'city_id',
                'membership_status',
                'membership_expiry',
                'introduced_by',
                'members_introduced_count',
                'target_regions',
                'target_business_categories',
                'hobbies_interests',
                'leadership_roles',
                'is_sponsored_member',
                'public_profile_slug',
                'special_recognitions',
                'gdpr_deleted_at',
                'anonymized_at',
                'is_gdpr_exported',
                'coins_balance',
                'influencer_stars',
                'last_login_at',
                'created_at',
                'updated_at',
                'city',
                'skills',
                'interests',
                'gender',
                'dob',
                'experience_years',
                'experience_summary',
                'profile_photo_file_id',
                'cover_photo_file_id',
                'deleted_at',
            ])
            ->with('city');

        $search = $request->input('q', $request->input('search'));
        $membership = $request->input('membership_status');
        $cityId = $request->input('city_id', $request->input('city'));
        $phone = $request->input('phone');
        $company = $request->input('company_name');
        $perPage = $request->integer('per_page') ?: 20;

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('display_name', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%")
                    ->orWhere('first_name', 'ILIKE', "%{$search}%")
                    ->orWhere('last_name', 'ILIKE', "%{$search}%");
            });
        }

        if ($membership && $membership !== 'all') {
            $query->where('membership_status', $membership);
        }

        if ($cityId && $cityId !== 'all') {
            $query->where('city_id', $cityId);
        }

        if ($phone) {
            $query->where('phone', 'ILIKE', "%{$phone}%");
        }

        if ($company) {
            $query->where('company_name', 'ILIKE', "%{$company}%");
        }

        $sortable = ['display_name', 'coins_balance', 'last_login_at', 'created_at'];
        $sort = $request->input('sort');
        $direction = $request->input('dir', 'desc') === 'asc' ? 'asc' : 'desc';

        if ($sort && in_array($sort, $sortable, true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderByDesc('last_login_at');
        }

        $perPage = in_array($perPage, [10, 20, 25, 50, 100], true) ? $perPage : 20;

        $users = $query->paginate($perPage)->withQueryString();

        $membershipStatuses = User::query()
            ->whereNotNull('membership_status')
            ->distinct()
            ->pluck('membership_status')
            ->sort()
            ->values();

        $cities = City::query()->orderBy('name')->get();

        return view('admin.users.index', [
            'users' => $users,
            'membershipStatuses' => $membershipStatuses,
            'cities' => $cities,
            'filters' => [
                'search' => $search,
                'membership_status' => $membership,
                'city_id' => $cityId,
                'phone' => $phone,
                'company_name' => $company,
                'per_page' => $perPage,
                'sort' => $sort,
                'dir' => $direction,
            ],
        ]);
    }

    public function edit(string $userId): View
    {
        $user = User::query()->with(['city', 'roles'])->findOrFail($userId);
        $cities = City::query()->orderBy('name')->get();
        $roles = Role::query()->orderBy('name')->get();
        $membershipStatuses = $this->membershipStatuses();

        return view('admin.users.edit', [
            'user' => $user,
            'cities' => $cities,
            'roles' => $roles,
            'membershipStatuses' => $membershipStatuses,
            'userRoleIds' => $user->roles->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, string $userId)
    {
        $user = User::query()->findOrFail($userId);

        $membershipStatuses = $this->membershipStatuses();
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'display_name' => ['nullable', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'designation' => ['nullable', 'string', 'max:100'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'business_type' => ['nullable', 'string', 'max:100'],
            'turnover_range' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', 'string', 'max:20'],
            'dob' => ['nullable', 'date'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:100'],
            'experience_summary' => ['nullable', 'string'],
            'short_bio' => ['nullable', 'string'],
            'long_bio_html' => ['nullable', 'string'],
            'public_profile_slug' => ['nullable', 'string', 'max:80', 'unique:users,public_profile_slug,' . $user->id],
            'membership_status' => ['required', 'in:' . implode(',', $membershipStatuses)],
            'membership_expiry' => ['nullable', 'date'],
            'coins_balance' => ['required', 'integer', 'min:0'],
            'influencer_stars' => ['nullable', 'integer', 'min:0'],
            'is_sponsored_member' => ['boolean'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'city' => ['nullable', 'string', 'max:150'],
            'introduced_by' => ['nullable', 'exists:users,id'],
            'members_introduced_count' => ['nullable', 'integer', 'min:0'],
            'profile_photo_file_id' => ['nullable', 'uuid'],
            'cover_photo_file_id' => ['nullable', 'uuid'],
            'industry_tags' => ['nullable', 'string', 'max:10000'],
            'target_regions' => ['nullable', 'string', 'max:10000'],
            'target_business_categories' => ['nullable', 'string', 'max:10000'],
            'hobbies_interests' => ['nullable', 'string', 'max:10000'],
            'leadership_roles' => ['nullable', 'string', 'max:10000'],
            'special_recognitions' => ['nullable', 'string', 'max:10000'],
            'skills' => ['nullable', 'string', 'max:10000'],
            'interests' => ['nullable', 'string', 'max:10000'],
            'role_ids' => ['array'],
            'role_ids.*' => ['exists:roles,id'],
        ]);

        $csvFields = [
            'industry_tags',
            'target_regions',
            'target_business_categories',
            'hobbies_interests',
            'leadership_roles',
            'special_recognitions',
            'skills',
            'interests',
        ];

        foreach ($csvFields as $field) {
            $validated[$field] = $this->csvToArray($request->input($field, ''));
        }

        $booleanFields = ['is_sponsored_member'];
        foreach ($booleanFields as $field) {
            $validated[$field] = $request->boolean($field);
        }

        $updatable = Arr::except($validated, ['role_ids', 'profile_photo_file_id', 'cover_photo_file_id']);

        DB::transaction(function () use ($user, $updatable, $validated, $request) {
            $user->fill($updatable);

            if ($request->filled('profile_photo_file_id')) {
                $user->profile_photo_file_id = $request->input('profile_photo_file_id');
            }

            if ($request->filled('cover_photo_file_id')) {
                $user->cover_photo_file_id = $request->input('cover_photo_file_id');
            }

            $user->save();

            if ($request->filled('role_ids')) {
                $adminUser = AdminUser::query()->where('user_id', $user->id)->first();

                if (! $adminUser) {
                    $adminUser = AdminUser::create([
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'name' => $user->display_name ?? $user->first_name,
                        'status' => 'active',
                    ]);
                }

                $adminUser->roles()->sync($validated['role_ids']);
            }
        });

        return redirect()->route('admin.users.edit', $user->id)
            ->with('status', 'User updated successfully.');
    }

    private function membershipStatuses(): array
    {
        return [
            'visitor',
            'premium',
            'charter',
            'suspended',
        ];
    }

    private function csvToArray(?string $value): array
    {
        if ($value === null) {
            return [];
        }

        $parts = array_map('trim', explode(',', $value));
        $parts = array_filter($parts, fn ($v) => $v !== '');

        return array_values($parts);
    }
}
