<?php

namespace App\Support\ActivityHistory;

use Illuminate\Support\Facades\Schema;

class HistoryColumnResolver
{
    /**
     * Candidate columns for the creator side of an activity.
     */
    public const CREATOR_CANDIDATES = [
        'created_by',
        'user_id',
        'owner_id',
        'from_user_id',
        'initiator_id',
        'initiator_user_id',
        'sender_id',
        'given_by_user_id',
        'author_id',
    ];

    /**
     * Candidate columns for the receiver side of an activity.
     */
    public const RECEIVER_CANDIDATES = [
        'to_user_id',
        'peer_user_id',
        'recipient_user_id',
        'receiver_user_id',
        'received_by_user_id',
        'target_user_id',
        'member_id',
        'shared_with_user_id',
        'referred_to_user_id',
    ];

    public function resolveCreatorColumn(string $table): ?string
    {
        return $this->findFirstExistingColumn($table, self::CREATOR_CANDIDATES);
    }

    public function resolveReceiverColumn(string $table): ?string
    {
        return $this->findFirstExistingColumn($table, self::RECEIVER_CANDIDATES);
    }

    protected function findFirstExistingColumn(string $table, array $candidates): ?string
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        foreach ($candidates as $column) {
            if (Schema::hasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }
}
