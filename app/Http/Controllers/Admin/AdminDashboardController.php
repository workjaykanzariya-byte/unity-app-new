<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'total_users' => '12,450',
                'pending_activities' => '132',
                'coins_issued' => '4,820,000',
            ],
        ]);
    }
}
