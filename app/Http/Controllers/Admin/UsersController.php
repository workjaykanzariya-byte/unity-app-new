<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UsersController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->with('city');

        $search = $request->input('search');
        $membership = $request->input('membership_status');
        $cityId = $request->input('city_id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('display_name', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%");
            });
        }

        if ($membership) {
            $query->where('membership_status', $membership);
        }

        if ($cityId) {
            $query->where('city_id', $cityId);
        }

        $users = $query->orderByDesc('last_login_at')->paginate(20)->withQueryString();

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
            ],
        ]);
    }
}
