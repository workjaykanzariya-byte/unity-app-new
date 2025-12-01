<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class UserLink extends Model
{
    use HasFactory;

    protected $table = 'user_links'; // just to be explicit

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'type',
        'label',
        'url',
        'is_public',
    ];

    /**
     * Automatically assign a UUID when creating a new record.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (UserLink $link) {
            if (empty($link->id)) {
                $link->id = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
