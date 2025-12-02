<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SupportRequest extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'support_type',
        'details',
        'attachments',
        'routed_to_user_id',
        'status',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $supportRequest): void {
            if (empty($supportRequest->id)) {
                $supportRequest->id = Str::uuid()->toString();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function routedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'routed_to_user_id');
    }
}
