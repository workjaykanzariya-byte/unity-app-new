<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AdminOtp extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'admin_otps';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'email',
        'otp',
        'expires_at',
        'used_at',
        'created_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $otp): void {
            if (empty($otp->id)) {
                $otp->id = Str::uuid()->toString();
            }
        });
    }
}
