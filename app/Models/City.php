<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasFactory;

    protected $table = 'cities';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'state_name',
        'country_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function circles(): HasMany
    {
        return $this->hasMany(Circle::class);
    }

    public function getStateAttribute(): ?string
    {
        return $this->state_name;
    }

    public function getDistrictAttribute(): ?string
    {
        return null;
    }

    public function getCountryAttribute(): ?string
    {
        return $this->country_name;
    }

    public function getCountryCodeAttribute(): ?string
    {
        return null;
    }
}
