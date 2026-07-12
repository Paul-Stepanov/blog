<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Admin;

use App\Domain\User\ValueObjects\UserRole;
use App\Infrastructure\Persistence\Eloquent\Models\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Admin Category Management API Feature Tests.
 */
final class CategoryManagementTest extends TestCase
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

    public function test_get_all_categories_with_authentication_returns_categories(): void
    {
        CategoryModel::factory()->count(3)->create();

        $this->actingAs($this->adminUser);

        $response = $this->getJson('/api/admin/categories');

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
                        'description',
                    ],
                ],
            ]);
    }

    public function test_get_all_categories_without_authentication_returns_unauthorized(): void
    {
        $response = $this->getJson('/api/admin/categories');

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'unauthenticated');
    }

    public function test_get_category_by_id_with_valid_id_returns_category(): void
    {
        $category = CategoryModel::factory()->create();

        $this->actingAs($this->adminUser);

        $response = $this->getJson("/api/admin/categories/{$category->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $category->uuid)
            ->assertJsonPath('data.name', $category->name);
    }

    public function test_get_category_by_id_with_invalid_id_returns_not_found(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->getJson('/api/admin/categories/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'entity_not_found');
    }

    public function test_create_category_with_valid_data_returns_created(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'A test category',
        ];

        $response = $this->postJson('/api/admin/categories', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Test Category')
            ->assertJsonPath('data.slug', 'test-category');

        $this->assertDatabaseHas('categories', [
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);
    }

    public function test_create_category_with_missing_name_returns_validation_error(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'description' => 'A test category',
        ];

        $response = $this->postJson('/api/admin/categories', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'validation_error')
            ->assertJsonStructure([
                'errors' => [
                    'name',
                ],
            ]);
    }

    public function test_create_category_with_invalid_slug_format_returns_validation_error(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'name' => 'Test Category',
            'slug' => 'Invalid_Slug_With_Underscores',
        ];

        $response = $this->postJson('/api/admin/categories', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'validation_error')
            ->assertJsonStructure([
                'errors' => [
                    'slug',
                ],
            ]);
    }

    public function test_create_category_with_duplicate_slug_returns_validation_error(): void
    {
        CategoryModel::factory()->create([
            'slug' => 'test-category',
        ]);

        $this->actingAs($this->adminUser);

        $payload = [
            'name' => 'Another Category',
            'slug' => 'test-category',
        ];

        $response = $this->postJson('/api/admin/categories', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'validation_error')
            ->assertJsonStructure([
                'errors' => [
                    'slug',
                ],
            ]);
    }

    public function test_update_category_with_valid_data_returns_success(): void
    {
        $category = CategoryModel::factory()->create([
            'name' => 'Original Name',
        ]);

        $this->actingAs($this->adminUser);

        $payload = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ];

        $response = $this->putJson("/api/admin/categories/{$category->uuid}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_update_category_with_duplicate_slug_returns_validation_error(): void
    {
        $category1 = CategoryModel::factory()->create(['slug' => 'category-1']);
        $category2 = CategoryModel::factory()->create(['slug' => 'category-2']);

        $this->actingAs($this->adminUser);

        $payload = [
            'name' => 'Updated Name',
            'slug' => 'category-2',
        ];

        $response = $this->putJson("/api/admin/categories/{$category1->id}", $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'validation_error')
            ->assertJsonStructure([
                'errors' => [
                    'slug',
                ],
            ]);
    }

    public function test_delete_category_with_valid_id_returns_success(): void
    {
        $category = CategoryModel::factory()->create();

        $this->actingAs($this->adminUser);

        $response = $this->deleteJson("/api/admin/categories/{$category->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_delete_category_with_invalid_id_returns_not_found(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->deleteJson('/api/admin/categories/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'entity_not_found');
    }

    public function test_categories_endpoint_is_rate_limited(): void
    {
        $this->actingAs($this->adminUser);

        // Make more than 120 requests (limit is 120 per minute)
        for ($i = 0; $i < 121; $i++) {
            $response = $this->getJson('/api/admin/categories');
        }

        $response->assertStatus(429);
    }
}
