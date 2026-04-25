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

    public function testGetMediaFile_WithValidId_ReturnsMedia(): void
    {
        $media = MediaFileModel::factory()->create();

        $this->actingAs($this->adminUser);

        $response = $this->getJson("/api/admin/media/{$media->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $media->id)
            ->assertJsonPath('data.file_name', $media->file_name);
    }

    public function testGetMediaFile_WithInvalidId_ReturnsNotFound(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->getJson('/api/admin/media/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'entity_not_found');
    }

    public function testGetMediaFile_WithoutAuthentication_ReturnsUnauthorized(): void
    {
        $media = MediaFileModel::factory()->create();

        $response = $this->getJson("/api/admin/media/{$media->id}");

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'unauthenticated');
    }

    public function testUploadFile_WithValidImage_ReturnsSuccess(): void
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

        // Verify file was stored
        Storage::disk('public')->assertExists($file->hashName('uploads'));
    }

    public function testUploadFile_WithMissingFile_ReturnsValidationError(): void
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

    public function testUploadFile_WithInvalidMimeType_ReturnsValidationError(): void
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

    public function testUploadFile_WithLargeFile_ReturnsValidationError(): void
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

    public function testUpdateAltText_WithValidData_ReturnsSuccess(): void
    {
        $media = MediaFileModel::factory()->create();

        $this->actingAs($this->adminUser);

        $payload = [
            'alt_text' => 'Updated alt text',
        ];

        $response = $this->putJson("/api/admin/media/{$media->id}/alt-text", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.alt_text', 'Updated alt text');

        $this->assertDatabaseHas('media_files', [
            'id' => $media->id,
            'alt_text' => 'Updated alt text',
        ]);
    }

    public function testRenameFile_WithValidName_ReturnsSuccess(): void
    {
        $media = MediaFileModel::factory()->create([
            'file_name' => 'original-name.jpg',
        ]);

        $this->actingAs($this->adminUser);

        $payload = [
            'file_name' => 'new-name.jpg',
        ];

        $response = $this->putJson("/api/admin/media/{$media->id}/rename", $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.file_name', 'new-name.jpg');

        $this->assertDatabaseHas('media_files', [
            'id' => $media->id,
            'file_name' => 'new-name.jpg',
        ]);
    }

    public function testRenameFile_WithInvalidExtension_ReturnsValidationError(): void
    {
        $media = MediaFileModel::factory()->create();

        $this->actingAs($this->adminUser);

        $payload = [
            'file_name' => 'new-name.exe',
        ];

        $response = $this->putJson("/api/admin/media/{$media->id}/rename", $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'validation_error')
            ->assertJsonStructure([
                'errors' => [
                    'file_name',
                ],
            ]);
    }

    public function testDeleteFile_WithValidId_ReturnsSuccess(): void
    {
        $file = UploadedFile::fake()->image('test-image.jpg');
        Storage::disk('public')->putFileAs('uploads', $file, 'test-image.jpg');

        $media = MediaFileModel::factory()->create([
            'file_path' => 'uploads/test-image.jpg',
        ]);

        $this->actingAs($this->adminUser);

        $response = $this->deleteJson("/api/admin/media/{$media->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Verify file was deleted from storage
        Storage::disk('public')->assertMissing('uploads/test-image.jpg');

        $this->assertDatabaseMissing('media_files', [
            'id' => $media->id,
        ]);
    }

    public function testDeleteFile_WithInvalidId_ReturnsNotFound(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->deleteJson('/api/admin/media/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'entity_not_found');
    }

    public function testMediaEndpoint_IsRateLimited(): void
    {
        $this->actingAs($this->adminUser);

        // Make more than 120 requests (limit is 120 per minute)
        for ($i = 0; $i < 121; $i++) {
            $response = $this->getJson('/api/admin/media/'.($i + 1));
        }

        $response->assertStatus(429);
    }
}