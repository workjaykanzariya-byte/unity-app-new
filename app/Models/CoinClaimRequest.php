<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoinClaimRequest extends Model
{
    use HasUuids;

    protected $table = 'coin_claim_requests';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'activity_code',
        'payload',
        'status',
        'coins_awarded',
        'reviewed_by_admin_id',
        'reviewed_at',
        'admin_note',
    ];

    protected $casts = [
        'payload' => 'array',
        'coins_awarded' => 'integer',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewedByAdmin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'reviewed_by_admin_id');
    }

    public function formattedPayloadForEmail(): array
    {
        $payload = is_array($this->payload) ? $this->payload : [];

        $fileUrl = function (?string $fileId): ?string {
            if (! $fileId) {
                return null;
            }

            return rtrim((string) config('app.url'), '/').'/api/v1/files/'.$fileId;
        };

        return match ($this->activity_code) {
            'attend_circle_meeting' => [
                'Circle Meeting Date' => $payload['meeting_date'] ?? null,
            ],
            'vyapaarjagat_story' => [
                'VyapaarJagat Story URL' => $payload['story_url'] ?? null,
            ],
            'host_member_spotlight' => [
                'Name of Member Spotlighted' => $payload['member_name'] ?? null,
                'Spotlight Date' => $payload['spotlight_date'] ?? null,
                'Spotlight Link' => $payload['spotlight_link'] ?? null,
            ],
            'bring_speaker' => [
                'Speaker Name' => $payload['speaker_name'] ?? null,
            ],
            'join_circle' => [
                'Circle Name' => $payload['circle_name'] ?? null,
            ],
            'renew_membership' => [
                'Date of Renewal' => $payload['renewal_date'] ?? null,
                'Payment Proof' => $fileUrl($payload['payment_proof_file_id'] ?? null),
            ],
            'invite_visitor' => [
                'Visitor Name' => $payload['visitor_name'] ?? null,
                'Visitor Mobile' => $payload['visitor_mobile'] ?? null,
                'Visitor Email' => $payload['visitor_email'] ?? null,
                'Date of Visit' => $payload['visit_date'] ?? null,
                'Event Confirmation' => $fileUrl($payload['event_confirmation_file_id'] ?? null),
            ],
            'new_member_addition' => [
                'New Member Full Name' => $payload['new_member_name'] ?? null,
                'Mobile Number' => $payload['new_member_mobile'] ?? null,
                'Email' => $payload['new_member_email'] ?? null,
                'Date of Joining' => $payload['joining_date'] ?? null,
                'Membership Confirmation' => $fileUrl($payload['membership_confirmation_file_id'] ?? null),
            ],
            default => collect($payload)
                ->reject(fn ($_, $key) => str_ends_with((string) $key, '_id'))
                ->mapWithKeys(fn ($value, $key) => [ucwords(str_replace('_', ' ', (string) $key)) => is_scalar($value) ? (string) $value : json_encode($value)])
                ->toArray(),
        };
    }
}
