<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'user_id',
        'circle_id',
        'content',
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

    protected function content(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->attributes['content_text'] ?? null,
            set: fn ($value) => ['content_text' => $value],
        );
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

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(FileModel::class, 'posts_media', 'post_id', 'file_id');
    }
}
