<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Public;

use App\Infrastructure\Persistence\Eloquent\Models\SiteSettingModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public Settings API Feature Tests.
 */
final class SettingsApiTest extends TestCase
{
    use RefreshDatabase;

    private SiteSettingModel $publicSetting;

    protected function setUp(): void
    {
        parent::setUp();

        // Create public settings
        SiteSettingModel::factory()->create([
            'uuid' => fake()->uuid(),
            'key' => 'site.title',
            'value' => 'My Blog',
            'type' => 'string',
        ]);

        SiteSettingModel::factory()->create([
            'uuid' => fake()->uuid(),
            'key' => 'site.description',
            'value' => 'A blog about technology',
            'type' => 'string',
        ]);

        // Create private settings (should not be exposed in public API)
        SiteSettingModel::factory()->create([
            'uuid' => fake()->uuid(),
            'key' => 'admin.email',
            'value' => 'admin@example.com',
            'type' => 'string',
        ]);
    }

    public function test_get_public_settings_returns_successful_response(): void
    {
        $response = $this->getJson('/api/settings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data', // Key-value pairs of public settings
            ]);
    }

    public function test_get_public_settings_only_returns_public_groups(): void
    {
        $response = $this->getJson('/api/settings');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertArrayHasKey('site.title', $data);
        $this->assertArrayHasKey('site.description', $data);
        $this->assertArrayNotHasKey('admin.email', $data);
    }

    public function test_get_setting_by_key_with_valid_key_returns_setting(): void
    {
        $response = $this->getJson('/api/settings/site.title');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.key', 'site.title')
            ->assertJsonPath('data.value', 'My Blog');
    }

    public function test_get_setting_by_key_with_private_group_returns_not_found(): void
    {
        $response = $this->getJson('/api/settings/admin.email');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'setting_not_public');
    }

    public function test_get_setting_by_key_with_invalid_key_returns_not_found(): void
    {
        $response = $this->getJson('/api/settings/nonexistent.key');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'setting_not_public');
    }

    public function test_get_setting_by_key_with_invalid_key_format_returns_not_found(): void
    {
        $response = $this->getJson('/api/settings/invalid-key-format');

        $response->assertStatus(404);
    }

    public function test_settings_endpoint_is_rate_limited(): void
    {
        for ($i = 0; $i < 61; $i++) {
            $response = $this->getJson('/api/settings');
        }

        $response->assertStatus(429);
    }
}
