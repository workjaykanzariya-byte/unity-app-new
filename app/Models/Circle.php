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
    public const STAGE_OPTIONS = ['conceptualized', 'foundation', 'pre_launch', 'launched', 'growth', 'high_impact'];
    public const MEETING_MODE_OPTIONS = ['online', 'offline', 'hybrid'];
    public const MEETING_FREQUENCY_OPTIONS = ['monthly', 'quarterly'];

    public const STAGE_LABELS = [
        'conceptualized' => 'Conceptualized Circle',
        'foundation' => 'Foundation Circle',
        'pre_launch' => 'Pre-Launch Circle',
        'launched' => 'Launched Circle',
        'growth' => 'Growth Circle',
        'high_impact' => 'High-Impact Circle',
    ];

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
        'stage',
        'meeting_mode',
        'meeting_frequency',
        'meeting_repeat',
        'launch_date',
        'annual_fee',
        'director_user_id',
        'industry_director_user_id',
        'ded_user_id',
        'active_members_count',
        'image_file_id',
    ];

    protected $casts = [
        'calendar' => 'array',
        'industry_tags' => 'array',
        'launch_date' => 'date',
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

    public function directorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'director_user_id');
    }

    public function industryDirectorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'industry_director_user_id');
    }

    public function dedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ded_user_id');
    }

    public function imageFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_file_id');
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
