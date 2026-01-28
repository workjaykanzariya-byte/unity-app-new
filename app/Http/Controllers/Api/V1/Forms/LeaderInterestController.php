<?php

namespace App\Http\Controllers\Api\V1\Forms;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Forms\StoreLeaderInterestRequest;
use App\Models\LeaderInterestSubmission;

class LeaderInterestController extends BaseApiController
{
    public function store(StoreLeaderInterestRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()?->id;

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

        $submission = LeaderInterestSubmission::create($data);

        $payload = [
            'id' => $submission->id,
            'created_at' => $submission->created_at,
        ];

        if (app()->environment('local')) {
            $payload['debug_saved_payload'] = $data;
        }

        return $this->success($payload, 'Leader interest submitted successfully.', 201);
    }
}
