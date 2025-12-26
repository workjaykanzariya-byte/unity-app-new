<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_login_otps')) {
            return;
        }

        DB::statement("ALTER TABLE admin_login_otps ALTER COLUMN expires_at TYPE timestamptz USING expires_at::timestamptz");
        DB::statement("ALTER TABLE admin_login_otps ALTER COLUMN last_sent_at TYPE timestamptz USING last_sent_at::timestamptz");
        DB::statement("ALTER TABLE admin_login_otps ALTER COLUMN created_at TYPE timestamptz USING created_at::timestamptz");
        DB::statement("ALTER TABLE admin_login_otps ALTER COLUMN updated_at TYPE timestamptz USING updated_at::timestamptz");
    }

    public function down(): void
    {
        // No-op: keeping timestamptz types
    }
};
