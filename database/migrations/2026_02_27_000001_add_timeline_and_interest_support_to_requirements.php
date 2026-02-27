<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requirements', function (Blueprint $table): void {
            if (! Schema::hasColumn('requirements', 'timeline_post_id')) {
                $table->uuid('timeline_post_id')->nullable()->after('status');
            }

            if (! Schema::hasColumn('requirements', 'closed_at')) {
                $table->timestampTz('closed_at')->nullable()->after('timeline_post_id');
            }

            if (! Schema::hasColumn('requirements', 'completed_at')) {
                $table->timestampTz('completed_at')->nullable()->after('closed_at');
            }
        });

        Schema::table('requirements', function (Blueprint $table): void {
            $table->index(['status', 'created_at'], 'idx_requirements_status_created_at');
        });

        DB::statement('ALTER TABLE requirements ADD CONSTRAINT requirements_timeline_post_id_foreign FOREIGN KEY (timeline_post_id) REFERENCES posts(id) ON DELETE SET NULL');

        Schema::create('requirement_interests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('requirement_id');
            $table->uuid('user_id');
            $table->string('source', 50)->nullable();
            $table->text('comment')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('requirement_id')->references('id')->on('requirements')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['requirement_id', 'user_id'], 'uq_requirement_interests_requirement_user');
            $table->index('requirement_id', 'idx_requirement_interests_requirement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requirement_interests');

        DB::statement('ALTER TABLE requirements DROP CONSTRAINT IF EXISTS requirements_timeline_post_id_foreign');

        Schema::table('requirements', function (Blueprint $table): void {
            $table->dropIndex('idx_requirements_status_created_at');
            $table->dropColumn(['timeline_post_id', 'closed_at', 'completed_at']);
        });
    }
};
