<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MembershipPlan;
use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MembershipPlansController extends Controller
{
    public function index(Request $request): View
    {
        $plans = MembershipPlan::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $canEdit = AdminAccess::isGlobalAdmin($request->user('admin'));

        return view('admin.memberships.plans.index', [
            'plans' => $plans,
            'canEdit' => $canEdit,
        ]);
    }

    public function edit(Request $request, MembershipPlan $plan): View
    {
        $this->authorizeGlobalAdmin($request);

        return view('admin.memberships.plans.edit', [
            'plan' => $plan,
        ]);
    }

    public function update(Request $request, MembershipPlan $plan): RedirectResponse
    {
        $this->authorizeGlobalAdmin($request);

        $validated = $request->validate([
            'price' => ['required', 'numeric', 'min:0'],
            'gst_percent' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:0'],
            'duration_months' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $plan->update($validated);

        return redirect()
            ->route('admin.memberships.plans.index')
            ->with('success', 'Membership plan updated successfully.');
    }

    private function authorizeGlobalAdmin(Request $request): void
    {
        if (! AdminAccess::isGlobalAdmin($request->user('admin'))) {
            abort(403);
        }
    }
}
