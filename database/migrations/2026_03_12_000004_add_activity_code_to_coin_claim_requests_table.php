<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('coin_claim_requests', 'activity_code')) {
            Schema::table('coin_claim_requests', function (Blueprint $table): void {
                $table->string('activity_code', 100)->nullable();
            });
        }

        DB::statement('CREATE INDEX IF NOT EXISTS coin_claim_requests_activity_code_idx ON coin_claim_requests (activity_code)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS coin_claim_requests_activity_code_idx');

        if (Schema::hasColumn('coin_claim_requests', 'activity_code')) {
            Schema::table('coin_claim_requests', function (Blueprint $table): void {
                $table->dropColumn('activity_code');
            });
        }
    }
};
