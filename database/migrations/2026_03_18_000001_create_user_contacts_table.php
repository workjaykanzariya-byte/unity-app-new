<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_contacts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->index();
            $table->string('name', 255);
            $table->string('mobile', 20);
            $table->string('mobile_normalized', 20)->index();
            $table->string('device', 50)->nullable();
            $table->string('app_version', 20)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'mobile_normalized']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_contacts');
    }
};
