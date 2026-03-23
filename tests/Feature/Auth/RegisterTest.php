<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpInMemoryDatabase();
    }

    public function test_users_with_same_name_can_register(): void
    {
        $firstPayload = [
            'first_name'   => 'Pravin',
            'last_name'    => 'Parmar',
            'email'        => 'user1@example.com',
            'phone'        => '1111111111',
            'password'     => 'password123',
            'password_confirmation' => 'password123',
        ];

        $secondPayload = [
            'first_name'   => 'Pravin',
            'last_name'    => 'Parmar',
            'email'        => 'user2@example.com',
            'phone'        => '2222222222',
            'password'     => 'password123',
            'password_confirmation' => 'password123',
        ];

        $firstResponse = $this->postJson('/api/v1/auth/register', $firstPayload);
        $firstResponse->assertStatus(201)->assertJson(['success' => true]);
        $firstResponse->assertJsonPath('data.user.membership_status', User::STATUS_FREE_TRIAL);

        $firstMembershipStartsAt = Carbon::parse($firstResponse->json('data.user.membership_starts_at'));
        $firstMembershipExpiry = Carbon::parse($firstResponse->json('data.user.membership_expiry'));
        $this->assertTrue(
            $firstMembershipExpiry->equalTo($firstMembershipStartsAt->copy()->addDays(User::FREE_TRIAL_DURATION_DAYS)),
            'Newly registered user should receive a 3-day trial expiry window.'
        );

        $secondResponse = $this->postJson('/api/v1/auth/register', $secondPayload);
        $secondResponse->assertStatus(201)->assertJson(['success' => true]);

        $this->assertNotSame(
            $firstResponse->json('data.user.public_profile_slug'),
            $secondResponse->json('data.user.public_profile_slug'),
            'Users with the same name should receive different profile slugs.'
        );
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        $payload = [
            'first_name'   => 'Alex',
            'last_name'    => 'Smith',
            'email'        => 'duplicate@example.com',
            'phone'        => '3333333333',
            'password'     => 'password123',
            'password_confirmation' => 'password123',
        ];

        $this->postJson('/api/v1/auth/register', $payload)->assertStatus(201);

        $duplicateEmailResponse = $this->postJson('/api/v1/auth/register', $payload);
        $duplicateEmailResponse->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_login_expires_elapsed_trial_membership_as_fallback(): void
    {
        $user = User::query()->create([
            'id' => '1cb9148a-b1f2-4ed5-8fa6-c268ca6956c5',
            'first_name' => 'Trial',
            'last_name' => 'User',
            'display_name' => 'Trial User',
            'email' => 'trial-fallback@example.com',
            'phone' => '8888888888',
            'password_hash' => Hash::make('password123'),
            'membership_status' => User::STATUS_FREE_TRIAL,
            'membership_starts_at' => now()->subDays(4),
            'membership_ends_at' => now()->subHour(),
            'membership_expiry' => now()->subHour(),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'trial-fallback@example.com',
            'password' => 'password123',
        ])->assertStatus(200);

        $user->refresh();
        $this->assertSame(User::STATUS_FREE, $user->membership_status);
    }

    private function setUpInMemoryDatabase(): void
    {
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();
            $table->string('display_name', 150)->nullable();
            $table->string('email', 255)->unique();
            $table->string('phone', 20)->nullable()->unique();
            $table->string('password_hash');
            $table->string('company_name', 150)->nullable();
            $table->string('designation', 100)->nullable();
            $table->uuid('city_id')->nullable();
            $table->string('membership_status', 50)->default('visitor');
            $table->timestamp('membership_starts_at')->nullable();
            $table->timestamp('membership_ends_at')->nullable();
            $table->timestamp('membership_expiry')->nullable();
            $table->bigInteger('coins_balance')->default(0);
            $table->string('public_profile_slug', 80)->nullable()->unique();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tokenable_type');
            $table->uuid('tokenable_id');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['tokenable_type', 'tokenable_id']);
        });
    }
}
