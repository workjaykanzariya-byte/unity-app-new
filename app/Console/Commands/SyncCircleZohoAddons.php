<?php

namespace App\Console\Commands;

use App\Enums\CircleBillingTerm;
use App\Models\Circle;
use App\Services\Zoho\CircleAddonSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncCircleZohoAddons extends Command
{
    protected $signature = 'circles:sync-zoho-addons
        {--circle= : Sync only a specific circle UUID}
        {--term= : Sync only one term (monthly|quarterly|half_yearly|yearly)}';

    protected $description = 'Sync circles with Zoho Billing addons';

    public function __construct(private readonly CircleAddonSyncService $syncService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $circleId = trim((string) $this->option('circle'));
        $termFilter = trim((string) $this->option('term'));

        if ($termFilter !== '' && ! in_array($termFilter, CircleBillingTerm::values(), true)) {
            $this->error('Invalid --term value. Allowed: ' . implode(', ', CircleBillingTerm::values()));

            return self::FAILURE;
        }

        $query = Circle::query()->orderBy('created_at');

        if ($circleId !== '') {
            $query->where('id', $circleId);
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        $query->chunkById(50, function ($circles) use (&$created, &$updated, &$skipped, &$errors, $termFilter): void {
            foreach ($circles as $circle) {
                try {
                    if ($termFilter !== '') {
                        $this->line("Syncing circle={$circle->id} term={$termFilter}");
                        $result = $this->syncService->syncCircleTerm($circle, CircleBillingTerm::from($termFilter));
                    } else {
                        $this->line("Syncing circle={$circle->id}");
                        $result = $this->syncService->syncCircle($circle);
                    }

                    $created += (int) ($result['created'] ?? 0);
                    $updated += (int) ($result['updated'] ?? 0);
                    $skipped += (int) ($result['skipped'] ?? 0);
                    $errors += (int) ($result['errors'] ?? 0);

                    if ($this->getOutput()->isVerbose()) {
                        $this->line('Result: ' . json_encode($result));
                    }
                } catch (\Throwable $throwable) {
                    $errors++;

                    Log::error('Circle addon backfill failed', [
                        'circle_id' => $circle->id,
                        'error' => $throwable->getMessage(),
                    ]);

                    $this->warn("Failed circle={$circle->id}: {$throwable->getMessage()}");
                }
            }
        }, 'id');

        $this->info("Done. created={$created}, updated={$updated}, skipped={$skipped}, errors={$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
