<?php

namespace App\Http\Controllers\Api\V1\Forms;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Forms\StoreLeaderInterestRequest;
use App\Models\LeaderInterestSubmission;

class LeaderInterestController extends BaseApiController
{
    public function store(StoreLeaderInterestRequest $request)
    {
        $authUser = $request->user();
        $data = $request->validated();

        if ($data['applying_for'] === 'myself') {
            $data['referred_name'] = null;
            $data['referred_mobile'] = null;
        }

        $submission = LeaderInterestSubmission::create([
            'user_id' => $authUser->id,
            'applying_for' => $data['applying_for'],
            'referred_name' => $data['referred_name'] ?? null,
            'referred_mobile' => $data['referred_mobile'] ?? null,
        ]);

        return $this->success([
            'id' => $submission->id,
            'created_at' => $submission->created_at,
        ], 'Leader interest submitted successfully.', 201);
    }
}
