<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoinClaimRequest extends Model
{
    use HasUuids;

    protected $table = 'coin_claim_requests';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'activity_code',
        'payload',
        'status',
        'coins_awarded',
        'reviewed_by_admin_id',
        'reviewed_at',
        'admin_note',
    ];

    protected $casts = [
        'payload' => 'array',
        'coins_awarded' => 'integer',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewedByAdmin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'reviewed_by_admin_id');
    }
}
