<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AdminLoginOtp extends Model
{
    use HasFactory;

    protected $table = 'admin_login_otps';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'email',
        'otp_hash',
        'expires_at',
        'last_sent_at',
        'attempts',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'attempts' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $otp): void {
            if (! $otp->id) {
                $otp->id = Str::uuid()->toString();
            }
        });
    }
}
