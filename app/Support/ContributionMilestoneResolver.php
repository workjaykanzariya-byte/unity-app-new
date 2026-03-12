<?php

namespace App\Support;

class ContributionMilestoneResolver
{
    /**
     * @var array<int, array{threshold:int, award_name:string, recognition:string}>
     */
    private const MILESTONES = [
        [
            'threshold' => 1,
            'award_name' => 'The Connector Award',
            'recognition' => 'Digital Certificate + Social Media Spotlight',
        ],
        [
            'threshold' => 2,
            'award_name' => 'Rising Voice Award',
            'recognition' => 'Digital Certificate + Social Media Spotlight',
        ],
        [
            'threshold' => 3,
            'award_name' => 'Community Catalyst Award',
            'recognition' => 'Digital Certificate + Social Media Spotlight',
        ],
        [
            'threshold' => 4,
            'award_name' => 'Voice of Change Award',
            'recognition' => 'Digital Certificate + Social Media Spotlight',
        ],
        [
            'threshold' => 5,
            'award_name' => 'Influencer Award',
            'recognition' => 'Entry to Influencers Club',
        ],
        [
            'threshold' => 6,
            'award_name' => 'Inspiration Icon Award',
            'recognition' => 'Digital Certificate + Social Media Spotlight',
        ],
        [
            'threshold' => 8,
            'award_name' => 'Super Star Award',
            'recognition' => 'Premium Recognition + Podcast Invite',
        ],
        [
            'threshold' => 10,
            'award_name' => 'Global Star',
            'recognition' => 'Recognition at City Meet',
        ],
        [
            'threshold' => 12,
            'award_name' => 'Legacy Creator',
            'recognition' => 'Trophy + Digital Certificate + Social Media Spotlight',
        ],
        [
            'threshold' => 15,
            'award_name' => 'Impact Creator Award',
            'recognition' => '₹1L Membership Credit (in kind) + City Convention Recognition',
        ],
        [
            'threshold' => 20,
            'award_name' => 'Nation Builder Award',
            'recognition' => 'Trophy + National Platform Honor',
        ],
        [
            'threshold' => 25,
            'award_name' => 'Peers Global Hall of Fame 👑',
            'recognition' => 'Crown Pin + Lifetime Badge + Unity Wall of Fame Feature',
        ],
    ];

    /**
     * @return array{award_name:?string,recognition:?string}
     */
    public static function resolve(int|float|null $introducedCount): array
    {
        $count = (int) floor((float) ($introducedCount ?? 0));

        $resolved = [
            'award_name' => null,
            'recognition' => null,
        ];

        foreach (self::MILESTONES as $milestone) {
            if ($count < $milestone['threshold']) {
                break;
            }

            $resolved = [
                'award_name' => $milestone['award_name'],
                'recognition' => $milestone['recognition'],
            ];
        }

        return $resolved;
    }
}
