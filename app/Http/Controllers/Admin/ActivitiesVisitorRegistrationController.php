<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VisitorRegistration;
use App\Support\AdminCircleScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ActivitiesVisitorRegistrationController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status', 'all');

        $query = VisitorRegistration::query()
            ->with(['user:id,display_name,first_name,last_name,phone']);

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $like = "%{$search}%";
            $query->where(function ($q) use ($like) {
                $q->where('visitor_full_name', 'ILIKE', $like)
                    ->orWhere('visitor_mobile', 'ILIKE', $like)
                    ->orWhereHas('user', function ($userQuery) use ($like) {
                        $userQuery->where('display_name', 'ILIKE', $like)
                            ->orWhere('first_name', 'ILIKE', $like)
                            ->orWhere('last_name', 'ILIKE', $like)
                            ->orWhere('phone', 'ILIKE', $like);
                    });
            });
        }

        AdminCircleScope::applyToActivityQuery($query, Auth::guard('admin')->user(), 'visitor_registrations.user_id', null);

        $items = $query
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.activities.register_visitor.index', [
            'items' => $items,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'statusOptions' => ['all', 'pending', 'approved', 'rejected'],
        ]);
    }
}
