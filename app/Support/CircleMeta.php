<?php

namespace App\Support;

use App\Models\Circle;

class CircleMeta
{
    public static function stages(): array
    {
        return [
            ['key' => 'conceptualized', 'label' => Circle::STAGE_LABELS['conceptualized'], 'description' => 'Early ideation and validation phase.'],
            ['key' => 'foundation', 'label' => Circle::STAGE_LABELS['foundation'], 'description' => 'Core members and operating foundation are being set.'],
            ['key' => 'pre_launch', 'label' => Circle::STAGE_LABELS['pre_launch'], 'description' => 'Pre-launch setup and readiness activities.'],
            ['key' => 'launched', 'label' => Circle::STAGE_LABELS['launched'], 'description' => 'Circle is launched and operational.'],
            ['key' => 'growth', 'label' => Circle::STAGE_LABELS['growth'], 'description' => 'Circle is scaling members and impact.'],
            ['key' => 'high_impact', 'label' => Circle::STAGE_LABELS['high_impact'], 'description' => 'Circle demonstrates sustained high impact.'],
        ];
    }
}
