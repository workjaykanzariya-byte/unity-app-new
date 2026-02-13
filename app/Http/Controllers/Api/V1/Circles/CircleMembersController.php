<?php

namespace App\Http\Controllers\Api\V1\Circles;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\V1\CircleMemberResource;
use App\Models\Circle;
use App\Models\CircleMember;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CircleMembersController extends BaseApiController
{
    public function index(Request $request, Circle $circle)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'active', 'inactive', 'left', 'removed'])],
        ]);

        $status = $validated['status'] ?? 'active';

        $members = CircleMember::query()
            ->where('circle_id', $circle->id)
            ->where('status', $status)
            ->with('user:id,display_name,first_name,last_name,profile_photo_file_id')
            ->orderByDesc('joined_at')
            ->get();

        return $this->success(CircleMemberResource::collection($members));
    }
}
