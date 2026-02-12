<?php

namespace App\Console\Commands;

use App\Jobs\SendAdminBroadcastJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BroadcastsRunCommand extends Command
{
    protected $signature = 'broadcasts:run';

    protected $description = 'Dispatch scheduled admin broadcasts that are due.';

    public function handle(): int
    {
        $dueRows = DB::select(<<<'SQL'
            WITH picked AS (
                SELECT id
                FROM admin_broadcasts
                WHERE status = 'scheduled'
                  AND next_run_at IS NOT NULL
                  AND next_run_at <= NOW()
                ORDER BY next_run_at ASC
                FOR UPDATE SKIP LOCKED
                LIMIT 20
            )
            UPDATE admin_broadcasts ab
            SET status = 'sending',
                updated_at = NOW()
            FROM picked
            WHERE ab.id = picked.id
            RETURNING ab.id
        SQL);

        if ($dueRows === []) {
            $this->info('No due broadcasts found.');

            return self::SUCCESS;
        }

        foreach ($dueRows as $row) {
            SendAdminBroadcastJob::dispatch((string) $row->id);
        }

        $this->info('Queued ' . count($dueRows) . ' broadcast(s).');

        return self::SUCCESS;
    }
}
