<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Requirement extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'subject',
        'description',
        'media',
        'region_filter',
        'category_filter',
        'status',
    ];

    protected $casts = [
        'media' => 'array',
        'region_filter' => 'array',
        'category_filter' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $requirement): void {
            if (empty($requirement->id)) {
                $requirement->id = Str::uuid()->toString();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
