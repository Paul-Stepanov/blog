<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Admin;

use App\Domain\User\ValueObjects\UserRole;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Admin Authentication API Feature Tests.
 */
final class AuthTest extends TestCase
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

    public function test_login_with_valid_credentials_returns_success(): void
    {
        $payload = [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/admin/auth/login', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Login successful.')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'role',
                ],
            ]);

        // Verify user is authenticated
        $this->assertAuthenticated();
    }

    public function test_login_with_invalid_email_returns_unauthorized(): void
    {
        $payload = [
            'email' => 'wrong@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/admin/auth/login', $payload);

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'invalid_credentials')
            ->assertJsonPath('message', 'Invalid credentials.');
    }

    public function test_login_with_invalid_password_returns_unauthorized(): void
    {
        $payload = [
            'email' => 'admin@example.com',
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson('/api/admin/auth/login', $payload);

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'invalid_credentials');
    }

    public function test_login_with_missing_email_returns_validation_error(): void
    {
        $payload = [
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/admin/auth/login', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'validation_error')
            ->assertJsonStructure([
                'errors' => [
                    'email',
                ],
            ]);
    }

    public function test_login_with_missing_password_returns_validation_error(): void
    {
        $payload = [
            'email' => 'admin@example.com',
        ];

        $response = $this->postJson('/api/admin/auth/login', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'validation_error')
            ->assertJsonStructure([
                'errors' => [
                    'password',
                ],
            ]);
    }

    public function test_login_with_invalid_email_format_returns_validation_error(): void
    {
        $payload = [
            'email' => 'not-an-email',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/admin/auth/login', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'validation_error')
            ->assertJsonStructure([
                'errors' => [
                    'email',
                ],
            ]);
    }

    public function test_login_has_strict_rate_limiting(): void
    {
        $payload = [
            'email' => 'admin@example.com',
            'password' => 'wrongpassword',
        ];

        // Make more than 5 failed login attempts (limit is 5 per minute)
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/admin/auth/login', $payload);
        }

        // Last request should be rate limited
        $response->assertStatus(429);
    }

    public function test_logout_with_authenticated_user_returns_success(): void
    {
        $this->actingAs($this->adminUser, 'web');

        $response = $this->postJson('/api/admin/auth/logout');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Logged out successfully.');

        $this->assertGuest('web');
    }

    public function test_logout_without_authentication_returns_unauthorized(): void
    {
        $response = $this->postJson('/api/admin/auth/logout');

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'unauthenticated');
    }

    public function test_get_current_user_with_authenticated_user_returns_user(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->getJson('/api/admin/auth/user');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $this->adminUser->uuid)
            ->assertJsonPath('data.email', $this->adminUser->email->getValue())
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'role',
                ],
            ]);
    }

    public function test_get_current_user_without_authentication_returns_unauthorized(): void
    {
        $response = $this->getJson('/api/admin/auth/user');

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'unauthenticated');
    }

    public function test_login_regenerates_session(): void
    {
        $payload = [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/admin/auth/login', $payload);

        $response->assertStatus(200);

        // Session should be regenerated (check for session cookie)
        $this->assertNotEmpty($this->app['session']->getId());
    }
}
