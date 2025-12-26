<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admin_login_otps')) {
            return;
        }

        Schema::create('admin_login_otps', function (Blueprint $table): void {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('email')->index();
            $table->string('otp_hash');
            $table->timestampTz('expires_at');
            $table->timestampTz('last_sent_at')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_login_otps');
    }
};
