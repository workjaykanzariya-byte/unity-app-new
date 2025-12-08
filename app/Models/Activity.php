<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use HasFactory;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'related_user_id',
        'circle_id',
        'event_id',
        'type',
        'status',
        'description',
        'admin_notes',
        'requires_verification',
        'verified_by_admin_id',
        'verified_at',
        'coins_awarded',
        'coins_ledger_id',
    ];

    protected $casts = [
        'requires_verification' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function relatedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'related_user_id');
    }

    public function circle(): BelongsTo
    {
        return $this->belongsTo(Circle::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function verifiedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_admin_id');
    }

    public function coinLedger(): BelongsTo
    {
        return $this->belongsTo(CoinsLedger::class, 'coins_ledger_id');
    }
}
