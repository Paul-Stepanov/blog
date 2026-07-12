<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Admin;

use App\Domain\User\ValueObjects\UserRole;
use App\Infrastructure\Persistence\Eloquent\Models\TagModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Admin Tag Management API Feature Tests.
 */
final class TagManagementTest extends TestCase
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

    public function test_get_all_tags_with_authentication_returns_tags(): void
    {
        TagModel::factory()->count(3)->create();

        $this->actingAs($this->adminUser);

        $response = $this->getJson('/api/admin/tags');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                    ],
                ],
            ]);
    }

    public function test_get_all_tags_without_authentication_returns_unauthorized(): void
    {
        $response = $this->getJson('/api/admin/tags');

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'unauthenticated');
    }

    public function test_get_tag_by_id_with_valid_id_returns_tag(): void
    {
        $tag = TagModel::factory()->create();

        $this->actingAs($this->adminUser);

        $response = $this->getJson("/api/admin/tags/{$tag->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $tag->uuid)
            ->assertJsonPath('data.name', $tag->name);
    }

    public function test_get_tag_by_id_with_invalid_id_returns_not_found(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->getJson('/api/admin/tags/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'entity_not_found');
    }

    public function test_create_tag_with_valid_data_returns_created(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'name' => 'Test Tag',
            'slug' => 'test-tag',
        ];

        $response = $this->postJson('/api/admin/tags', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Test Tag')
            ->assertJsonPath('data.slug', 'test-tag');

        $this->assertDatabaseHas('tags', [
            'name' => 'Test Tag',
            'slug' => 'test-tag',
        ]);
    }

    public function test_create_tag_with_missing_name_returns_validation_error(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->postJson('/api/admin/tags', ['slug' => 'some-slug']);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'validation_error')
            ->assertJsonStructure([
                'errors' => [
                    'name',
                ],
            ]);
    }

    public function test_create_tag_with_invalid_slug_format_returns_validation_error(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'name' => 'Test Tag',
            'slug' => 'Invalid_Slug_With_Underscores',
        ];

        $response = $this->postJson('/api/admin/tags', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'validation_error')
            ->assertJsonStructure([
                'errors' => [
                    'slug',
                ],
            ]);
    }

    public function test_create_tag_with_duplicate_slug_returns_validation_error(): void
    {
        TagModel::factory()->create(['slug' => 'existing-tag']);

        $this->actingAs($this->adminUser);

        $payload = [
            'name' => 'Another Tag',
            'slug' => 'existing-tag',
        ];

        $response = $this->postJson('/api/admin/tags', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'validation_error')
            ->assertJsonStructure([
                'errors' => [
                    'slug',
                ],
            ]);
    }

    public function test_update_tag_with_valid_data_returns_success(): void
    {
        $tag = TagModel::factory()->create(['name' => 'Original Tag']);

        $this->actingAs($this->adminUser);

        $payload = [
            'name' => 'Updated Tag',
            'slug' => 'updated-tag',
        ];

        $response = $this->putJson("/api/admin/tags/{$tag->uuid}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Tag');

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'Updated Tag',
        ]);
    }

    public function test_delete_tag_with_valid_id_returns_success(): void
    {
        $tag = TagModel::factory()->create();

        $this->actingAs($this->adminUser);

        $response = $this->deleteJson("/api/admin/tags/{$tag->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('tags', [
            'id' => $tag->id,
        ]);
    }

    public function test_delete_tag_with_invalid_id_returns_not_found(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->deleteJson('/api/admin/tags/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'entity_not_found');
    }
}
