<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Requirement extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'requirements';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'subject',
        'description',
        'media',
        'region_filter',
        'category_filter',
        'status',
        'timeline_post_id',
        'closed_at',
        'completed_at',
    ];

    protected $casts = [
        'media' => 'array',
        'region_filter' => 'array',
        'category_filter' => 'array',
        'closed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function interests(): HasMany
    {
        return $this->hasMany(RequirementInterest::class);
    }

    public function timelinePost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'timeline_post_id');
    }
}
