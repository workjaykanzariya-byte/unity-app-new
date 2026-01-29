<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeerRecommendation extends Model
{
    protected $table = 'peer_recommendations';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'peer_name',
        'peer_mobile',
        'peer_email',
        'peer_city',
        'peer_business',
        'how_well_known',
        'is_aware',
        'note',
        'status',
        'reviewed_at',
        'reviewed_by_admin_user_id',
        'coins_awarded',
        'coins_awarded_at',
    ];

    protected $casts = [
        'is_aware' => 'boolean',
        'coins_awarded' => 'boolean',
        'coins_awarded_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
