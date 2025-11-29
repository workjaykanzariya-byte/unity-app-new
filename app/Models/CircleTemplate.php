<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CircleTemplate extends Model
{
    use HasFactory;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
    ];

    public function circles(): HasMany
    {
        return $this->hasMany(Circle::class, 'template_id');
    }
}
