<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class CoinClaimRequest extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'coin_claim_requests';

    protected static ?string $resolvedTable = null;

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
        'reviewed_at' => 'datetime',
        'coins_awarded' => 'integer',
    ];

    public function getTable()
    {
        if (static::$resolvedTable !== null) {
            return static::$resolvedTable;
        }

        $candidates = [
            'coin_claim_requests',
            'coin_claims',
            'coin_claim_request',
            'coin_claim_submissions',
        ];

        foreach ($candidates as $candidate) {
            if (Schema::hasTable($candidate)) {
                static::$resolvedTable = $candidate;

                return $candidate;
            }
        }

        static::$resolvedTable = $this->table;

        return $this->table;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
