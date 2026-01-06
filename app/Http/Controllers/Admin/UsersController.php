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
        $query = User::query()
            ->select([
                'id',
                'first_name',
                'last_name',
                'display_name',
                'email',
                'phone',
                'company_name',
                'business_type',
                'industry_tags',
                'membership_status',
                'membership_expiry',
                'coins_balance',
                'influencer_stars',
                'last_login_at',
                'created_at',
                'updated_at',
                'city_id',
                'city',
                'profile_photo_url',
                'profile_photo_file_id',
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
}
