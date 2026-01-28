<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VisitorRegistration;
use App\Services\Coins\CoinsService;
use App\Support\AdminCircleScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VisitorRegistrationsController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending');
        $search = trim((string) $request->query('search', ''));

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

        $registrations = $query
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.visitor_registrations.index', [
            'registrations' => $registrations,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
            'statuses' => ['pending', 'approved', 'rejected'],
        ]);
    }

    public function approve(string $id, CoinsService $coinsService): RedirectResponse
    {
        if (! Str::isUuid($id)) {
            abort(404);
        }

        $admin = Auth::guard('admin')->user();
        $message = DB::transaction(function () use ($id, $admin, $coinsService) {
            $registration = VisitorRegistration::query()
                ->where('id', $id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! AdminCircleScope::userInScope($admin, $registration->user_id)) {
                abort(403);
            }

            if ($registration->status === 'approved' || $registration->coins_awarded) {
                return 'Already approved.';
            }

            $registration->status = 'approved';
            $registration->reviewed_at = now();
            $registration->reviewed_by_admin_user_id = $admin?->id;
            $registration->save();

            $amount = (int) config('coins.register_visitor', 0);
            $ledger = $coinsService->reward($registration->user, $amount, 'Register a Visitor (Approved)');

            if ($ledger) {
                $registration->coins_awarded = true;
                $registration->coins_awarded_at = now();
                $registration->save();
            }

            return 'Visitor registration approved.';
        });

        return redirect()
            ->back()
            ->with('success', $message);
    }

    public function reject(string $id): RedirectResponse
    {
        if (! Str::isUuid($id)) {
            abort(404);
        }

        $admin = Auth::guard('admin')->user();
        $message = DB::transaction(function () use ($id, $admin) {
            $registration = VisitorRegistration::query()
                ->where('id', $id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! AdminCircleScope::userInScope($admin, $registration->user_id)) {
                abort(403);
            }

            if ($registration->status === 'approved') {
                return 'Already approved.';
            }

            $registration->status = 'rejected';
            $registration->reviewed_at = now();
            $registration->reviewed_by_admin_user_id = $admin?->id;
            $registration->save();

            return 'Visitor registration rejected.';
        });

        return redirect()
            ->back()
            ->with('success', $message);
    }
}
