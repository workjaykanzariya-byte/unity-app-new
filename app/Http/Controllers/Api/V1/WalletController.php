<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\CoinsLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    use ApiResponse;

    public function summary(Request $request)
    {
        try {
            $user = $request->user();

            return $this->successResponse('Wallet summary fetched successfully', [
                'user_id' => $user->id,
                'total_coins' => $user->coins_balance,
            ]);
        } catch (\Throwable $e) {
            Log::error('Wallet summary error', [
                'user_id' => optional($request->user())->id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Something went wrong', [], 500);
        }
    }

    public function ledger(Request $request)
    {
        try {
            $user = $request->user();

            $ledger = CoinsLedger::where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->paginate($request->get('per_page', 20));

            return $this->successResponse('Wallet ledger fetched successfully', $ledger);
        } catch (\Throwable $e) {
            Log::error('Wallet ledger error', [
                'user_id' => optional($request->user())->id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Something went wrong', [], 500);
        }
    }

    protected function successResponse(string $message, $data)
    {
        return $this->success($data, $message);
    }

    protected function errorResponse(string $message, $errors = [], int $status = 400)
    {
        return $this->error($message, $status, $errors);
    }
}
