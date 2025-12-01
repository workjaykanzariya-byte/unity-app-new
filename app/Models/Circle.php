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

                $slug = $base;
                $i = 1;

                // Ensure uniqueness
                while (Circle::where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $i;
                    $i++;
                }

                $circle->slug = $slug;
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
