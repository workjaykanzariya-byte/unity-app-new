<?php

namespace App\Http\Controllers\Api\V1\Circles;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Circle;
use App\Support\CircleMeta;
use App\Support\CircleRank;

class CircleMetaController extends BaseApiController
{
    public function show()
    {
        return $this->success([
            'stages' => CircleMeta::stages(),
            'rank_rules' => CircleRank::RULES,
            'meeting_modes' => array_map(fn ($key) => ['key' => $key, 'label' => ucfirst($key)], Circle::MEETING_MODE_OPTIONS),
            'meeting_frequencies' => array_map(fn ($key) => ['key' => $key, 'label' => ucfirst($key)], Circle::MEETING_FREQUENCY_OPTIONS),
            'circle_types' => array_map(fn ($key) => ['key' => $key, 'label' => ucfirst($key)], Circle::TYPE_OPTIONS),
        ]);
    }
}
