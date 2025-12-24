<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminLoginOtp extends Model
{
    use HasFactory;

    protected $table = 'admin_login_otps';

    public $timestamps = false;

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
    ];
}
