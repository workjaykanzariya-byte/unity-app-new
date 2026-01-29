<?php

namespace App\Http\Controllers\Api\V1\Forms;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Forms\StorePeerRecommendationRequest;
use App\Models\PeerRecommendation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeerRecommendationController extends BaseApiController
{
    public function store(StorePeerRecommendationRequest $request)
    {
        $authUser = $request->user();
        $data = $request->validated();

        $result = DB::transaction(function () use ($authUser, $data) {
            $recommendation = PeerRecommendation::create([
                'user_id' => $authUser->id,
                'peer_name' => $data['peer_name'],
                'peer_mobile' => $data['peer_mobile'] ?? null,
                'peer_email' => $data['peer_email'] ?? null,
                'peer_city' => $data['peer_city'] ?? null,
                'peer_business' => $data['peer_business'] ?? null,
                'how_well_known' => $data['how_well_known'],
                'is_aware' => (bool) $data['is_aware'],
                'note' => $data['note'] ?? null,
                'coins_awarded' => false,
                'status' => 'pending',
            ]);

            return $recommendation;
        });

        /** @var \App\Models\PeerRecommendation $recommendation */
        $recommendation = $result;

        $payload = [
            'id' => $recommendation->id,
            'status' => $recommendation->status ?? 'pending',
        ];

        return $this->success($payload, 'Recommendation submitted. Pending admin approval.', 201);
    }

    public function myIndex(Request $request)
    {
        $authUser = $request->user();

        $items = PeerRecommendation::query()
            ->where('user_id', $authUser->id)
            ->orderByDesc('created_at')
            ->select([
                'id',
                'peer_name',
                'peer_mobile',
                'how_well_known',
                'is_aware',
                'note',
                'created_at',
            ])
            ->get();

        return $this->success([
            'items' => $items,
        ], 'Peer recommendations fetched successfully.');
    }
}
