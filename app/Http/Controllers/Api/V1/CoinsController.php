<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CoinsLedger;
use Illuminate\Http\Request;

class CoinsController extends Controller
{
    public function balance(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Coins balance fetched successfully',
            'data' => [
                'user_id' => $user->id,
                'coins_balance' => (int) $user->coins_balance,
            ],
        ]);
    }

    public function ledger(Request $request)
    {
        $user = $request->user();

        $items = CoinsLedger::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Coins ledger fetched successfully',
            'data' => $items,
        ]);
    }
}
