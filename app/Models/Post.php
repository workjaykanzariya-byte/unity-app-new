<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'circle_id',
        'content_text',
        'media',
        'tags',
        'visibility',
        'moderation_status',
        'sponsored',
        'is_deleted',
    ];

    protected $casts = [
        'media' => 'array',
        'tags' => 'array',
        'sponsored' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $post): void {
            if (empty($post->id)) {
                $post->id = Str::uuid()->toString();
            }
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function circle(): BelongsTo
    {
        return $this->belongsTo(Circle::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PostComment::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(PostLike::class);
    }

    public function getMediaItemsAttribute(): array
    {
        return collect($this->media ?? [])
            ->map(function ($item) {
                if (! is_array($item)) {
                    return null;
                }

                $fileId = $item['file_id'] ?? $item['id'] ?? null;

                return [
                    'id' => $fileId,
                    'file_id' => $fileId,
                    'type' => $item['type'] ?? null,
                    'url' => $fileId
                        ? url("/api/v1/files/{$fileId}")
                        : null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
