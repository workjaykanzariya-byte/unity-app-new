<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('gender')->nullable()->after('last_name');
            $table->date('dob')->nullable()->after('gender');
            $table->integer('experience_years')->nullable()->after('dob');
            $table->text('experience_summary')->nullable()->after('experience_years');
            $table->jsonb('skills')->nullable()->after('experience_summary');
            $table->jsonb('interests')->nullable()->after('skills');
            $table->jsonb('social_links')->nullable()->after('interests');
            $table->uuid('profile_photo_file_id')->nullable()->after('social_links');
            $table->uuid('cover_photo_file_id')->nullable()->after('profile_photo_file_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
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
