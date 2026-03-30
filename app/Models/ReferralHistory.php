<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralHistory extends Model
{
    protected $table = 'referral_histories';

    protected $fillable = [
        'referrer_user_id',
        'referred_user_id',
        'referral_code',
        'reward_coins',
        'reward_status',
        'source',
    ];

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }
}

