<?php

namespace App\Http\Controllers\Admin\Circles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Circles\StoreCircleMemberRequest;
use App\Http\Requests\Admin\Circles\UpdateCircleMemberRequest;
use App\Models\Circle;
use App\Models\CircleMember;
use Illuminate\Http\RedirectResponse;

class CircleMemberController extends Controller
{
    public function store(StoreCircleMemberRequest $request, Circle $circle): RedirectResponse
    {
        $data = $request->validated();

        $circle->members()->create([
            'user_id' => $data['user_id'],
            'role' => $data['role'],
            'status' => 'approved',
        ]);

        return redirect()
            ->route('admin.circles.show', $circle)
            ->with('success', 'Member added to the circle.');
    }

    public function update(UpdateCircleMemberRequest $request, Circle $circle, CircleMember $circleMember): RedirectResponse
    {
        if ($circleMember->circle_id !== $circle->id) {
            abort(404);
        }

        $circleMember->update([
            'role' => $request->validated()['role'],
        ]);

        return redirect()
            ->route('admin.circles.show', $circle)
            ->with('success', 'Member role updated.');
    }

    public function destroy(Circle $circle, CircleMember $circleMember): RedirectResponse
    {
        if ($circleMember->circle_id !== $circle->id) {
            abort(404);
        }

        $circleMember->forceDelete();

        return redirect()
            ->route('admin.circles.show', $circle)
            ->with('success', 'Member removed from the circle.');
    }
}
