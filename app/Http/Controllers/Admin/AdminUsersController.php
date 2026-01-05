<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\AdminUserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUsersController extends Controller
{
    public function show(User $user): View
    {
        $roles = Role::query()->orderBy('name')->get();
        $currentRoleId = AdminUserRole::query()
            ->where('user_id', $user->id)
            ->value('role_id');

        $adminId = auth('admin')->id();
        $canAssign = $adminId ? $this->isGlobalAdmin($adminId) : false;

        return view('admin.users.show', [
            'user' => $user->load('city'),
            'roles' => $roles,
            'currentRoleId' => $currentRoleId,
            'canAssign' => $canAssign,
        ]);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $adminId = auth('admin')->id();

        abort_if(!$adminId || !$this->isGlobalAdmin($adminId), 403);

        $validated = $request->validate([
            'role_id' => ['required', 'string', Rule::exists('roles', 'id')],
        ]);

        DB::transaction(function () use ($validated, $user) {
            AdminUser::query()->updateOrCreate(
                ['id' => $user->id],
                [
                    'name' => $this->resolveAdminName($user),
                    'email' => $user->email,
                ]
            );

            AdminUserRole::query()->where('user_id', $user->id)->delete();

            AdminUserRole::query()->create([
                'id' => DB::raw('gen_random_uuid()'),
                'user_id' => $user->id,
                'role_id' => $validated['role_id'],
                'created_at' => now(),
            ]);
        });

        return redirect()
            ->route('admin.users.show', $user)
            ->with('status', 'Role updated successfully.');
    }

    private function isGlobalAdmin(string $adminId): bool
    {
        return AdminUserRole::query()
            ->join('roles', 'admin_user_roles.role_id', '=', 'roles.id')
            ->where('admin_user_roles.user_id', $adminId)
            ->where('roles.key', 'global_admin')
            ->exists();
    }

    private function resolveAdminName(User $user): string
    {
        $name = $user->display_name ?: trim($user->first_name . ' ' . ($user->last_name ?? ''));

        if ($name === '') {
            $name = $user->email ?? '';
        }

        return $name;
    }
}
