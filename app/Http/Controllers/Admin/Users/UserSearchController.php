<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        if ($search === '') {
            return response()->json([]);
        }

        $users = User::query()
            ->whereNull('deleted_at')
            ->where(function ($query) use ($search): void {
                $query->where('display_name', 'ILIKE', "%{$search}%")
                    ->orWhere('first_name', 'ILIKE', "%{$search}%")
                    ->orWhere('last_name', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%");
            })
            ->orderBy('display_name')
            ->limit(10)
            ->get(['id', 'display_name', 'first_name', 'last_name', 'email']);

        $results = $users->map(function (User $user): array {
            $name = $user->display_name
                ?? trim($user->first_name . ' ' . ($user->last_name ?? ''));

            $label = trim($name);

            if ($user->email) {
                $label = $label !== '' ? $label . " ({$user->email})" : $user->email;
            }

            return [
                'id' => $user->id,
                'label' => $label !== '' ? $label : (string) $user->email,
            ];
        })->values();

        return response()->json($results);
    }
}
