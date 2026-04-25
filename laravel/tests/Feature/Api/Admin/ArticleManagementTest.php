<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Admin;

use App\Domain\Article\ValueObjects\ArticleStatus;
use App\Domain\User\ValueObjects\UserRole;
use App\Infrastructure\Persistence\Eloquent\Models\ArticleModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Admin Article Management API Feature Tests.
 */
final class ArticleManagementTest extends TestCase
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

    public function testGetAllArticles_WithAuthentication_ReturnsArticles(): void
    {
        ArticleModel::factory()->count(3)->create();

        $this->actingAs($this->adminUser);

        $response = $this->getJson('/api/admin/articles');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'slug',
                        'status',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta',
            ]);
    }

    public function testGetAllArticles_WithoutAuthentication_ReturnsUnauthorized(): void
    {
        $response = $this->getJson('/api/admin/articles');

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'unauthenticated');
    }

    public function testGetArticleById_WithValidId_ReturnsArticle(): void
    {
        $article = ArticleModel::factory()->create();

        $this->actingAs($this->adminUser);

        $response = $this->getJson("/api/admin/articles/{$article->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $article->id)
            ->assertJsonPath('data.title', $article->title);
    }

    public function testGetArticleById_WithInvalidId_ReturnsNotFound(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->getJson('/api/admin/articles/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'entity_not_found');
    }

    public function testCreateArticle_WithValidData_ReturnsCreated(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'title' => 'Test Article',
            'content' => 'This is test content for the article.',
            'slug' => 'test-article',
            'status' => ArticleStatus::DRAFT->value,
        ];

        $response = $this->postJson('/api/admin/articles', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Test Article')
            ->assertJsonPath('data.slug', 'test-article');

        $this->assertDatabaseHas('articles', [
            'title' => 'Test Article',
            'slug' => 'test-article',
        ]);
    }

    public function testCreateArticle_WithMissingTitle_ReturnsValidationError(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'content' => 'This is test content for the article.',
        ];

        $response = $this->postJson('/api/admin/articles', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'validation_error')
            ->assertJsonStructure([
                'errors' => [
                    'title',
                ],
            ]);
    }

    public function testCreateArticle_WithInvalidSlugFormat_ReturnsValidationError(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'title' => 'Test Article',
            'content' => 'This is test content for the article.',
            'slug' => 'Invalid_Slug_With_Underscores',
        ];

        $response = $this->postJson('/api/admin/articles', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'validation_error')
            ->assertJsonStructure([
                'errors' => [
                    'slug',
                ],
            ]);
    }

    public function testUpdateArticle_WithValidData_ReturnsSuccess(): void
    {
        $article = ArticleModel::factory()->create([
            'title' => 'Original Title',
        ]);

        $this->actingAs($this->adminUser);

        $payload = [
            'title' => 'Updated Title',
            'content' => 'Updated content.',
        ];

        $response = $this->putJson("/api/admin/articles/{$article->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Updated Title');

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'title' => 'Updated Title',
        ]);
    }

    public function testDeleteArticle_WithValidId_ReturnsSuccess(): void
    {
        $article = ArticleModel::factory()->create();

        $this->actingAs($this->adminUser);

        $response = $this->deleteJson("/api/admin/articles/{$article->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('articles', [
            'id' => $article->id,
        ]);
    }

    public function testPublishArticle_WithDraftStatus_ReturnsSuccess(): void
    {
        $article = ArticleModel::factory()->create([
            'status' => ArticleStatus::DRAFT,
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->postJson("/api/admin/articles/{$article->id}/publish");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', ArticleStatus::PUBLISHED->value);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'status' => ArticleStatus::PUBLISHED,
        ]);
    }

    public function testArchiveArticle_WithPublishedStatus_ReturnsSuccess(): void
    {
        $article = ArticleModel::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->postJson("/api/admin/articles/{$article->id}/archive");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', ArticleStatus::ARCHIVED->value);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'status' => ArticleStatus::ARCHIVED,
        ]);
    }

    public function testSyncArticleTags_WithValidTags_ReturnsSuccess(): void
    {
        $article = ArticleModel::factory()->create();
        $tags = ['tag1', 'tag2', 'tag3'];

        $this->actingAs($this->adminUser);

        $payload = [
            'tags' => $tags,
        ];

        $response = $this->putJson("/api/admin/articles/{$article->id}/tags", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function testSyncArticleTags_WithMissingTags_ReturnsValidationError(): void
    {
        $article = ArticleModel::factory()->create();

        $this->actingAs($this->adminUser);

        $payload = [
            // Missing 'tags' key
        ];

        $response = $this->putJson("/api/admin/articles/{$article->id}/tags", $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'validation_error');
    }
}