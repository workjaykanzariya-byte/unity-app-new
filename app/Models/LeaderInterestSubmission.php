<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaderInterestSubmission extends Model
{
    protected $table = 'leader_interest_submissions';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'applying_for',
        'referred_name',
        'referred_mobile',
        'leadership_roles',
        'contribute_city',
        'primary_domain',
        'why_interested',
        'excitement',
        'ownership',
        'time_commitment',
        'has_led_before',
        'message',
    ];

    protected $casts = [
        'leadership_roles' => 'array',
        'has_led_before' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
