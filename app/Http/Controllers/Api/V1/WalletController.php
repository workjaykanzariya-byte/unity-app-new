<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\CoinsLedger;
use Illuminate\Support\Facades\Log;
use Throwable;

class WalletController extends BaseApiController
{
    public function summary()
    {
        try {
            $user = auth()->user();

            return $this->success(
                [
                    'user_id' => $user->id,
                    'total_coins' => (int) $user->coins_balance,
                ],
                'Wallet summary fetched'
            );
        } catch (Throwable $e) {
            Log::error('Wallet summary failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return $this->error('Something went wrong', 500);
        }
    }

    public function ledger()
    {
        try {
            $ledger = CoinsLedger::where('user_id', auth()->id())
                ->orderByDesc('created_at')
                ->paginate(request('per_page', 20));

            return $this->success($ledger, 'Wallet ledger fetched');
        } catch (Throwable $e) {
            Log::error('Wallet ledger failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return $this->error('Something went wrong', 500);
        }
    }
}
