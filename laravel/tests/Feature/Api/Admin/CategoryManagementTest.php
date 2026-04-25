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

    public function testGetAllCategories_WithAuthentication_ReturnsCategories(): void
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

    public function testGetAllCategories_WithoutAuthentication_ReturnsUnauthorized(): void
    {
        $response = $this->getJson('/api/admin/categories');

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'unauthenticated');
    }

    public function testGetCategoryById_WithValidId_ReturnsCategory(): void
    {
        $category = CategoryModel::factory()->create();

        $this->actingAs($this->adminUser);

        $response = $this->getJson("/api/admin/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.name', $category->name);
    }

    public function testGetCategoryById_WithInvalidId_ReturnsNotFound(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->getJson('/api/admin/categories/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'entity_not_found');
    }

    public function testCreateCategory_WithValidData_ReturnsCreated(): void
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

    public function testCreateCategory_WithMissingName_ReturnsValidationError(): void
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

    public function testCreateCategory_WithInvalidSlugFormat_ReturnsValidationError(): void
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

    public function testCreateCategory_WithDuplicateSlug_ReturnsValidationError(): void
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

    public function testUpdateCategory_WithValidData_ReturnsSuccess(): void
    {
        $category = CategoryModel::factory()->create([
            'name' => 'Original Name',
        ]);

        $this->actingAs($this->adminUser);

        $payload = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ];

        $response = $this->putJson("/api/admin/categories/{$category->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
        ]);
    }

    public function testUpdateCategory_WithDuplicateSlug_ReturnsValidationError(): void
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

    public function testDeleteCategory_WithValidId_ReturnsSuccess(): void
    {
        $category = CategoryModel::factory()->create();

        $this->actingAs($this->adminUser);

        $response = $this->deleteJson("/api/admin/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    public function testDeleteCategory_WithInvalidId_ReturnsNotFound(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->deleteJson('/api/admin/categories/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'entity_not_found');
    }

    public function testCategoriesEndpoint_IsRateLimited(): void
    {
        $this->actingAs($this->adminUser);

        // Make more than 120 requests (limit is 120 per minute)
        for ($i = 0; $i < 121; $i++) {
            $response = $this->getJson('/api/admin/categories');
        }

        $response->assertStatus(429);
    }
}