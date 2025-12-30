<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminLoginOtp extends Model
{
    protected $table = 'admin_login_otps';

    protected $fillable = [
        'id',
        'email',
        'otp_hash',
        'expires_at',
        'last_sent_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_sent_at' => 'datetime',
    ];
}
