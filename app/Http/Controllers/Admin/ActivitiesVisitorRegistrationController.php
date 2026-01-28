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
        $query = VisitorRegistration::query()
            ->with(['user:id,display_name,first_name,last_name']);

        AdminCircleScope::applyToActivityQuery($query, Auth::guard('admin')->user(), 'visitor_registrations.user_id', null);

        $items = $query
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.activities.register_visitor.index', [
            'items' => $items,
        ]);
    }
}
