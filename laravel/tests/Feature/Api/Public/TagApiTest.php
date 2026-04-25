<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Public;

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

    public function testGetAllTags_ReturnsSuccessfulResponse(): void
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

    public function testGetAllTags_ReturnsTags(): void
    {
        TagModel::factory()->count(3)->create();

        $response = $this->getJson('/api/tags');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(4, 'data'); // 3 created + 1 from setUp
    }

    public function testGetPopularTags_ReturnsSuccessfulResponse(): void
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

    public function testGetTagBySlug_WithValidSlug_ReturnsTag(): void
    {
        $response = $this->getJson("/api/tags/{$this->tag->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', 'test-tag')
            ->assertJsonPath('data.name', 'Test Tag');
    }

    public function testGetTagBySlug_WithInvalidSlug_ReturnsNotFound(): void
    {
        $response = $this->getJson('/api/tags/non-existent-tag');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'tag_not_found');
    }

    public function testGetTagBySlug_WithInvalidSlugFormat_ReturnsNotFound(): void
    {
        $response = $this->getJson('/api/tags/Invalid_Slug_With_Underscores');

        $response->assertStatus(404);
    }

    public function testTagsEndpoint_IsRateLimited(): void
    {
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson('/api/tags');
        }

        $response->assertStatus(429);
    }
}