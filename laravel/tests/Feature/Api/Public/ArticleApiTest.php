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

    public function test_get_published_articles_returns_successful_response(): void
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

    public function test_get_published_articles_only_returns_published_articles(): void
    {
        $response = $this->getJson('/api/articles');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data'); // Only published article

        $slugs = collect($response->json('data'))->pluck('slug');
        $this->assertContains('published-article', $slugs);
        $this->assertNotContains('draft-article', $slugs);
    }

    public function test_get_article_by_slug_with_valid_slug_returns_article(): void
    {
        $response = $this->getJson("/api/articles/{$this->publishedArticle->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'published-article')
            ->assertJsonPath('data.title', 'Published Article');
    }

    public function test_get_article_by_slug_with_draft_article_returns_not_found(): void
    {
        $response = $this->getJson("/api/articles/{$this->draftArticle->slug}");

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'article_not_found');
    }

    public function test_get_article_by_slug_with_invalid_slug_returns_not_found(): void
    {
        $response = $this->getJson('/api/articles/non-existent-article');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'article_not_found');
    }

    public function test_get_article_by_slug_with_invalid_slug_format_returns_not_found(): void
    {
        $response = $this->getJson('/api/articles/Invalid_Slug_With_Underscores');

        $response->assertStatus(404);
    }

    public function test_articles_endpoint_is_rate_limited(): void
    {
        // Make more than 60 requests (should be limited to 60 per minute)
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson('/api/articles');
        }

        // Last request should be rate limited
        $response->assertStatus(429);
    }
}
