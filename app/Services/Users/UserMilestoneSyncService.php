<?php

namespace App\Services\Users;

use App\Models\User;

class UserMilestoneSyncService
{
    public function sync(User $user): void
    {
        $count = (int) ($user->members_introduced_count ?? 0);

        if ($count <= 0) {
            $user->coin_medal_rank = null;
            $user->coin_milestone_title = null;
            $user->coin_milestone_meaning = null;
            $user->contribution_award_name = null;
            $user->contribution_award_recognition = null;
            $user->save();

            return;
        }

        $payload = $this->resolveMilestone($count);

        $user->coin_medal_rank = $payload['coin_medal_rank'];
        $user->coin_milestone_title = $payload['coin_milestone_title'];
        $user->coin_milestone_meaning = $payload['coin_milestone_meaning'];
        $user->contribution_award_name = $payload['contribution_award_name'];
        $user->contribution_award_recognition = $payload['contribution_award_recognition'];
        $user->save();
    }

    public function resolveMilestone(int $count): array
    {
        if ($count >= 25) {
            return [
                'coin_medal_rank' => 'Hall of Fame',
                'coin_milestone_title' => 'Peers Global Hall of Fame 👑',
                'coin_milestone_meaning' => 'Elite contributor who built a powerful community network.',
                'contribution_award_name' => 'Peers Global Hall of Fame',
                'contribution_award_recognition' => 'Crown Pin + Lifetime Badge + Unity Wall',
            ];
        }

        if ($count >= 20) {
            return [
                'coin_medal_rank' => 'Nation Builder',
                'coin_milestone_title' => 'Nation Builder Award',
                'coin_milestone_meaning' => 'Nation builder creating strong business communities.',
                'contribution_award_name' => 'Nation Builder Award',
                'contribution_award_recognition' => 'Trophy + National Platform Honor',
            ];
        }

        if ($count >= 15) {
            return [
                'coin_medal_rank' => 'Impact Creator',
                'coin_milestone_title' => 'Impact Creator Award',
                'coin_milestone_meaning' => 'Impact creator driving powerful collaboration.',
                'contribution_award_name' => 'Impact Creator Award',
                'contribution_award_recognition' => '₹1L Membership Credit + City Convention Recognition',
            ];
        }

        if ($count >= 12) {
            return [
                'coin_medal_rank' => 'Legacy Creator',
                'coin_milestone_title' => 'Legacy Creator',
                'coin_milestone_meaning' => 'Legacy creator building long-term value.',
                'contribution_award_name' => 'Legacy Creator',
                'contribution_award_recognition' => 'Trophy + Digital Certificate + Social Media Spotlight',
            ];
        }

        if ($count >= 10) {
            return [
                'coin_medal_rank' => 'Global Star',
                'coin_milestone_title' => 'Global Star',
                'coin_milestone_meaning' => 'Global star expanding the network worldwide.',
                'contribution_award_name' => 'Global Star',
                'contribution_award_recognition' => 'Recognition at City Meet',
            ];
        }

        if ($count >= 8) {
            return [
                'coin_medal_rank' => 'Super Star',
                'coin_milestone_title' => 'Super Star Award',
                'coin_milestone_meaning' => 'Super star collaborator bringing high impact.',
                'contribution_award_name' => 'Super Star Award',
                'contribution_award_recognition' => 'Premium Recognition + Podcast Invite',
            ];
        }

        if ($count >= 6) {
            return [
                'coin_medal_rank' => 'Inspiration Icon',
                'coin_milestone_title' => 'Inspiration Icon Award',
                'coin_milestone_meaning' => 'Inspiration icon motivating peers.',
                'contribution_award_name' => 'Inspiration Icon Award',
                'contribution_award_recognition' => 'Digital Certificate + Social Media Spotlight',
            ];
        }

        if ($count >= 5) {
            return [
                'coin_medal_rank' => 'Influencer',
                'coin_milestone_title' => 'Influencer Award',
                'coin_milestone_meaning' => 'Influencer growing the network.',
                'contribution_award_name' => 'Influencer Award',
                'contribution_award_recognition' => 'Entry to Influencers Club',
            ];
        }

        if ($count >= 4) {
            return [
                'coin_medal_rank' => 'Titanium',
                'coin_milestone_title' => 'Synergy Architect',
                'coin_milestone_meaning' => 'You know how to collaborate deeply, create win-wins, and keep the vibe high.',
                'contribution_award_name' => 'Voice of Change Award',
                'contribution_award_recognition' => 'Digital Certificate + Social Media Spotlight',
            ];
        }

        if ($count >= 3) {
            return [
                'coin_medal_rank' => 'Titanium',
                'coin_milestone_title' => 'Synergy Architect',
                'coin_milestone_meaning' => 'You know how to collaborate deeply, create win-wins, and keep the vibe high.',
                'contribution_award_name' => 'Community Catalyst Award',
                'contribution_award_recognition' => 'Digital Certificate + Social Media Spotlight',
            ];
        }

        if ($count >= 2) {
            return [
                'coin_medal_rank' => 'Silver',
                'coin_milestone_title' => 'Rising Voice Award',
                'coin_milestone_meaning' => 'Your voice is rising in the community.',
                'contribution_award_name' => 'Rising Voice Award',
                'contribution_award_recognition' => 'Digital Certificate + Social Media Spotlight',
            ];
        }

        return [
            'coin_medal_rank' => 'Bronze',
            'coin_milestone_title' => 'The Connector Award',
            'coin_milestone_meaning' => 'You started connecting peers and building relationships.',
            'contribution_award_name' => 'The Connector Award',
            'contribution_award_recognition' => 'Digital Certificate + Social Media Spotlight',
        ];
    }
}
