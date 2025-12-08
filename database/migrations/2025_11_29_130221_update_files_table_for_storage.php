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
            $table->string('disk', 100)->default('public');
            $table->text('path')->nullable();
            $table->text('original_name')->nullable();
            $table->unsignedBigInteger('size')->nullable();
        });

        DB::table('files')->update([
            'path' => DB::raw("COALESCE(path, s3_key)"),
            'size' => DB::raw("COALESCE(size, size_bytes)"),
        ]);
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropColumn(['disk', 'path', 'original_name', 'size']);
        });
    }
};
