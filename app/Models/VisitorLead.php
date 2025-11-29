<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorLead extends Model
{
    use HasFactory;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'email',
        'phone',
        'status',
        'referral_link_id',
        'converted_user_id',
        'converted_at',
        'notes',
    ];

    protected $casts = [
        'converted_at' => 'datetime',
    ];

    public function referralLink(): BelongsTo
    {
        return $this->belongsTo(ReferralLink::class, 'referral_link_id');
    }

    public function convertedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_user_id');
    }
}
