<?php

namespace App\Support\CoinClaims;

class CoinClaimActivityRegistry
{
    public static function all(): array
    {
        return [
            [
                'code' => 'attend_circle_meeting',
                'label' => 'Attend Circle Meetings',
                'fields' => [
                    ['key' => 'meeting_date', 'label' => 'Meeting Date', 'type' => 'date', 'required' => true],
                ],
            ],
            [
                'code' => 'vyapaarjagat_story',
                'label' => 'Publish Your Story on VyapaarJagat.com',
                'fields' => [
                    ['key' => 'story_url', 'label' => 'Story URL', 'type' => 'url', 'required' => true],
                ],
            ],
            [
                'code' => 'host_member_spotlight',
                'label' => 'Host Member Spotlights',
                'fields' => [
                    ['key' => 'member_name', 'label' => 'Member Name', 'type' => 'text', 'required' => true],
                    ['key' => 'spotlight_date', 'label' => 'Spotlight Date', 'type' => 'date', 'required' => true],
                    ['key' => 'spotlight_link', 'label' => 'Spotlight Link', 'type' => 'url', 'required' => true],
                ],
            ],
            [
                'code' => 'bring_speaker',
                'label' => 'Bring a Speaker',
                'fields' => [
                    ['key' => 'speaker_name', 'label' => 'Speaker Name', 'type' => 'text', 'required' => true],
                ],
            ],
            [
                'code' => 'join_circle',
                'label' => 'Join A Circle',
                'fields' => [
                    ['key' => 'circle_name', 'label' => 'Circle Name', 'type' => 'text', 'required' => true],
                ],
            ],
            [
                'code' => 'renew_membership',
                'label' => 'Renew Membership',
                'fields' => [
                    ['key' => 'renewal_date', 'label' => 'Renewal Date', 'type' => 'date', 'required' => true],
                    ['key' => 'payment_proof_file', 'label' => 'Payment Proof File', 'type' => 'file', 'required' => true],
                ],
            ],
            [
                'code' => 'invite_visitor',
                'label' => 'Invite a Visitor',
                'fields' => [
                    ['key' => 'visitor_name', 'label' => 'Visitor Name', 'type' => 'text', 'required' => true],
                    ['key' => 'visitor_mobile', 'label' => 'Visitor Mobile', 'type' => 'phone', 'required' => true],
                    ['key' => 'visitor_email', 'label' => 'Visitor Email', 'type' => 'email', 'required' => true],
                    ['key' => 'visit_date', 'label' => 'Visit Date', 'type' => 'date', 'required' => true],
                    ['key' => 'event_confirmation_file', 'label' => 'Event Confirmation File', 'type' => 'file', 'required' => false],
                ],
            ],
            [
                'code' => 'new_member_addition',
                'label' => 'New Member Addition',
                'fields' => [
                    ['key' => 'new_member_name', 'label' => 'New Member Name', 'type' => 'text', 'required' => true],
                    ['key' => 'new_member_mobile', 'label' => 'New Member Mobile', 'type' => 'phone', 'required' => true],
                    ['key' => 'new_member_email', 'label' => 'New Member Email', 'type' => 'email', 'required' => true],
                    ['key' => 'joining_date', 'label' => 'Joining Date', 'type' => 'date', 'required' => true],
                    ['key' => 'membership_confirmation_file', 'label' => 'Membership Confirmation File', 'type' => 'file', 'required' => false],
                ],
            ],
        ];
    }

    public static function byCode(string $activityCode): ?array
    {
        foreach (self::all() as $activity) {
            if ($activity['code'] === $activityCode) {
                return $activity;
            }
        }

        return null;
    }

    public static function rulesFor(string $activityCode): array
    {
        $activity = self::byCode($activityCode);

        if (! $activity) {
            return [];
        }

        $rules = [];

        foreach ($activity['fields'] as $field) {
            $isRequired = $field['required'] ? ['required'] : ['nullable'];
            $path = 'fields.'.$field['key'];

            $rules[$path] = match ($field['type']) {
                'date' => [...$isRequired, 'date_format:Y-m-d'],
                'url' => [...$isRequired, 'url'],
                'email' => [...$isRequired, 'email'],
                'phone' => [...$isRequired, 'regex:/^\+?[0-9]{8,15}$/'],
                default => [...$isRequired, 'string', 'max:255'],
            };

            if ($field['type'] === 'file') {
                $rules['files.'.$field['key']] = [...$isRequired, 'file', 'max:20480'];
            }
        }

        return $rules;
    }
}
