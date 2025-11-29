<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasFactory;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'state',
        'district',
        'country',
        'country_code',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function circles(): HasMany
    {
        return $this->hasMany(Circle::class);
    }
}
