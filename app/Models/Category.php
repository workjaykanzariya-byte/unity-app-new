<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'category_name',
        'sector',
        'remarks',
        'parent_id',
        'level',
        'slug',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'parent_id' => 'integer',
        'level' => 'integer',
        'sort_order' => 'integer',
    ];

    public function circleMappings(): HasMany
    {
        return $this->hasMany(CircleCategoryMapping::class);
    }

    public function circles(): BelongsToMany
    {
        return $this->belongsToMany(Circle::class, 'circle_category_mappings', 'category_id', 'circle_id')
            ->withTimestamps();
    }

    public function eventGalleries(): HasMany
    {
        return $this->hasMany(EventGallery::class, 'circle_category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->ordered();
    }

    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }

    public function scopeActive(Builder $query): Builder
    {
        if (! self::hasColumn('is_active')) {
            return $query;
        }

        return $query->where('is_active', true);
    }

    public function scopeMainCircles(Builder $query): Builder
    {
        if (! self::hasColumn('parent_id') || ! self::hasColumn('level')) {
            return $query;
        }

        return $query->whereNull('parent_id')->where('level', 1);
    }

    public function scopeFinalCategories(Builder $query): Builder
    {
        if (! self::hasColumn('level')) {
            return $query;
        }

        return $query->where('level', 4);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        if (self::hasColumn('sort_order')) {
            $query->orderBy('sort_order');
        }

        return $query->orderBy('category_name');
    }

    public function hasChildren(): bool
    {
        if (! self::hasColumn('parent_id')) {
            return false;
        }

        return $this->children()->exists();
    }

    public function isLeafNode(): bool
    {
        if (! self::hasColumn('parent_id')) {
            return true;
        }

        return ! $this->hasChildren();
    }

    public function isMainCircle(): bool
    {
        if (! self::hasColumn('level')) {
            return false;
        }

        return (int) $this->level === 1;
    }

    public static function hierarchyColumnsAvailable(): bool
    {
        return self::hasColumn('parent_id') && self::hasColumn('level');
    }

    private static function hasColumn(string $column): bool
    {
        static $cache = [];

        if (! array_key_exists($column, $cache)) {
            $cache[$column] = Schema::hasColumn((new self())->getTable(), $column);
        }

        return $cache[$column];
    }
}
