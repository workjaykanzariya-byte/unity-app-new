<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Event\EventCheckinRequest;
use App\Http\Requests\Event\EventRsvpRequest;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Resources\EventResource;
use App\Http\Resources\EventRsvpResource;
use App\Models\CircleMember;
use App\Models\Event;
use App\Models\EventRsvp;
use Illuminate\Http\Request;

class EventController extends BaseApiController
{
    public function index(Request $request)
    {
        $query = Event::query()
            ->with(['circle', 'createdByUser', 'rsvps']);

        if ($circleId = $request->input('circle_id')) {
            $query->where('circle_id', $circleId);
        }

        if ($from = $request->input('from')) {
            $query->where('start_at', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->where('start_at', '<=', $to);
        }

        if ($visibility = $request->input('visibility')) {
            $query->where('visibility', $visibility);
        }

        $upcoming = $request->input('upcoming', '1');
        if ($upcoming === '1' || $upcoming === 1 || $upcoming === true || $upcoming === 'true') {
            $query->where('start_at', '>=', now());
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        $paginator = $query->orderBy('start_at', 'asc')->paginate($perPage);

        $data = [
            'items' => EventResource::collection($paginator),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];

        return $this->success($data);
    }

    public function show(Request $request, string $id)
    {
        $event = Event::with(['circle', 'createdByUser', 'rsvps.user'])->find($id);

        if (! $event) {
            return $this->error('Event not found', 404);
        }

        return $this->success(new EventResource($event));
    }

    public function store(StoreEventRequest $request)
    {
        $authUser = $request->user();
        $data = $request->validated();

        $circleId = $data['circle_id'];

        $membership = CircleMember::where('circle_id', $circleId)
            ->where('user_id', $authUser->id)
            ->where('status', 'approved')
            ->whereNull('deleted_at')
            ->first();

        if (! $membership) {
            return $this->error('You are not a member of this circle', 403);
        }

        $adminRoles = ['founder', 'director', 'chair', 'vice_chair', 'secretary'];
        if (! in_array($membership->role, $adminRoles, true)) {
            return $this->error('You are not allowed to create events for this circle', 403);
        }

        $event = new Event();
        $event->circle_id = $circleId;
        $event->created_by_user_id = $authUser->id;
        $event->title = $data['title'];
        $event->description = $data['description'] ?? null;
        $event->start_at = $data['start_at'];
        $event->end_at = $data['end_at'] ?? null;
        $event->is_virtual = $data['is_virtual'] ?? true;
        $event->location_text = $data['location_text'] ?? null;
        $event->agenda = $data['agenda'] ?? null;
        $event->speakers = $data['speakers'] ?? null;
        $event->banner_url = $data['banner_url'] ?? null;
        $event->visibility = $data['visibility'];
        $event->is_paid = $data['is_paid'] ?? false;
        $event->metadata = $data['metadata'] ?? null;
        $event->save();

        $event->load(['circle', 'createdByUser', 'rsvps']);

        return $this->success(new EventResource($event), 'Event created successfully', 201);
    }

    public function rsvp(EventRsvpRequest $request, string $id)
    {
        $authUser = $request->user();
        $event = Event::find($id);

        if (! $event) {
            return $this->error('Event not found', 404);
        }

        $data = $request->validated();
        $status = $data['status'];

        $rsvp = EventRsvp::firstOrNew([
            'event_id' => $event->id,
            'user_id' => $authUser->id,
        ]);

        $rsvp->status = $status;

        if (! $rsvp->exists) {
            $rsvp->checked_in = false;
            $rsvp->checkin_at = null;
        }

        $rsvp->save();
        $rsvp->load('user');

        return $this->success(new EventRsvpResource($rsvp), 'RSVP updated');
    }

    public function checkin(EventCheckinRequest $request, string $id)
    {
        $authUser = $request->user();
        $data = $request->validated();

        $event = Event::find($id);
        if (! $event) {
            return $this->error('Event not found', 404);
        }

        $targetUserId = $data['user_id'] ?? $authUser->id;
        $checkedIn = $data['checked_in'] ?? true;

        if ($targetUserId !== $authUser->id) {
            if (! $event->circle_id) {
                return $this->error('Cannot manage check-in for this event', 403);
            }

            $membership = CircleMember::where('circle_id', $event->circle_id)
                ->where('user_id', $authUser->id)
                ->where('status', 'approved')
                ->whereNull('deleted_at')
                ->first();

            if (! $membership) {
                return $this->error('You are not a member of this circle', 403);
            }

            $adminRoles = ['founder', 'director', 'chair', 'vice_chair', 'secretary'];
            if (! in_array($membership->role, $adminRoles, true)) {
                return $this->error('You are not allowed to manage check-ins for this event', 403);
            }
        }

        $rsvp = EventRsvp::firstOrNew([
            'event_id' => $event->id,
            'user_id' => $targetUserId,
        ]);

        if (! $rsvp->exists) {
            $rsvp->status = 'going';
        }

        $rsvp->checked_in = (bool) $checkedIn;
        $rsvp->checkin_at = $rsvp->checked_in ? now() : null;
        $rsvp->save();

        $rsvp->load('user');

        return $this->success(new EventRsvpResource($rsvp), 'Check-in updated');
    }
}
