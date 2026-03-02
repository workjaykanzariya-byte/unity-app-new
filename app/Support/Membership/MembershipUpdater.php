<?php

namespace App\Support\Membership;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MembershipUpdater
{
    public function activatePaidMembership(User $user, array $attributes = []): bool
    {
        $dirty = [];

        $startedAt = $attributes['membership_starts_at'] ?? null;
        $endsAt = $attributes['membership_ends_at'] ?? null;
        $lastPaymentAt = $attributes['last_payment_at'] ?? now();

        if ($startedAt !== null) {
            $dirty['membership_starts_at'] = $startedAt;
        }

        if ($endsAt !== null) {
            $dirty['membership_ends_at'] = $endsAt;
            if ($this->hasUserColumn('membership_expiry')) {
                $dirty['membership_expiry'] = $endsAt;
            }
        }

        if ($lastPaymentAt !== null) {
            $dirty['last_payment_at'] = $lastPaymentAt;
        }

        if ($this->hasUserColumn('membership_type')) {
            $dirty['membership_type'] = 'only_unity_peer';
        } elseif ($this->hasUserColumn('membership')) {
            $dirty['membership'] = 'only_unity_peer';
        } elseif ($this->hasUserColumn('membership_status')) {
            $dirty['membership_status'] = 'only_unity_peer';
        } else {
            Log::warning('No known membership column found; skipping direct status update', [
                'user_id' => $user->id,
            ]);
        }

        if ($dirty === []) {
            return false;
        }

        $user->forceFill($dirty);

        return $user->isDirty() ? $user->save() : false;
    }

    private function hasUserColumn(string $column): bool
    {
        static $columns = [];

        if (! array_key_exists($column, $columns)) {
            $columns[$column] = Schema::hasColumn('users', $column);
        }

        return $columns[$column];
    }
}
