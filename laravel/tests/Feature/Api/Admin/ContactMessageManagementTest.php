<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Admin;

use App\Domain\User\ValueObjects\UserRole;
use App\Infrastructure\Persistence\Eloquent\Models\ContactMessageModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Admin Contact Message Management API Feature Tests.
 */
final class ContactMessageManagementTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = UserModel::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::ADMIN,
        ]);
    }

    public function test_get_all_messages_with_authentication_returns_messages(): void
    {
        ContactMessageModel::factory()->count(3)->create();

        $this->actingAs($this->adminUser);

        $response = $this->getJson('/api/admin/messages');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'subject',
                        'message',
                        'is_read',
                    ],
                ],
                'meta',
            ]);
    }

    public function test_get_all_messages_without_authentication_returns_unauthorized(): void
    {
        $response = $this->getJson('/api/admin/messages');

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'unauthenticated');
    }

    public function test_get_message_by_id_with_valid_id_returns_message(): void
    {
        $message = ContactMessageModel::factory()->create();

        $this->actingAs($this->adminUser);

        $response = $this->getJson("/api/admin/messages/{$message->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $message->uuid)
            ->assertJsonPath('data.subject', $message->subject);
    }

    public function test_get_message_by_id_with_invalid_id_returns_not_found(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->getJson('/api/admin/messages/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'entity_not_found');
    }

    public function test_mark_as_read_with_valid_id_returns_success(): void
    {
        $message = ContactMessageModel::factory()->create(['is_read' => false]);

        $this->actingAs($this->adminUser);

        $response = $this->postJson("/api/admin/messages/{$message->uuid}/mark-read");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('contact_messages', [
            'id' => $message->id,
            'is_read' => true,
        ]);
    }

    public function test_mark_as_read_with_invalid_id_returns_not_found(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->postJson('/api/admin/messages/00000000-0000-0000-0000-000000000000/mark-read');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'entity_not_found');
    }

    public function test_mark_as_unread_with_valid_id_returns_success(): void
    {
        $message = ContactMessageModel::factory()->create(['is_read' => true]);

        $this->actingAs($this->adminUser);

        $response = $this->postJson("/api/admin/messages/{$message->uuid}/mark-unread");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('contact_messages', [
            'id' => $message->id,
            'is_read' => false,
        ]);
    }

    public function test_delete_message_with_valid_id_returns_success(): void
    {
        $message = ContactMessageModel::factory()->create();

        $this->actingAs($this->adminUser);

        $response = $this->deleteJson("/api/admin/messages/{$message->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('contact_messages', [
            'id' => $message->id,
        ]);
    }

    public function test_delete_message_with_invalid_id_returns_not_found(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->deleteJson('/api/admin/messages/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'entity_not_found');
    }
}
