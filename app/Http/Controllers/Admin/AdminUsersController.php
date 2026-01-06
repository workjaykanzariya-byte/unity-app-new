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
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Str;

class AdminUsersController extends Controller
{
    public function show(User $user): View
    {
        $user->loadMissing('city');

        $roles = Role::query()->orderBy('name')->get();
        $currentRoleId = AdminUserRole::query()
            ->where('user_id', $user->id)
            ->value('role_id');

        $adminId = auth('admin')->id();
        $canAssign = $adminId ? $this->isGlobalAdmin($adminId) : false;

        $details = $this->buildDetails($user);
        $skills = $this->extractArrayField($user, 'skills');
        $interests = $this->extractArrayField($user, 'interests');
        $socialLinks = $this->extractSocialLinks($user);

        return view('admin.users.show', [
            'user' => $user->load('city'),
            'roles' => $roles,
            'currentRoleId' => $currentRoleId,
            'canAssign' => $canAssign,
            'details' => $details,
            'skills' => $skills,
            'interests' => $interests,
            'socialLinks' => $socialLinks,
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

            $existingRole = AdminUserRole::query()->where('user_id', $user->id)->first();

            if ($existingRole) {
                $existingRole->update([
                    'role_id' => $validated['role_id'],
                ]);
            } else {
                AdminUserRole::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'id' => Str::uuid()->toString(),
                        'role_id' => $validated['role_id'],
                        'created_at' => now(),
                    ]
                );
            }
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

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function buildDetails(User $user): array
    {
        $details = [];

        $this->addDetail($details, 'User ID', $user->id, 'id');
        $this->addDetail($details, 'First Name', $user->first_name, 'first_name');
        $this->addDetail($details, 'Last Name', $user->last_name, 'last_name');
        $this->addDetail($details, 'Gender', $user->gender ?? null, 'gender');
        $this->addDetail($details, 'Date of Birth', $this->formatDate($user->dob ?? null), 'dob');
        $this->addDetail($details, 'Influencer Stars', $user->influencer_stars ?? null, 'influencer_stars');
        $this->addDetail($details, 'Coins Balance', isset($user->coins_balance) ? number_format((int) $user->coins_balance) : null, 'coins_balance');
        $this->addDetail($details, 'Joined', $this->formatDate($user->created_at ?? null), 'created_at');
        $this->addDetail($details, 'Updated At', $this->formatDate($user->updated_at ?? null), 'updated_at');

        $bio = $this->collectBio($user);
        $this->addDetail($details, 'About', $bio, $bio !== null ? null : 'short_bio');

        return $details;
    }

    private function collectBio(User $user): ?string
    {
        if ($this->hasUserColumn('short_bio') && !empty($user->short_bio)) {
            return $user->short_bio;
        }

        if ($this->hasUserColumn('long_bio_html') && !empty($user->long_bio_html)) {
            return strip_tags((string) $user->long_bio_html);
        }

        return null;
    }

    private function addDetail(array &$details, string $label, $value, ?string $column): void
    {
        if ($column !== null && !$this->hasUserColumn($column)) {
            return;
        }

        if ($value === null || $value === '') {
            return;
        }

        $details[] = [
            'label' => $label,
            'value' => (string) $value,
        ];
    }

    private function hasUserColumn(string $column): bool
    {
        static $columns = null;

        if ($columns === null) {
            $columns = Schema::getColumnListing('users');
        }

        return in_array($column, $columns, true);
    }

    private function formatDate($value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i');
        }

        return $value ?: null;
    }

    /**
     * @return array<int, string>
     */
    private function extractArrayField(User $user, string $field): array
    {
        if (!$this->hasUserColumn($field)) {
            return [];
        }

        $value = $user->{$field};

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(fn ($item) => is_string($item) ? trim($item) : null, $value)));
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function extractSocialLinks(User $user): array
    {
        if (!$this->hasUserColumn('social_links')) {
            return [];
        }

        $links = $user->social_links;

        if (!is_array($links)) {
            return [];
        }

        $prepared = [];

        foreach ($links as $key => $link) {
            $url = null;
            $label = is_string($key) ? ucfirst(str_replace('_', ' ', $key)) : 'Link ' . ((int) $key + 1);

            if (is_array($link)) {
                $url = $link['url'] ?? $link['link'] ?? null;
                $label = $link['label'] ?? $label;
            } elseif (is_string($link)) {
                $url = $link;
            }

            if ($url) {
                $prepared[] = [
                    'label' => $label,
                    'url' => $url,
                ];
            }
        }

        return $prepared;
    }
}
