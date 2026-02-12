<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AdminBroadcast extends Model
{
    use HasFactory;

    protected $table = 'admin_broadcasts';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'created_by_admin_id',
        'title',
        'message',
        'image_file_id',
        'status',
        'recurrence',
        'send_at',
        'time_of_day',
        'day_of_week',
        'day_of_month',
        'next_run_at',
        'last_sent_at',
        'sent_count',
        'success_count',
        'failure_count',
    ];

    protected $casts = [
        'created_by_admin_id' => 'string',
        'image_file_id' => 'string',
        'send_at' => 'datetime',
        'next_run_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'time_of_day' => 'string',
        'day_of_week' => 'integer',
        'day_of_month' => 'integer',
        'sent_count' => 'integer',
        'success_count' => 'integer',
        'failure_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];


    protected static function booted(): void
    {
        static::creating(function (self $broadcast): void {
            if (empty($broadcast->id)) {
                $broadcast->id = (string) Str::uuid();
            }
        });
    }


    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by_admin_id');
    }

    public function isRecurring(): bool
    {
        return in_array($this->recurrence, ['daily', 'weekly', 'monthly'], true);
    }

    public function computeNextRunAt($fromDateTime = null): ?CarbonImmutable
    {
        $timezone = 'Asia/Kolkata';
        $from = $fromDateTime
            ? CarbonImmutable::parse($fromDateTime)->setTimezone($timezone)
            : CarbonImmutable::now($timezone);

        if ($this->recurrence === 'none') {
            if (! $this->send_at) {
                return null;
            }

            return CarbonImmutable::parse($this->send_at)->setTimezone($timezone);
        }

        if (! $this->time_of_day) {
            return null;
        }

        [$hour, $minute, $second] = array_pad(explode(':', $this->time_of_day), 3, '0');

        $candidate = $from->setTime((int) $hour, (int) $minute, (int) $second);

        if ($this->recurrence === 'daily') {
            return $candidate->lte($from) ? $candidate->addDay() : $candidate;
        }

        if ($this->recurrence === 'weekly') {
            if ($this->day_of_week === null) {
                return null;
            }

            $daysUntil = ($this->day_of_week - $from->dayOfWeek + 7) % 7;
            $candidate = $candidate->addDays($daysUntil);

            return $candidate->lte($from) ? $candidate->addWeek() : $candidate;
        }

        if ($this->recurrence === 'monthly') {
            if ($this->day_of_month === null) {
                return null;
            }

            $day = max(1, min(28, (int) $this->day_of_month));
            $candidate = $candidate->day($day);

            if ($candidate->lte($from)) {
                $candidate = $candidate->addMonthNoOverflow()->day($day);
            }

            return $candidate;
        }

        return null;
    }

    public function normalizeScheduleInputs(): void
    {
        if ($this->recurrence === 'none') {
            $this->time_of_day = null;
            $this->day_of_week = null;
            $this->day_of_month = null;

            return;
        }

        $this->send_at = null;

        if ($this->recurrence !== 'weekly') {
            $this->day_of_week = null;
        }

        if ($this->recurrence !== 'monthly') {
            $this->day_of_month = null;
        }
    }
}
