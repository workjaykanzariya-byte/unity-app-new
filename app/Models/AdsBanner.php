<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdsBanner extends Model
{
    use HasFactory;

    protected $table = 'ads_banners';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'title',
        'image_url',
        'link_url',
        'position',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];
}
