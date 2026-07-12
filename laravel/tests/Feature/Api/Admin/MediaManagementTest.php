<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Admin;

use App\Domain\User\ValueObjects\UserRole;
use App\Infrastructure\Persistence\Eloquent\Models\MediaFileModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Admin Media Management API Feature Tests.
 */
final class MediaManagementTest extends TestCase
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

        // Configure fake storage for testing
        Storage::fake('public');
    }

    public function test_get_media_file_with_valid_id_returns_media(): void
    {
        $media = MediaFileModel::factory()->create();

        $this->actingAs($this->adminUser);

        $response = $this->getJson("/api/admin/media/{$media->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $media->uuid)
            ->assertJsonPath('data.file_name', $media->filename);
    }

    public function test_get_media_file_with_invalid_id_returns_not_found(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->getJson('/api/admin/media/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'entity_not_found');
    }

    public function test_get_media_file_without_authentication_returns_unauthorized(): void
    {
        $media = MediaFileModel::factory()->create();

        $response = $this->getJson("/api/admin/media/{$media->uuid}");

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'unauthenticated');
    }

    public function test_upload_file_with_valid_image_returns_success(): void
    {
        $this->actingAs($this->adminUser);

        $file = UploadedFile::fake()->image('test-image.jpg', 800, 600);

        $response = $this->postJson('/api/admin/media/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'file_name',
                    'file_path',
                    'mime_type',
                    'file_size',
                    'width',
                    'height',
                ],
            ]);

        $storedPath = $response->json('data.file_path');
        $relativeOnPublicDisk = preg_replace('#^public/#', '', (string) $storedPath);

        Storage::disk('public')->assertExists($relativeOnPublicDisk);
    }

    public function test_upload_file_with_missing_file_returns_validation_error(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->postJson('/api/admin/media/upload', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'validation_error')
            ->assertJsonStructure([
                'errors' => [
                    'file',
                ],
            ]);
    }

    public function test_upload_file_with_invalid_mime_type_returns_validation_error(): void
    {
        $this->actingAs($this->adminUser);

        $file = UploadedFile::fake()->create('document.pdf', 1000);

        $response = $this->postJson('/api/admin/media/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'validation_error')
            ->assertJsonStructure([
                'errors' => [
                    'file',
                ],
            ]);
    }

    public function test_upload_file_with_large_file_returns_validation_error(): void
    {
        $this->actingAs($this->adminUser);

        // Create file larger than 5MB (max upload size)
        $file = UploadedFile::fake()->create('large-image.jpg', 6000); // 6MB

        $response = $this->postJson('/api/admin/media/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'validation_error');
    }

    public function test_update_alt_text_with_valid_data_returns_success(): void
    {
        $media = MediaFileModel::factory()->create();

        $this->actingAs($this->adminUser);

        $payload = [
            'alt_text' => 'Updated alt text',
        ];

        $response = $this->putJson("/api/admin/media/{$media->uuid}/alt-text", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.alt_text', 'Updated alt text');

        $this->assertDatabaseHas('media_files', [
            'id' => $media->id,
            'alt_text' => 'Updated alt text',
        ]);
    }

    public function test_rename_file_with_valid_name_returns_success(): void
    {
        $media = MediaFileModel::factory()->create([
            'filename' => 'original-name.jpg',
        ]);

        $this->actingAs($this->adminUser);

        $payload = [
            'file_name' => 'new-name.jpg',
        ];

        $response = $this->putJson("/api/admin/media/{$media->uuid}/rename", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.file_name', 'new-name.jpg');

        $this->assertDatabaseHas('media_files', [
            'id' => $media->id,
            'filename' => 'new-name.jpg',
        ]);
    }

    public function test_rename_file_with_invalid_extension_returns_validation_error(): void
    {
        $media = MediaFileModel::factory()->create();

        $this->actingAs($this->adminUser);

        $payload = [
            'file_name' => 'new-name.exe',
        ];

        $response = $this->putJson("/api/admin/media/{$media->uuid}/rename", $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'validation_error')
            ->assertJsonStructure([
                'errors' => [
                    'file_name',
                ],
            ]);
    }

    public function test_delete_file_with_valid_id_returns_success(): void
    {
        $file = UploadedFile::fake()->image('test-image.jpg');
        Storage::disk('public')->putFileAs('uploads', $file, 'test-image.jpg');

        $media = MediaFileModel::factory()->create([
            'path' => 'public/uploads/test-image.jpg',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->deleteJson("/api/admin/media/{$media->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verify file was deleted from storage
        Storage::disk('public')->assertMissing('uploads/test-image.jpg');

        $this->assertDatabaseMissing('media_files', [
            'id' => $media->id,
        ]);
    }

    public function test_delete_file_with_invalid_id_returns_not_found(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->deleteJson('/api/admin/media/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'entity_not_found');
    }

    public function test_media_endpoint_is_rate_limited(): void
    {
        $this->actingAs($this->adminUser);

        // Make more than 120 requests (limit is 120 per minute)
        for ($i = 0; $i < 121; $i++) {
            $response = $this->getJson('/api/admin/media/'.($i + 1));
        }

        $response->assertStatus(429);
    }
}
