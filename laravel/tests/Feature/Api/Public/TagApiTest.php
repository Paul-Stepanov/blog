<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Public;

use App\Domain\Article\ValueObjects\ArticleStatus;
use App\Infrastructure\Persistence\Eloquent\Models\ArticleModel;
use App\Infrastructure\Persistence\Eloquent\Models\TagModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public Tag API Feature Tests.
 */
final class TagApiTest extends TestCase
{
    use RefreshDatabase;

    private TagModel $tag;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tag = TagModel::factory()->create([
            'name' => 'Test Tag',
            'slug' => 'test-tag',
        ]);
    }

    public function test_get_all_tags_returns_successful_response(): void
    {
        $response = $this->getJson('/api/tags');

        $response->assertStatus(200)
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

    public function test_get_all_tags_returns_tags(): void
    {
        TagModel::factory()->count(3)->create();

        $response = $this->getJson('/api/tags');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(4, 'data'); // 3 created + 1 from setUp
    }

    public function test_get_popular_tags_returns_successful_response(): void
    {
        $response = $this->getJson('/api/tags/popular');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'articles_count',
                    ],
                ],
            ]);
    }

    public function test_get_popular_tags_returns_tags_with_article_count(): void
    {
        // Single-query path: popular tags carry articles_count from the same query.
        $article = ArticleModel::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
            'published_at' => now(),
        ]);
        $article->tags()->sync([$this->tag->id]);

        $response = $this->getJson('/api/tags/popular');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $popular = collect($response->json('data'))->firstWhere('slug', 'test-tag');
        $this->assertNotNull($popular, 'popular tag missing');
        $this->assertSame(1, $popular['articles_count']);
    }

    public function test_get_tag_by_slug_with_valid_slug_returns_tag(): void
    {
        $response = $this->getJson("/api/tags/{$this->tag->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'test-tag')
            ->assertJsonPath('data.name', 'Test Tag');
    }

    public function test_get_tag_by_slug_with_invalid_slug_returns_not_found(): void
    {
        $response = $this->getJson('/api/tags/non-existent-tag');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'tag_not_found');
    }

    public function test_get_tag_by_slug_with_invalid_slug_format_returns_not_found(): void
    {
        $response = $this->getJson('/api/tags/Invalid_Slug_With_Underscores');

        $response->assertStatus(404);
    }

    public function test_tags_endpoint_is_rate_limited(): void
    {
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson('/api/tags');
        }

        $response->assertStatus(429);
    }
}
