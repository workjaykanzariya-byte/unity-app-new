<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MembershipPlans\StoreMembershipPlanRequest;
use App\Http\Requests\Admin\MembershipPlans\UpdateMembershipPlanRequest;
use App\Models\MembershipPlan;
use App\Support\AdminAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MembershipPlanController extends Controller
{
    public function index(Request $request): View
    {
        $plans = MembershipPlan::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $canEdit = AdminAccess::isGlobalAdmin($request->user('admin'));

        return view('admin.unity-peers-plans.index', [
            'plans' => $plans,
            'canEdit' => $canEdit,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeGlobalAdmin($request);

        return view('admin.unity-peers-plans.create');
    }

    public function store(StoreMembershipPlanRequest $request): RedirectResponse
    {
        $plan = MembershipPlan::query()->create($request->validated());

        return redirect()
            ->route('admin.unity-peers-plans.edit', $plan)
            ->with('success', 'Membership plan created successfully.');
    }

    public function edit(Request $request, MembershipPlan $plan): View
    {
        $this->authorizeGlobalAdmin($request);

        return view('admin.unity-peers-plans.edit', [
            'plan' => $plan,
        ]);
    }

    public function update(UpdateMembershipPlanRequest $request, MembershipPlan $plan): RedirectResponse
    {
        $plan->update($request->validated());

        return redirect()
            ->route('admin.unity-peers-plans.index')
            ->with('success', 'Membership plan updated successfully.');
    }

    private function authorizeGlobalAdmin(Request $request): void
    {
        if (! AdminAccess::isGlobalAdmin($request->user('admin'))) {
            abort(403);
        }
    }
}
