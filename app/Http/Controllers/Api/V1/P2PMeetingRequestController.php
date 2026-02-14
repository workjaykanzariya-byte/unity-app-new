<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\P2PMeetingRequestResource;
use App\Models\Notification;
use App\Models\P2PMeetingRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class P2PMeetingRequestController extends BaseApiController
{
    public function store(Request $request)
    {
        $authUser = $request->user();

        $validated = $request->validate([
            'to_user_id' => [
                'required',
                'uuid',
                'exists:users,id',
                Rule::notIn([(string) $authUser->id]),
            ],
            'scheduled_at' => ['required', 'date'],
            'place' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $scheduledAt = Carbon::parse($validated['scheduled_at']);
        $duplicateExists = P2PMeetingRequest::query()
            ->where('requester_id', $authUser->id)
            ->where('invitee_id', $validated['to_user_id'])
            ->where('status', 'pending')
            ->whereBetween('scheduled_at', [
                $scheduledAt->copy()->subMinutes(15),
                $scheduledAt->copy()->addMinutes(15),
            ])
            ->exists();

        if ($duplicateExists) {
            return $this->error('A similar pending meeting request already exists near this schedule time.', 422);
        }

        $meetingRequest = DB::transaction(function () use ($authUser, $validated, $scheduledAt) {
            $meetingRequest = P2PMeetingRequest::create([
                'requester_id' => $authUser->id,
                'invitee_id' => $validated['to_user_id'],
                'scheduled_at' => $scheduledAt,
                'place' => $validated['place'],
                'message' => $validated['message'] ?? null,
                'status' => 'pending',
            ]);

            $invitee = User::query()->findOrFail($validated['to_user_id']);

            Notification::create([
                'user_id' => $invitee->id,
                'type' => 'p2p_meeting_request',
                'payload' => [
                    'notification_type' => 'p2p_meeting_request',
                    'meeting_request_id' => (string) $meetingRequest->id,
                    'scheduled_at' => $meetingRequest->scheduled_at?->toIso8601String(),
                    'place' => $meetingRequest->place,
                    'message' => $meetingRequest->message,
                    'from_user' => $authUser->publicProfileArray(),
                ],
                'is_read' => false,
                'created_at' => now(),
                'read_at' => null,
            ]);

            return $meetingRequest;
        });

        $meetingRequest->load(['requester', 'invitee']);

        return $this->success(new P2PMeetingRequestResource($meetingRequest), 'P2P meeting request created.', 201);
    }

    public function inbox(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'accepted', 'rejected', 'cancelled'])],
        ]);

        $query = P2PMeetingRequest::query()
            ->with('requester')
            ->where('invitee_id', $request->user()->id)
            ->orderByDesc('created_at');

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $items = $query->get();

        return $this->success([
            'total' => $items->count(),
            'items' => P2PMeetingRequestResource::collection($items),
        ]);
    }

    public function sent(Request $request)
    {
        $items = P2PMeetingRequest::query()
            ->with('invitee')
            ->where('requester_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return $this->success([
            'total' => $items->count(),
            'items' => P2PMeetingRequestResource::collection($items),
        ]);
    }

    public function show(Request $request, string $id)
    {
        $meetingRequest = P2PMeetingRequest::query()
            ->with(['requester', 'invitee'])
            ->find($id);

        if (! $meetingRequest) {
            return $this->error('Meeting request not found.', 404);
        }

        if (! $this->isParticipant($meetingRequest, (string) $request->user()->id)) {
            return $this->error('Forbidden.', 403);
        }

        return $this->success(new P2PMeetingRequestResource($meetingRequest));
    }

    public function accept(Request $request, string $id)
    {
        return $this->respondToRequest($request, $id, 'accepted');
    }

    public function reject(Request $request, string $id)
    {
        return $this->respondToRequest($request, $id, 'rejected');
    }

    public function cancel(Request $request, string $id)
    {
        $meetingRequest = P2PMeetingRequest::query()
            ->with(['requester', 'invitee'])
            ->find($id);

        if (! $meetingRequest) {
            return $this->error('Meeting request not found.', 404);
        }

        if ((string) $meetingRequest->requester_id !== (string) $request->user()->id) {
            return $this->error('Only requester can cancel this meeting request.', 403);
        }

        if ($meetingRequest->status !== 'pending') {
            return $this->error('Only pending requests can be cancelled.', 422);
        }

        DB::transaction(function () use ($meetingRequest, $request) {
            $meetingRequest->update([
                'status' => 'cancelled',
                'responded_at' => now(),
            ]);

            Notification::create([
                'user_id' => $meetingRequest->invitee_id,
                'type' => 'p2p_meeting_cancelled',
                'payload' => [
                    'notification_type' => 'p2p_meeting_cancelled',
                    'meeting_request_id' => (string) $meetingRequest->id,
                    'scheduled_at' => $meetingRequest->scheduled_at?->toIso8601String(),
                    'place' => $meetingRequest->place,
                    'message' => $meetingRequest->message,
                    'from_user' => $request->user()->publicProfileArray(),
                ],
                'is_read' => false,
                'created_at' => now(),
                'read_at' => null,
            ]);
        });

        $meetingRequest->refresh()->load(['requester', 'invitee']);

        return $this->success(new P2PMeetingRequestResource($meetingRequest), 'Meeting request cancelled successfully.');
    }

    private function respondToRequest(Request $request, string $id, string $status)
    {
        $meetingRequest = P2PMeetingRequest::query()
            ->with(['requester', 'invitee'])
            ->find($id);

        if (! $meetingRequest) {
            return $this->error('Meeting request not found.', 404);
        }

        if ((string) $meetingRequest->invitee_id !== (string) $request->user()->id) {
            return $this->error('Only invitee can perform this action.', 403);
        }

        if ($meetingRequest->status !== 'pending') {
            return $this->error('Only pending requests can be updated.', 422);
        }

        DB::transaction(function () use ($meetingRequest, $status, $request) {
            $meetingRequest->update([
                'status' => $status,
                'responded_at' => now(),
            ]);

            Notification::create([
                'user_id' => $meetingRequest->requester_id,
                'type' => 'p2p_meeting_' . $status,
                'payload' => [
                    'notification_type' => 'p2p_meeting_' . $status,
                    'meeting_request_id' => (string) $meetingRequest->id,
                    'scheduled_at' => $meetingRequest->scheduled_at?->toIso8601String(),
                    'place' => $meetingRequest->place,
                    'message' => $meetingRequest->message,
                    'from_user' => $request->user()->publicProfileArray(),
                ],
                'is_read' => false,
                'created_at' => now(),
                'read_at' => null,
            ]);
        });

        $meetingRequest->refresh()->load(['requester', 'invitee']);

        return $this->success(new P2PMeetingRequestResource($meetingRequest), 'Meeting request ' . $status . ' successfully.');
    }

    private function isParticipant(P2PMeetingRequest $meetingRequest, string $authUserId): bool
    {
        return (string) $meetingRequest->requester_id === $authUserId
            || (string) $meetingRequest->invitee_id === $authUserId;
    }
}
