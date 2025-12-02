<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Referral\StoreReferralLinkRequest;
use App\Http\Requests\Referral\UpdateVisitorLeadRequest;
use App\Http\Resources\ReferralLinkResource;
use App\Http\Resources\VisitorLeadResource;
use App\Models\ReferralLink;
use App\Models\VisitorLead;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReferralController extends BaseApiController
{
    public function storeLink(StoreReferralLinkRequest $request)
    {
        $userId = auth()->id();

        $data = $request->validated();

        $expiresAt = null;
        if (! empty($data['expires_at'])) {
            $expiresAt = $data['expires_at'];
        } elseif (! empty($data['expires_in_days'])) {
            $expiresAt = now()->addDays((int) $data['expires_in_days']);
        } else {
            $expiresAt = now()->addDays(90);
        }

        $token = null;
        do {
            $token = Str::upper(Str::random(12));
            $exists = ReferralLink::where('token', $token)->exists();
        } while ($exists);

        $link = ReferralLink::create([
            'referrer_user_id' => $userId,
            'token' => $token,
            'status' => 'active',
            'stats' => null,
            'expires_at' => $expiresAt,
        ]);

        $link->load([
            'referrerUser:id,display_name,first_name,last_name,profile_photo_url',
        ])->loadCount('visitorLeads as visitors_count');

        return $this->success(new ReferralLinkResource($link), 'Referral link created successfully', 201);
    }

    public function listLinks(Request $request)
    {
        $authUser = $request->user();

        $query = ReferralLink::where('referrer_user_id', $authUser->id)
            ->withCount('visitorLeads as visitors_count');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        $paginator = $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $data = [
            'items' => ReferralLinkResource::collection($paginator),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];

        return $this->success($data);
    }

    public function listVisitors(Request $request)
    {
        $admin = $request->user();
        // TODO: enforce admin/leader authorization (e.g. gate or middleware)

        $query = VisitorLead::with(['referralLink', 'convertedUser']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($referralLinkId = $request->input('referral_link_id')) {
            $query->where('referral_link_id', $referralLinkId);
        }

        if ($email = $request->input('email')) {
            $query->where('email', 'ILIKE', '%'.$email.'%');
        }

        if ($phone = $request->input('phone')) {
            $query->where('phone', 'ILIKE', '%'.$phone.'%');
        }

        if ($referrerUserId = $request->input('referrer_user_id')) {
            $query->whereHas('referralLink', function ($q) use ($referrerUserId) {
                $q->where('referrer_user_id', $referrerUserId);
            });
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = max(1, min($perPage, 100));

        $paginator = $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $data = [
            'items' => VisitorLeadResource::collection($paginator),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];

        return $this->success($data);
    }

    public function updateVisitor(UpdateVisitorLeadRequest $request, string $id)
    {
        $admin = $request->user();
        // TODO: enforce admin/leader authorization (e.g. gate or middleware)

        $lead = VisitorLead::with(['referralLink', 'convertedUser'])->find($id);

        if (! $lead) {
            return $this->error('Visitor lead not found', 404);
        }

        $data = $request->validated();

        $lead->fill($data);
        $lead->save();

        $lead->load(['referralLink', 'convertedUser']);

        return $this->success(new VisitorLeadResource($lead), 'Visitor lead updated successfully');
    }
}
