<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LifeImpactHistory extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'life_impact_histories';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'activity_id',
        'action_key',
        'action_label',
        'impact_category',
        'life_impacted',
        'remarks',
        'meta',
        'status',
        'approved_at',
        'counted_in_total',
        'created_by',
    ];

    protected $casts = [
        'meta' => 'array',
        'approved_at' => 'datetime',
        'counted_in_total' => 'boolean',
        'life_impacted' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
