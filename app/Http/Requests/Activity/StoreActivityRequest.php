<?php

namespace App\Http\Requests\Activity;

use Illuminate\Foundation\Http\FormRequest;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|string|in:publish_story_vj,visit_member_spotlight,bring_speaker,join_circle,install_app,renew_membership,post_ask,update_timeline,invite_visitor,new_member_addition,peer_meeting,pass_referral,attend_circle_meeting,close_business_deal,testimonial,need_help_growing,requirement_posted',
            'description' => 'nullable|string',
            'related_user_id' => 'nullable|uuid|exists:users,id',
            'circle_id' => 'nullable|uuid|exists:circles,id',
            'event_id' => 'nullable|uuid|exists:events,id',
        ];
    }
}
