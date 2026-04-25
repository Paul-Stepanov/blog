<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Public;

use App\Domain\Article\ValueObjects\ArticleStatus;
use App\Infrastructure\Persistence\Eloquent\Models\ArticleModel;
use App\Infrastructure\Persistence\Eloquent\Models\CategoryModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public Category API Feature Tests.
 */
final class CategoryApiTest extends TestCase
{
    use RefreshDatabase;

    private CategoryModel $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = CategoryModel::factory()->create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'A test category',
        ]);
    }

    public function testGetCategories_ReturnsSuccessfulResponse(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
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

    public function testGetCategories_ReturnsCategories(): void
    {
        // Create categories with published articles
        $categories = CategoryModel::factory()->count(3)->create();

        foreach ($categories as $category) {
            ArticleModel::factory()->create([
                'title' => 'Article for ' . $category->name,
                'slug' => 'article-for-' . $category->slug,
                'status' => ArticleStatus::PUBLISHED,
                'published_at' => now(),
                'category_uuid' => $category->uuid,
            ]);
        }

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function testGetCategoryBySlug_WithValidSlug_ReturnsCategory(): void
    {
        $response = $this->getJson("/api/categories/{$this->category->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'test-category')
            ->assertJsonPath('data.name', 'Test Category');
    }

    public function testGetCategoryBySlug_WithInvalidSlug_ReturnsNotFound(): void
    {
        $response = $this->getJson('/api/categories/non-existent-category');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'category_not_found');
    }

    public function testGetCategoryBySlug_WithInvalidSlugFormat_ReturnsNotFound(): void
    {
        $response = $this->getJson('/api/categories/Invalid_Slug_With_Underscores');

        $response->assertStatus(404);
    }

    public function testCategoriesEndpoint_IsRateLimited(): void
    {
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson('/api/categories');
        }

        $response->assertStatus(429);
    }
}