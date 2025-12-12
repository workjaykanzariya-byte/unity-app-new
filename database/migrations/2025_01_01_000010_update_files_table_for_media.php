<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {
            if (! Schema::hasColumn('files', 'disk')) {
                $table->string('disk', 50)->default('uploads');
            }
            if (! Schema::hasColumn('files', 'path')) {
                $table->text('path')->nullable();
            }
            if (! Schema::hasColumn('files', 'original_name')) {
                $table->string('original_name')->nullable();
            }
            if (! Schema::hasColumn('files', 'status')) {
                $table->string('status', 50)->default('ready');
            }
            if (! Schema::hasColumn('files', 'updated_at')) {
                $table->timestampTz('updated_at')->nullable();
            }
        });

        if (Schema::hasColumn('files', 's3_key')) {
            DB::statement("UPDATE files SET path = COALESCE(path, s3_key)");
        }

        DB::statement("UPDATE files SET status = 'ready' WHERE status IS NULL");
        DB::statement("UPDATE files SET updated_at = COALESCE(updated_at, created_at)");
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            if (Schema::hasColumn('files', 'disk')) {
                $table->dropColumn('disk');
            }
            if (Schema::hasColumn('files', 'path')) {
                $table->dropColumn('path');
            }
            if (Schema::hasColumn('files', 'original_name')) {
                $table->dropColumn('original_name');
            }
            if (Schema::hasColumn('files', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('files', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }
};
