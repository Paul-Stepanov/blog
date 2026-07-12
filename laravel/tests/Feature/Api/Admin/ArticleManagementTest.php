<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Admin;

use App\Domain\Article\ValueObjects\ArticleStatus;
use App\Domain\User\ValueObjects\UserRole;
use App\Infrastructure\Persistence\Eloquent\Models\ArticleModel;
use App\Infrastructure\Persistence\Eloquent\Models\TagModel;
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

    public function test_get_all_articles_with_authentication_returns_articles(): void
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

    public function test_get_all_articles_without_authentication_returns_unauthorized(): void
    {
        $response = $this->getJson('/api/admin/articles');

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'unauthenticated');
    }

    public function test_get_article_by_id_with_valid_id_returns_article(): void
    {
        $article = ArticleModel::factory()->create();

        $this->actingAs($this->adminUser);

        $response = $this->getJson("/api/admin/articles/{$article->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $article->uuid)
            ->assertJsonPath('data.title', $article->title);
    }

    public function test_get_article_by_id_with_invalid_id_returns_not_found(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->getJson('/api/admin/articles/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'entity_not_found');
    }

    public function test_create_article_with_valid_data_returns_created(): void
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

    public function test_create_article_with_missing_title_returns_validation_error(): void
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

    public function test_create_article_with_invalid_slug_format_returns_validation_error(): void
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

    public function test_update_article_with_valid_data_returns_success(): void
    {
        $article = ArticleModel::factory()->create([
            'title' => 'Original Title',
        ]);

        $this->actingAs($this->adminUser);

        $payload = [
            'title' => 'Updated Title',
            'content' => 'Updated content.',
        ];

        $response = $this->putJson("/api/admin/articles/{$article->uuid}", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Updated Title');

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_delete_article_with_valid_id_returns_success(): void
    {
        $article = ArticleModel::factory()->create();

        $this->actingAs($this->adminUser);

        $response = $this->deleteJson("/api/admin/articles/{$article->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('articles', [
            'id' => $article->id,
        ]);
    }

    public function test_publish_article_with_draft_status_returns_success(): void
    {
        $article = ArticleModel::factory()->create([
            'status' => ArticleStatus::DRAFT,
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->postJson("/api/admin/articles/{$article->uuid}/publish");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', ArticleStatus::PUBLISHED->value);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'status' => ArticleStatus::PUBLISHED,
        ]);
    }

    public function test_archive_article_with_published_status_returns_success(): void
    {
        $article = ArticleModel::factory()->create([
            'status' => ArticleStatus::PUBLISHED,
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->postJson("/api/admin/articles/{$article->uuid}/archive");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', ArticleStatus::ARCHIVED->value);

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'status' => ArticleStatus::ARCHIVED,
        ]);
    }

    public function test_sync_article_tags_with_valid_tags_returns_success(): void
    {
        $article = ArticleModel::factory()->create();
        $tags = TagModel::factory()->count(3)->create();

        $this->actingAs($this->adminUser);

        $payload = [
            'tags' => $tags->pluck('uuid')->all(),
        ];

        $response = $this->putJson("/api/admin/articles/{$article->uuid}/tags", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_sync_article_tags_with_missing_tags_returns_validation_error(): void
    {
        $article = ArticleModel::factory()->create();

        $this->actingAs($this->adminUser);

        $payload = [
            // Missing 'tags' key
        ];

        $response = $this->putJson("/api/admin/articles/{$article->uuid}/tags", $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'validation_error');
    }
}
