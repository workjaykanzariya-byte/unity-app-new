<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AdminUsersController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query();

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search): void {
                $q->where('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        /** @var LengthAwarePaginator $users */
        $users = $query
            ->select([
                'id',
                'first_name',
                'last_name',
                'email',
                'membership_status',
                'profile_photo_id',
                'profile_photo_file_id',
                'is_sponsored_member',
            ])
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'display_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'email' => ['sometimes', 'email'],
            'membership_status' => ['sometimes', 'string'],
            'industry_tags' => ['sometimes'],
            'target_regions' => ['sometimes'],
            'target_business_categories' => ['sometimes'],
            'hobbies_interests' => ['sometimes'],
            'leadership_roles' => ['sometimes'],
            'special_recognitions' => ['sometimes'],
            'is_sponsored_member' => ['sometimes', 'boolean'],
            'is_gdpr_exported' => ['sometimes', 'boolean'],
        ]);

        $jsonFields = [
            'industry_tags',
            'target_regions',
            'target_business_categories',
            'hobbies_interests',
            'leadership_roles',
            'special_recognitions',
        ];

        foreach ($jsonFields as $field) {
            if (array_key_exists($field, $data)) {
                $decoded = $data[$field];

                if (is_string($decoded)) {
                    $decoded = json_decode($decoded, true);
                }

                $data[$field] = is_array($decoded) ? $decoded : [];
            }
        }

        // Ensure booleans are not null
        foreach (['is_sponsored_member', 'is_gdpr_exported'] as $booleanField) {
            if (! array_key_exists($booleanField, $data)) {
                $data[$booleanField] = $user->{$booleanField} ?? false;
            }
        }

        // Normalize arrays to empty arrays when missing
        foreach ($jsonFields as $arrayField) {
            if (! array_key_exists($arrayField, $data)) {
                $data[$arrayField] = $user->{$arrayField} ?? [];
            }
        }

        try {
            $user->fill($data);
            $user->save();
        } catch (\Throwable $e) {
            Log::error('Admin user update failed', ['error' => $e->getMessage(), 'user_id' => $user->id]);

            return back()->withErrors(['error' => 'Failed to update user: '.$e->getMessage()]);
        }

        return redirect()->route('admin.users.edit', $user)->with('status', 'User updated successfully.');
    }
}
