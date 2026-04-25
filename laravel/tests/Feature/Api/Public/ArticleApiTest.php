<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Public;

use App\Domain\Article\ValueObjects\ArticleStatus;
use App\Infrastructure\Persistence\Eloquent\Models\ArticleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public Article API Feature Tests.
 */
final class ArticleApiTest extends TestCase
{
    use RefreshDatabase;

    private ArticleModel $publishedArticle;
    private ArticleModel $draftArticle;

    protected function setUp(): void
    {
        parent::setUp();

        // Create published article
        $this->publishedArticle = ArticleModel::factory()->create([
            'title' => 'Published Article',
            'slug' => 'published-article',
            'status' => ArticleStatus::PUBLISHED,
            'published_at' => now(),
        ]);

        // Create draft article (should not be visible in public API)
        $this->draftArticle = ArticleModel::factory()->create([
            'title' => 'Draft Article',
            'slug' => 'draft-article',
            'status' => ArticleStatus::DRAFT,
            'published_at' => null,
        ]);
    }

    public function testGetPublishedArticles_ReturnsSuccessfulResponse(): void
    {
        $response = $this->getJson('/api/articles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'slug',
                        'excerpt',
                        'status',
                        'category_id',
                        'published_at',
                        'reading_time',
                    ],
                ],
                'meta' => [
                    'pagination' => [
                        'total',
                        'count',
                        'per_page',
                        'current_page',
                        'total_pages',
                        'has_more',
                    ],
                ],
            ]);
    }

    public function testGetPublishedArticles_OnlyReturnsPublishedArticles(): void
    {
        $response = $this->getJson('/api/articles');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data'); // Only published article

        $slugs = collect($response->json('data'))->pluck('slug');
        $this->assertContains('published-article', $slugs);
        $this->assertNotContains('draft-article', $slugs);
    }

    public function testGetArticleBySlug_WithValidSlug_ReturnsArticle(): void
    {
        $response = $this->getJson("/api/articles/{$this->publishedArticle->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'published-article')
            ->assertJsonPath('data.title', 'Published Article');
    }

    public function testGetArticleBySlug_WithDraftArticle_ReturnsNotFound(): void
    {
        $response = $this->getJson("/api/articles/{$this->draftArticle->slug}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'article_not_found');
    }

    public function testGetArticleBySlug_WithInvalidSlug_ReturnsNotFound(): void
    {
        $response = $this->getJson('/api/articles/non-existent-article');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'article_not_found');
    }

    public function testGetArticleBySlug_WithInvalidSlugFormat_ReturnsNotFound(): void
    {
        $response = $this->getJson('/api/articles/Invalid_Slug_With_Underscores');

        $response->assertStatus(404);
    }

    public function testArticlesEndpoint_IsRateLimited(): void
    {
        // Make more than 60 requests (should be limited to 60 per minute)
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson('/api/articles');
        }

        // Last request should be rate limited
        $response->assertStatus(429);
    }
}