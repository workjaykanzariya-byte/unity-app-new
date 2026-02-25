<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CollaborationPost extends Model
{
    use HasFactory;

    protected $table = 'collaboration_posts';

    protected $keyType = 'string';

    public $incrementing = false;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_DELETED = 'deleted';

    protected $fillable = [
        'id',
        'user_id',
        'collaboration_type',
        'title',
        'description',
        'scope',
        'countries_of_interest',
        'preferred_model',
        'industry_id',
        'business_stage',
        'years_in_operation',
        'urgency',
        'posted_at',
        'expires_at',
        'renewed_at',
        'status',
    ];

    protected $casts = [
        'countries_of_interest' => 'array',
        'posted_at' => 'datetime',
        'expires_at' => 'datetime',
        'renewed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $post): void {
            if (blank($post->id)) {
                $post->id = (string) Str::uuid();
            }

            $post->posted_at ??= now();
            $post->expires_at ??= $post->posted_at->copy()->addDays(60);
            $post->status ??= self::STATUS_ACTIVE;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function interests(): HasMany
    {
        return $this->hasMany(CollaborationPostInterest::class, 'post_id');
    }

    public function meetingRequests(): HasMany
    {
        return $this->hasMany(CollaborationPostMeetingRequest::class, 'post_id');
    }
}
