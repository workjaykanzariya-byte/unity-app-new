<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'first_name',
        'last_name',
        'display_name',
        'email',
        'phone',
        'city_id',
        'password_hash',
        'membership_status',
        'membership_expiry',
        'coins_balance',
        'designation',
        'company_name',
        'profile_photo_url',
        'short_bio',
        'long_bio_html',
        'industry_tags',
        'business_type',
        'turnover_range',
        'introduced_by',
        'members_introduced_count',
        'influencer_stars',
        'target_regions',
        'target_business_categories',
        'hobbies_interests',
        'leadership_roles',
        'is_sponsored_member',
        'public_profile_slug',
        'special_recognitions',
        'gdpr_deleted_at',
        'anonymized_at',
        'is_gdpr_exported',
        'last_login_at',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected $casts = [
        'industry_tags' => 'array',
        'target_regions' => 'array',
        'target_business_categories' => 'array',
        'hobbies_interests' => 'array',
        'leadership_roles' => 'array',
        'special_recognitions' => 'array',
        'membership_expiry' => 'datetime',
        'gdpr_deleted_at' => 'datetime',
        'anonymized_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            // Ensure UUID primary key
            if (empty($user->id)) {
                $user->id = Str::uuid()->toString();
            }

            // Ensure display_name is set (fallback)
            if (empty($user->display_name)) {
                $user->display_name = trim($user->first_name . ' ' . ($user->last_name ?? ''));
            }

            // Auto-generate unique public_profile_slug if not provided
            if (empty($user->public_profile_slug)) {
                // Base string for slug
                $base = Str::slug(
                    $user->display_name
                    ?: trim($user->first_name . ' ' . ($user->last_name ?? ''))
                    ?: $user->email
                    ?: 'user'
                );

                if ($base === '') {
                    $base = 'user';
                }

                $slug = $base;
                $i = 1;

                // Make sure slug is unique
                while (static::where('public_profile_slug', $slug)->exists()) {
                    $slug = $base . '-' . $i;
                    $i++;
                }

                $user->public_profile_slug = $slug;
            }
        });
    }

    public function links(): HasMany
    {
        return $this->hasMany(UserLink::class);
    }

    public function userLinks(): HasMany
    {
        return $this->hasMany(UserLink::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function introducedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'introduced_by');
    }

    public function foundedCircles(): HasMany
    {
        return $this->hasMany(Circle::class, 'founder_user_id');
    }

    public function circleMembers(): HasMany
    {
        return $this->hasMany(CircleMember::class);
    }

    public function requestedConnections(): HasMany
    {
        return $this->hasMany(Connection::class, 'requester_id');
    }

    public function receivedConnections(): HasMany
    {
        return $this->hasMany(Connection::class, 'addressee_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function postComments(): HasMany
    {
        return $this->hasMany(PostComment::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'created_by_user_id');
    }

    public function eventRsvps(): HasMany
    {
        return $this->hasMany(EventRsvp::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function relatedActivities(): HasMany
    {
        return $this->hasMany(Activity::class, 'related_user_id');
    }

    public function verifiedActivities(): HasMany
    {
        return $this->hasMany(Activity::class, 'verified_by_admin_id');
    }

    public function coinLedgers(): HasMany
    {
        return $this->hasMany(CoinLedger::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(Requirement::class);
    }

    public function supportRequests(): HasMany
    {
        return $this->hasMany(SupportRequest::class);
    }

    public function chatsInitiated(): HasMany
    {
        return $this->hasMany(Chat::class, 'user1_id');
    }

    public function chatsReceived(): HasMany
    {
        return $this->hasMany(Chat::class, 'user2_id');
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function uploadedFiles(): HasMany
    {
        return $this->hasMany(FileModel::class, 'uploader_user_id');
    }

    public function adminAuditLogs(): HasMany
    {
        return $this->hasMany(AdminAuditLog::class, 'admin_user_id');
    }

    public function referralLinks(): HasMany
    {
        return $this->hasMany(ReferralLink::class, 'referrer_user_id');
    }

    public function visitorLeads(): HasMany
    {
        return $this->hasMany(VisitorLead::class, 'converted_user_id');
    }

    public function dataExports(): HasMany
    {
        return $this->hasMany(DataExport::class);
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }
}
