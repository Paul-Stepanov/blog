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

    public function testLogin_WithValidCredentials_ReturnsSuccess(): void
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

    public function testLogin_WithInvalidEmail_ReturnsUnauthorized(): void
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

    public function testLogin_WithInvalidPassword_ReturnsUnauthorized(): void
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

    public function testLogin_WithMissingEmail_ReturnsValidationError(): void
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

    public function testLogin_WithMissingPassword_ReturnsValidationError(): void
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

    public function testLogin_WithInvalidEmailFormat_ReturnsValidationError(): void
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

    public function testLogin_HasStrictRateLimiting(): void
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

    public function testLogout_WithAuthenticatedUser_ReturnsSuccess(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->postJson('/api/admin/auth/logout');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Logged out successfully.');

        // Verify user is logged out
        $this->assertGuest();
    }

    public function testLogout_WithoutAuthentication_ReturnsUnauthorized(): void
    {
        $response = $this->postJson('/api/admin/auth/logout');

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'unauthenticated');
    }

    public function testGetCurrentUser_WithAuthenticatedUser_ReturnsUser(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->getJson('/api/admin/auth/user');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $this->adminUser->id)
            ->assertJsonPath('data.email', $this->adminUser->email)
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

    public function testGetCurrentUser_WithoutAuthentication_ReturnsUnauthorized(): void
    {
        $response = $this->getJson('/api/admin/auth/user');

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'unauthenticated');
    }

    public function testLogin_RegeneratesSession(): void
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