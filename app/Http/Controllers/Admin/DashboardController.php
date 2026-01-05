<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Circle;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_users' => User::count(),
            'active_circles' => Circle::query()->where('status', 'active')->count(),
            'pending_approvals' => Circle::query()->where('status', 'pending')->count(),
            'new_signups' => User::query()->whereDate('created_at', now()->toDateString())->count(),
        ];

        $pendingItems = [
            ['title' => 'New Circles Awaiting Review', 'count' => $stats['pending_approvals']],
            ['title' => 'Support Tickets', 'count' => DB::table('support_requests')->count()],
            ['title' => 'Content Reports', 'count' => DB::table('posts')->where('status', 'flagged')->count()],
        ];

        return view('admin.dashboard', [
            'stats' => $stats,
            'pendingItems' => $pendingItems,
        ]);
    }
}
