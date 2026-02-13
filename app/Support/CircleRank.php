<?php

namespace App\Support;

class CircleRank
{
    public const RULES = [
        ['min' => 1, 'max' => 19, 'rank_key' => 'bronze', 'rank_label' => 'Bronze', 'circle_title' => 'Rising Circle'],
        ['min' => 20, 'max' => 29, 'rank_key' => 'silver', 'rank_label' => 'Silver', 'circle_title' => 'Trusted Circle'],
        ['min' => 30, 'max' => 39, 'rank_key' => 'gold', 'rank_label' => 'Gold', 'circle_title' => 'Collaborative Circle'],
        ['min' => 40, 'max' => 49, 'rank_key' => 'platinum', 'rank_label' => 'Platinum', 'circle_title' => 'Influencer Circle'],
        ['min' => 50, 'max' => 59, 'rank_key' => 'titanium', 'rank_label' => 'Titanium', 'circle_title' => 'Growth Powerhouse'],
        ['min' => 60, 'max' => 74, 'rank_key' => 'diamond', 'rank_label' => 'Diamond', 'circle_title' => 'Iconic Circle'],
        ['min' => 75, 'max' => 99, 'rank_key' => 'royal', 'rank_label' => 'Royal', 'circle_title' => 'Legacy Circle'],
        ['min' => 100, 'max' => null, 'rank_key' => 'global_elite', 'rank_label' => 'Global Elite', 'circle_title' => 'Flagship Circle'],
    ];

    public static function compute(?int $activeMembersCount): array
    {
        $count = max(0, (int) $activeMembersCount);

        foreach (self::RULES as $rule) {
            $max = $rule['max'];

            if ($count >= $rule['min'] && ($max === null || $count <= $max)) {
                return $rule;
            }
        }

        return [
            'min' => 0,
            'max' => 0,
            'rank_key' => 'bronze',
            'rank_label' => 'Bronze',
            'circle_title' => 'Rising Circle',
        ];
    }
}
