<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdminUsersController extends Controller
{
    protected array $excludedFields = [
        'id',
        'password',
        'password_hash',
        'remember_token',
        'created_at',
        'updated_at',
    ];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $query = User::query();
        if (Schema::hasColumn('users', 'created_at')) {
            $query->orderByDesc('created_at');
        }

        $searchableColumns = array_filter([
            'first_name',
            'last_name',
            'display_name',
            'email',
        ], static fn (string $column): bool => Schema::hasColumn('users', $column));

        if ($search !== '' && ! empty($searchableColumns)) {
            $query->where(function ($subQuery) use ($search, $searchableColumns): void {
                foreach ($searchableColumns as $column) {
                    $subQuery->orWhere($column, 'ILIKE', '%' . $search . '%');
                }
            });
        }

        /** @var LengthAwarePaginator $users */
        $users = $query->paginate(20);

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
            'hasMembershipStatus' => Schema::hasColumn('users', 'membership_status'),
        ]);
    }

    public function edit(string $id): View
    {
        $user = User::findOrFail($id);

        $columns = Schema::getColumnListing($user->getTable());
        $fillable = $user->getFillable();

        $fields = array_values(array_filter($fillable, function (string $field) use ($columns): bool {
            return in_array($field, $columns, true) && ! in_array($field, $this->excludedFields, true);
        }));

        $roles = Role::all();
        $userRoleIds = $user->adminRoles()->pluck('roles.id')->toArray();

        $canManageRoles = Auth::guard('admin')->user()?->adminRoles()->where('key', 'global_admin')->exists();

        return view('admin.users.edit', [
            'user' => $user,
            'fields' => $fields,
            'casts' => $user->getCasts(),
            'roles' => $roles,
            'userRoleIds' => $userRoleIds,
            'canManageRoles' => $canManageRoles,
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $columns = Schema::getColumnListing($user->getTable());
        $fillable = $user->getFillable();

        $fields = array_values(array_filter($fillable, function (string $field) use ($columns): bool {
            return in_array($field, $columns, true) && ! in_array($field, $this->excludedFields, true);
        }));

        $rules = [];

        foreach ($fields as $field) {
            if ($field === 'email') {
                $rules[$field] = ['required', 'email'];
            } elseif (in_array($field, ['membership_expiry', 'dob', 'last_login_at', 'gdpr_deleted_at', 'anonymized_at'], true)) {
                $rules[$field] = ['nullable', 'date'];
            } elseif (in_array($field, ['coins_balance', 'members_introduced_count', 'influencer_stars', 'experience_years'], true)) {
                $rules[$field] = ['nullable', 'numeric'];
            } else {
                $rules[$field] = ['nullable'];
            }
        }

        $validated = $request->validate($rules);

        $casts = $user->getCasts();
        foreach ($validated as $field => $value) {
            if (($casts[$field] ?? null) === 'array') {
                $validated[$field] = $value !== null && $value !== ''
                    ? array_values(array_filter(array_map('trim', explode(',', (string) $value))))
                    : [];
            }

            if (in_array($field, ['membership_expiry', 'dob', 'last_login_at', 'gdpr_deleted_at', 'anonymized_at'], true)) {
                $validated[$field] = $value ? Carbon::parse($value) : null;
            }
        }

        $user->fill($validated);
        $user->save();

        $canManageRoles = Auth::guard('admin')->user()?->adminRoles()->where('key', 'global_admin')->exists();

        if ($canManageRoles) {
            $request->validate([
                'roles' => ['array'],
                'roles.*' => ['uuid'],
            ]);
            $roleIds = array_filter(Arr::wrap($request->input('roles', [])), static fn ($value): bool => ! empty($value));
            $validRoleIds = Role::whereIn('id', $roleIds)->pluck('id')->all();
            $user->adminRoles()->sync($validRoleIds);
        }

        return redirect()->back()->with('status', 'User updated successfully.');
    }
}
