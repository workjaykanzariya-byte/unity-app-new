<?php

namespace Tests\Feature\Auth;

use Illuminate\Database\Schema\Blueprint;
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
