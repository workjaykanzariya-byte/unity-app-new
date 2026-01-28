<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorRegistration extends Model
{
    protected $table = 'visitor_registrations';

    protected $fillable = [
        'user_id',
        'event_type',
        'event_name',
        'event_date',
        'visitor_full_name',
        'visitor_mobile',
        'visitor_email',
        'visitor_city',
        'visitor_business',
        'how_known',
        'note',
        'status',
        'reviewed_at',
        'reviewed_by_admin_user_id',
        'coins_awarded',
        'coins_awarded_at',
    ];

    protected $casts = [
        'event_date' => 'date',
        'reviewed_at' => 'datetime',
        'coins_awarded' => 'boolean',
        'coins_awarded_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'reviewed_by_admin_user_id');
    }
}
