<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('gender', 20)->nullable();
            $table->date('dob')->nullable();
            $table->unsignedSmallInteger('experience_years')->nullable();
            $table->text('experience_summary')->nullable();
            $table->json('skills')->nullable();
            $table->json('interests')->nullable();
            $table->json('social_links')->nullable();
            $table->uuid('profile_photo_file_id')->nullable();
            $table->uuid('cover_photo_file_id')->nullable();

            $table->foreign('profile_photo_file_id')
                ->references('id')
                ->on('files')
                ->nullOnDelete();

            $table->foreign('cover_photo_file_id')
                ->references('id')
                ->on('files')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['profile_photo_file_id']);
            $table->dropForeign(['cover_photo_file_id']);

            $table->dropColumn([
                'gender',
                'dob',
                'experience_years',
                'experience_summary',
                'skills',
                'interests',
                'social_links',
                'profile_photo_file_id',
                'cover_photo_file_id',
            ]);
        });
    }
};
