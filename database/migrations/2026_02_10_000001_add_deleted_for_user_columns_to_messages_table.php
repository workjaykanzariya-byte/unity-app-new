<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (! Schema::hasColumn('messages', 'deleted_for_user1_at')) {
                $table->timestampTz('deleted_for_user1_at')->nullable()->after('deleted_at');
            }

            if (! Schema::hasColumn('messages', 'deleted_for_user2_at')) {
                $table->timestampTz('deleted_for_user2_at')->nullable()->after('deleted_for_user1_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'deleted_for_user2_at')) {
                $table->dropColumn('deleted_for_user2_at');
            }

            if (Schema::hasColumn('messages', 'deleted_for_user1_at')) {
                $table->dropColumn('deleted_for_user1_at');
            }
        });
    }
};
