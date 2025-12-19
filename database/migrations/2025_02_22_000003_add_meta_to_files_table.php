<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table): void {
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                $table->jsonb('meta')->nullable()->default(DB::raw("'{}'::jsonb"));
            } else {
                $table->json('meta')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table): void {
            $table->dropColumn('meta');
        });
    }
};
