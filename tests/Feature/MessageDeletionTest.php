<?php

namespace Tests\Feature;

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class MessageDeletionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpInMemoryDatabase();
    }

    public function test_delete_for_me_hides_only_for_current_user(): void
    {
        [$userA, $userB, $chat] = $this->makeTwoUserChat();

        $message = Message::create([
            'id' => (string) Str::uuid(),
            'chat_id' => $chat->id,
            'sender_id' => $userA->id,
            'content' => 'hello',
            'is_read' => false,
        ]);

        $this->actingAs($userA, 'sanctum')
            ->postJson('/api/v1/messages/' . $message->id . '/delete-for-me')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Message deleted for you',
            ]);

        $message->refresh();
        $this->assertNotNull($message->deleted_for_user1_at ?? $message->deleted_for_user2_at);
        $this->assertNull($message->deleted_at);

        $this->actingAs($userA, 'sanctum')
            ->getJson('/api/v1/chats/' . $chat->id . '/messages')
            ->assertOk()
            ->assertJsonCount(0, 'data.items');

        $this->actingAs($userB, 'sanctum')
            ->getJson('/api/v1/chats/' . $chat->id . '/messages')
            ->assertOk()
            ->assertJsonCount(1, 'data.items');
    }

    public function test_delete_for_everyone_hides_for_both_users(): void
    {
        [$userA, $userB, $chat] = $this->makeTwoUserChat();

        $message = Message::create([
            'id' => (string) Str::uuid(),
            'chat_id' => $chat->id,
            'sender_id' => $userA->id,
            'content' => 'hello',
            'is_read' => false,
        ]);

        $this->actingAs($userA, 'sanctum')
            ->postJson('/api/v1/messages/' . $message->id . '/delete-for-everyone')
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Message deleted for everyone',
            ]);

        $message->refresh();
        $this->assertNotNull($message->deleted_at);

        $this->actingAs($userA, 'sanctum')
            ->getJson('/api/v1/chats/' . $chat->id . '/messages')
            ->assertOk()
            ->assertJsonCount(0, 'data.items');

        $this->actingAs($userB, 'sanctum')
            ->getJson('/api/v1/chats/' . $chat->id . '/messages')
            ->assertOk()
            ->assertJsonCount(0, 'data.items');

        $this->assertDatabaseCount('messages', 1);
    }

    public function test_non_sender_cannot_delete_for_everyone(): void
    {
        [$userA, $userB, $chat] = $this->makeTwoUserChat();

        $message = Message::create([
            'id' => (string) Str::uuid(),
            'chat_id' => $chat->id,
            'sender_id' => $userA->id,
            'content' => 'hello',
            'is_read' => false,
        ]);

        $this->actingAs($userB, 'sanctum')
            ->postJson('/api/v1/messages/' . $message->id . '/delete-for-everyone')
            ->assertForbidden();

        $message->refresh();
        $this->assertNull($message->deleted_at);
    }

    private function makeTwoUserChat(): array
    {
        $userA = $this->makeUser('a@example.com');
        $userB = $this->makeUser('b@example.com');

        [$user1Id, $user2Id] = strcmp($userA->id, $userB->id) <= 0
            ? [$userA->id, $userB->id]
            : [$userB->id, $userA->id];

        $chat = Chat::create([
            'id' => (string) Str::uuid(),
            'user1_id' => $user1Id,
            'user2_id' => $user2Id,
        ]);

        return [$userA, $userB, $chat];
    }

    private function makeUser(string $email): User
    {
        return User::create([
            'id' => (string) Str::uuid(),
            'first_name' => 'Test',
            'last_name' => 'User',
            'display_name' => 'Test User',
            'email' => $email,
            'phone' => substr(preg_replace('/\D+/', '', (string) microtime(true) . random_int(1000, 9999)), 0, 10),
            'password_hash' => Hash::make('password'),
        ]);
    }

    private function setUpInMemoryDatabase(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('chats');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();
            $table->string('display_name', 150)->nullable();
            $table->string('email', 255)->nullable()->unique();
            $table->string('phone', 20)->nullable()->unique();
            $table->string('password_hash');
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

        Schema::create('chats', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user1_id');
            $table->uuid('user2_id');
            $table->timestampTz('last_message_at')->nullable();
            $table->uuid('last_message_id')->nullable();
            $table->timestamps();

            $table->unique(['user1_id', 'user2_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('chat_id');
            $table->uuid('sender_id');
            $table->text('content')->nullable();
            $table->json('attachments')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestampTz('deleted_for_user1_at')->nullable();
            $table->timestampTz('deleted_for_user2_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
