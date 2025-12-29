<?php

namespace App\Models;

use App\Enums\ReferralType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Referral extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'referrals';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'referral_type',
        'referral_date',
        'referral_of',
        'phone',
        'email',
        'address',
        'hot_value',
        'remarks',
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
    ];

    protected $appends = [
        'referral_type_label',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function getReferralTypeLabelAttribute(): ?string
    {
        $type = ReferralType::fromInput($this->referral_type);

        return $type?->label();
    }
}
