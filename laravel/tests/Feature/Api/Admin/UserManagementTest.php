<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Admin;

use App\Domain\User\ValueObjects\UserRole;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Admin User Management API Feature Tests.
 */
final class UserManagementTest extends TestCase
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

    public function test_get_all_users_with_authentication_returns_users(): void
    {
        UserModel::factory()->count(2)->create();

        $this->actingAs($this->adminUser);

        $response = $this->getJson('/api/admin/users');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            // admin user from setUp + 2 created = 3 total
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'role',
                    ],
                ],
                'meta',
            ]);
    }

    public function test_get_all_users_without_authentication_returns_unauthorized(): void
    {
        $response = $this->getJson('/api/admin/users');

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'unauthenticated');
    }

    public function test_get_user_by_id_with_valid_id_returns_user(): void
    {
        $user = UserModel::factory()->create();

        $this->actingAs($this->adminUser);

        $response = $this->getJson("/api/admin/users/{$user->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $user->uuid)
            ->assertJsonPath('data.email', $user->email->getValue());
    }

    public function test_get_user_by_id_with_invalid_id_returns_not_found(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->getJson('/api/admin/users/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'entity_not_found');
    }

    public function test_create_user_with_valid_data_returns_created(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'name' => 'Jane Editor',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'role' => UserRole::EDITOR->value,
        ];

        $response = $this->postJson('/api/admin/users', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Jane Editor')
            ->assertJsonPath('data.email', 'jane@example.com')
            ->assertJsonPath('data.role', UserRole::EDITOR->value);

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'name' => 'Jane Editor',
        ]);
    }

    public function test_create_user_with_missing_name_returns_validation_error(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'email' => 'jane@example.com',
            'password' => 'password123',
            'role' => UserRole::EDITOR->value,
        ];

        $response = $this->postJson('/api/admin/users', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'validation_error')
            ->assertJsonStructure([
                'errors' => [
                    'name',
                ],
            ]);
    }

    public function test_create_user_with_invalid_email_returns_validation_error(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'name' => 'Jane Editor',
            'email' => 'not-an-email',
            'password' => 'password123',
            'role' => UserRole::EDITOR->value,
        ];

        $response = $this->postJson('/api/admin/users', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'validation_error')
            ->assertJsonStructure([
                'errors' => [
                    'email',
                ],
            ]);
    }

    public function test_create_user_with_duplicate_email_returns_validation_error(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'name' => 'Duplicate',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role' => UserRole::EDITOR->value,
        ];

        $response = $this->postJson('/api/admin/users', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'validation_error')
            ->assertJsonStructure([
                'errors' => [
                    'email',
                ],
            ]);
    }

    public function test_update_user_with_valid_data_returns_success(): void
    {
        $user = UserModel::factory()->create(['name' => 'Original Name']);

        $this->actingAs($this->adminUser);

        $payload = [
            'name' => 'Updated Name',
            'email' => $user->email->getValue(),
            'password' => 'newpassword123',
            'role' => UserRole::AUTHOR->value,
        ];

        $response = $this->putJson("/api/admin/users/{$user->uuid}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.role', UserRole::AUTHOR->value);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_delete_user_with_valid_id_returns_success(): void
    {
        $user = UserModel::factory()->create();

        $this->actingAs($this->adminUser);

        $response = $this->deleteJson("/api/admin/users/{$user->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_delete_user_with_invalid_id_returns_not_found(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->deleteJson('/api/admin/users/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'entity_not_found');
    }
}
