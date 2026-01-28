<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaderInterestSubmission extends Model
{
    protected $table = 'leader_interest_submissions';

    protected $fillable = [
        'user_id',
        'applying_for',
        'referred_name',
        'referred_mobile',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
