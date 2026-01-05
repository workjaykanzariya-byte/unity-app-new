<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Circle;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CirclesController extends Controller
{
    public function index(Request $request): View
    {
        $query = Circle::query()->with(['founder', 'city']);

        $search = $request->input('search');
        $status = $request->input('status');
        $cityId = $request->input('city_id');

        if ($search) {
            $query->where('name', 'ILIKE', "%{$search}%");
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($cityId) {
            $query->where('city_id', $cityId);
        }

        $circles = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $statuses = Circle::query()
            ->whereNotNull('status')
            ->distinct()
            ->pluck('status')
            ->sort()
            ->values();

        $cities = City::query()->orderBy('name')->get();

        return view('admin.circles.index', [
            'circles' => $circles,
            'statuses' => $statuses,
            'cities' => $cities,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'city_id' => $cityId,
            ],
        ]);
    }
}
