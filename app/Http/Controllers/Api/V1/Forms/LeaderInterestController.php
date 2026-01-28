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

        if ($data['applying_for'] === 'referring_friend') {
            $data['leadership_roles'] = null;
            $data['contribute_city'] = null;
            $data['primary_domain'] = null;
            $data['why_interested'] = null;
            $data['excitement'] = null;
            $data['ownership'] = null;
            $data['time_commitment'] = null;
            $data['has_led_before'] = null;
            $data['message'] = null;
        } else {
            $data['referred_name'] = null;
            $data['referred_mobile'] = null;
        }

        $submission = LeaderInterestSubmission::create([
            'user_id' => $authUser->id,
            'applying_for' => $data['applying_for'],
            'referred_name' => $data['referred_name'] ?? null,
            'referred_mobile' => $data['referred_mobile'] ?? null,
            'leadership_roles' => $data['leadership_roles'] ?? null,
            'contribute_city' => $data['contribute_city'] ?? null,
            'primary_domain' => $data['primary_domain'] ?? null,
            'why_interested' => $data['why_interested'] ?? null,
            'excitement' => $data['excitement'] ?? null,
            'ownership' => $data['ownership'] ?? null,
            'time_commitment' => $data['time_commitment'] ?? null,
            'has_led_before' => $data['has_led_before'] ?? null,
            'message' => $data['message'] ?? null,
        ]);

        return $this->success([
            'id' => $submission->id,
            'created_at' => $submission->created_at,
        ], 'Leader interest submitted successfully.', 201);
    }
}
