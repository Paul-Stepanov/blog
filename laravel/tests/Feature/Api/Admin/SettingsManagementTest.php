<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Admin;

use App\Domain\User\ValueObjects\UserRole;
use App\Infrastructure\Persistence\Eloquent\Models\SiteSettingModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Admin Settings Management API Feature Tests.
 */
final class SettingsManagementTest extends TestCase
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

    public function test_get_all_settings_with_authentication_returns_settings(): void
    {
        SiteSettingModel::factory()->count(3)->create();

        $this->actingAs($this->adminUser);

        $response = $this->getJson('/api/admin/settings');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'key',
                        'value',
                        'type',
                    ],
                ],
            ]);
    }

    public function test_get_all_settings_without_authentication_returns_unauthorized(): void
    {
        $response = $this->getJson('/api/admin/settings');

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'unauthenticated');
    }

    public function test_get_setting_by_group_returns_group_settings(): void
    {
        SiteSettingModel::factory()->create(['key' => 'site.title', 'value' => 'My Blog']);
        SiteSettingModel::factory()->create(['key' => 'site.tagline', 'value' => 'Just a blog']);
        SiteSettingModel::factory()->create(['key' => 'blog.per_page', 'value' => '10']);

        $this->actingAs($this->adminUser);

        $response = $this->getJson('/api/admin/settings/group/site');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_get_setting_by_key_with_existing_key_returns_setting(): void
    {
        SiteSettingModel::factory()->create(['key' => 'site.title', 'value' => 'My Blog']);

        $this->actingAs($this->adminUser);

        $response = $this->getJson('/api/admin/settings/site.title');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.key', 'site.title')
            ->assertJsonPath('data.value', 'My Blog');
    }

    public function test_get_setting_by_key_with_unknown_key_returns_not_found(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->getJson('/api/admin/settings/site.nonexistent');

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'setting_not_found');
    }

    public function test_set_setting_creates_new_setting(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->putJson('/api/admin/settings/site.tagline', [
            'value' => 'Fresh content daily',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.value', 'Fresh content daily');

        $this->assertDatabaseHas('site_settings', [
            'key' => 'site.tagline',
            'value' => 'Fresh content daily',
        ]);
    }

    public function test_set_setting_updates_existing_setting(): void
    {
        SiteSettingModel::factory()->create(['key' => 'site.title', 'value' => 'Old Title']);

        $this->actingAs($this->adminUser);

        $response = $this->putJson('/api/admin/settings/site.title', [
            'value' => 'New Title',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.value', 'New Title');

        $this->assertDatabaseHas('site_settings', [
            'key' => 'site.title',
            'value' => 'New Title',
        ]);
    }

    public function test_set_setting_with_missing_value_returns_validation_error(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->putJson('/api/admin/settings/site.title', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'validation_error');
    }

    public function test_set_many_settings_returns_success(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'settings' => [
                'site.title' => 'Batch Title',
                'site.tagline' => 'Batch Tagline',
            ],
        ];

        $response = $this->postJson('/api/admin/settings/batch', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('site_settings', ['key' => 'site.title', 'value' => 'Batch Title']);
        $this->assertDatabaseHas('site_settings', ['key' => 'site.tagline', 'value' => 'Batch Tagline']);
    }

    public function test_delete_setting_returns_success(): void
    {
        SiteSettingModel::factory()->create(['key' => 'site.legacy', 'value' => 'bye']);

        $this->actingAs($this->adminUser);

        $response = $this->deleteJson('/api/admin/settings/site.legacy');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('site_settings', ['key' => 'site.legacy']);
    }
}
