<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class CoinsController extends Controller
{
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Coins fetched successfully',
            'data' => [
                'user_id' => $user->id,
                'coins_balance' => (int) $user->coins_balance,
            ],
        ]);
    }

    public function show(string $id)
    {
        $user = User::findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'User coins fetched successfully',
            'data' => [
                'user_id' => $user->id,
                'coins_balance' => (int) $user->coins_balance,
            ],
        ]);
    }
}
