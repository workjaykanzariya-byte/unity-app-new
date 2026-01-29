<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peer_recommendations', function (Blueprint $table) {
            if (! Schema::hasColumn('peer_recommendations', 'status')) {
                $table->string('status')->default('pending');
            }

            if (! Schema::hasColumn('peer_recommendations', 'reviewed_at')) {
                $table->timestampTz('reviewed_at')->nullable();
            }

            if (! Schema::hasColumn('peer_recommendations', 'reviewed_by_admin_user_id')) {
                $table->uuid('reviewed_by_admin_user_id')->nullable();
            }
        });

        DB::statement('CREATE INDEX IF NOT EXISTS idx_peer_recommendations_user_id ON peer_recommendations(user_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_peer_recommendations_status ON peer_recommendations(status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_peer_recommendations_created_at ON peer_recommendations(created_at)');

        DB::statement(<<<'SQL'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_constraint
        WHERE conname = 'peer_recommendations_reviewed_by_admin_user_id_fkey'
    ) THEN
        ALTER TABLE peer_recommendations
            ADD CONSTRAINT peer_recommendations_reviewed_by_admin_user_id_fkey
            FOREIGN KEY (reviewed_by_admin_user_id)
            REFERENCES admin_users(id)
            ON DELETE SET NULL;
    END IF;
END
$$;
SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_peer_recommendations_user_id');
        DB::statement('DROP INDEX IF EXISTS idx_peer_recommendations_status');
        DB::statement('DROP INDEX IF EXISTS idx_peer_recommendations_created_at');

        DB::statement('ALTER TABLE peer_recommendations DROP CONSTRAINT IF EXISTS peer_recommendations_reviewed_by_admin_user_id_fkey');

        Schema::table('peer_recommendations', function (Blueprint $table) {
            if (Schema::hasColumn('peer_recommendations', 'reviewed_by_admin_user_id')) {
                $table->dropColumn('reviewed_by_admin_user_id');
            }

            if (Schema::hasColumn('peer_recommendations', 'reviewed_at')) {
                $table->dropColumn('reviewed_at');
            }

            if (Schema::hasColumn('peer_recommendations', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
