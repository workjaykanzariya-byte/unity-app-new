<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LifeImpactHistory extends Model
{
    use HasFactory;

    private const ACTIVITY_TYPE_IMPACT_MAP = [
        'business_deal' => 5,
        'testimonial' => 5,
        'referral' => 1,
        'visitor_registration' => 1,
    ];

    protected $table = 'life_impact_histories';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'activity_id',
        'action_key',
        'action_label',
        'impact_category',
        'life_impacted',
        'remarks',
        'status',
        'approved_at',
        'counted_in_total',
        'created_by',
        'meta',
    ];

    protected $casts = [
        'life_impacted' => 'integer',
        'impact_value' => 'integer',
        'counted_in_total' => 'boolean',
        'approved_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function triggeredByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function resolveImpactValue(): int
    {
        if (array_key_exists('life_impacted', $this->attributes) && $this->attributes['life_impacted'] !== null) {
            return (int) $this->attributes['life_impacted'];
        }

        if (array_key_exists('impact_value', $this->attributes) && $this->attributes['impact_value'] !== null) {
            return (int) $this->attributes['impact_value'];
        }

        $metaImpactValue = data_get($this->meta, 'impact_value');
        if (is_numeric($metaImpactValue)) {
            return (int) $metaImpactValue;
        }

        return (int) (self::ACTIVITY_TYPE_IMPACT_MAP[(string) $this->activity_type] ?? 0);
    }

    public function resolveImpactValueSource(): string
    {
        if (array_key_exists('life_impacted', $this->attributes) && $this->attributes['life_impacted'] !== null) {
            return 'column:life_impacted';
        }

        if (array_key_exists('impact_value', $this->attributes) && $this->attributes['impact_value'] !== null) {
            return 'column:impact_value';
        }

        if (is_numeric(data_get($this->meta, 'impact_value'))) {
            return 'meta:impact_value';
        }

        return array_key_exists((string) $this->activity_type, self::ACTIVITY_TYPE_IMPACT_MAP)
            ? 'mapping:activity_type'
            : 'default:zero';
    }
}
