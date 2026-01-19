<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Circle extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_OPTIONS = ['pending', 'active', 'archived'];
    public const TYPE_OPTIONS = ['public', 'private'];

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        // 'slug' removed from fillable — backend generates it
        'description',
        'purpose',
        'announcement',
        'founder_user_id',
        'template_id',
        'status',
        'calendar',
        'city_id',
        'industry_tags',
        'referral_score',
        'visitor_count',
        'type',
    ];

    protected $casts = [
        'calendar' => 'array',
        'industry_tags' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function (Circle $circle) {

            /** Generate UUID */
            if (empty($circle->id)) {
                $circle->id = Str::uuid()->toString();
            }

            /** Auto-generate slug if not provided */
            if (empty($circle->slug)) {

                $base = Str::slug($circle->name ?: 'circle');

                if ($base === '') {
                    $base = 'circle';
                }

                $circle->slug = static::generateUniqueSlug($base);
            }

            /** Default status */
            if (empty($circle->status)) {
                $circle->status = 'pending';
            }
        });
    }

    public function founder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'founder_user_id');
    }

    public function founderUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'founder_user_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CircleTemplate::class, 'template_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(CircleMember::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(CircleMember::class);
    }

    public static function generateUniqueSlug(string $name, ?string $ignoreId = null): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'circle';
        }

        $slug = $base;
        $i = 1;

        while (
            static::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }
}
