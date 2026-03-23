<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExpireTrialUsersCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpInMemoryDatabase();
    }

    public function test_it_expires_only_elapsed_trial_users(): void
    {
        $expiredTrialUser = User::query()->create([
            'id' => '88ff3ce7-99cf-4f8e-a7aa-f98e5f1762fd',
            'first_name' => 'Expired',
            'email' => 'expired@example.com',
            'password_hash' => Hash::make('password123'),
            'membership_status' => User::STATUS_FREE_TRIAL,
            'membership_starts_at' => now()->subDays(4),
            'membership_ends_at' => now()->subDay(),
            'membership_expiry' => now()->subDay(),
        ]);

        $activeTrialUser = User::query()->create([
            'id' => '4c5eb5f8-39ca-47ce-ae5d-087f9ecff340',
            'first_name' => 'Active',
            'email' => 'active@example.com',
            'password_hash' => Hash::make('password123'),
            'membership_status' => User::STATUS_FREE_TRIAL,
            'membership_starts_at' => now()->subDay(),
            'membership_ends_at' => now()->addDay(),
            'membership_expiry' => now()->addDay(),
        ]);

        $this->artisan('users:expire-trial')
            ->expectsOutputToContain('Trial users expired: 1')
            ->assertSuccessful();

        $expiredTrialUser->refresh();
        $activeTrialUser->refresh();

        $this->assertSame(User::STATUS_FREE, $expiredTrialUser->membership_status);
        $this->assertSame(User::STATUS_FREE_TRIAL, $activeTrialUser->membership_status);
        $this->assertNotNull($expiredTrialUser->membership_expiry);
    }

    private function setUpInMemoryDatabase(): void
    {
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();
            $table->string('display_name', 150)->nullable();
            $table->string('email', 255)->unique();
            $table->string('password_hash');
            $table->string('membership_status', 50)->nullable();
            $table->timestamp('membership_starts_at')->nullable();
            $table->timestamp('membership_ends_at')->nullable();
            $table->timestamp('membership_expiry')->nullable();
            $table->string('public_profile_slug', 80)->nullable()->unique();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
