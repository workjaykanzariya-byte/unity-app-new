<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;

class CoinHistoryController extends BaseApiController
{
    public function index(Request $request)
    {
        $user = $request->user();

        return $this->success([
            'user_id' => $user?->id,
        ], 'Coins history route working');
    }
}
