<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_login_otps', function (Blueprint $table): void {
            $table->dropUnique('admin_login_otps_email_unique');
            $table->index(['email', 'created_at'], 'admin_login_otps_email_created_at_index');
            $table->index('expires_at', 'admin_login_otps_expires_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('admin_login_otps', function (Blueprint $table): void {
            $table->dropIndex('admin_login_otps_email_created_at_index');
            $table->dropIndex('admin_login_otps_expires_at_index');
            $table->unique('email', 'admin_login_otps_email_unique');
        });
    }
};
