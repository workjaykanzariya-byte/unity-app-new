<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CircleCategory extends Model
{
    protected $table = 'circle_categories';

    protected $fillable = [
        'name',
        'parent_id',
        'level',
        'slug',
        'circle_key',
        'sort_order',
        'is_active',
        'remarks',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'level' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function getCategoryNameAttribute(): ?string
    {
        return $this->name;
    }

    public function setCategoryNameAttribute(?string $value): void
    {
        $this->attributes['name'] = $value;
    }
}
