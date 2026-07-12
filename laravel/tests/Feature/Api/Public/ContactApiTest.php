<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Public;

use App\Infrastructure\Persistence\Eloquent\Models\ContactMessageModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public Contact API Feature Tests.
 */
final class ContactApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_contact_message_with_valid_data_returns_success(): void
    {
        $payload = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Test Subject',
            'message' => 'This is a test message with enough content.',
        ];

        $response = $this->postJson('/api/contact', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Message sent successfully.');

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Test Subject',
        ]);
    }

    public function test_submit_contact_message_with_missing_name_returns_validation_error(): void
    {
        $payload = [
            'email' => 'john@example.com',
            'subject' => 'Test Subject',
            'message' => 'This is a test message with enough content.',
        ];

        $response = $this->postJson('/api/contact', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'validation_error')
            ->assertJsonStructure([
                'success',
                'error',
                'message',
                'errors' => [
                    'name',
                ],
            ]);
    }

    public function test_submit_contact_message_with_invalid_email_returns_validation_error(): void
    {
        $payload = [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'subject' => 'Test Subject',
            'message' => 'This is a test message with enough content.',
        ];

        $response = $this->postJson('/api/contact', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'validation_error')
            ->assertJsonStructure([
                'errors' => [
                    'email',
                ],
            ]);
    }

    public function test_submit_contact_message_with_short_message_returns_validation_error(): void
    {
        $payload = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Test Subject',
            'message' => 'Short',
        ];

        $response = $this->postJson('/api/contact', $payload);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'validation_error')
            ->assertJsonStructure([
                'errors' => [
                    'message',
                ],
            ]);
    }

    public function test_submit_contact_message_with_empty_payload_returns_validation_error(): void
    {
        $response = $this->postJson('/api/contact', []);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'validation_error')
            ->assertJsonStructure([
                'errors' => [
                    'name',
                    'email',
                    'subject',
                    'message',
                ],
            ]);
    }

    public function test_contact_endpoint_has_strict_rate_limiting(): void
    {
        $payload = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Test Subject',
            'message' => 'This is a test message with enough content.',
        ];

        // Make more than 3 requests (limit is 3 per hour)
        for ($i = 0; $i < 4; $i++) {
            $response = $this->postJson('/api/contact', $payload);
        }

        // Last request should be rate limited
        $response->assertStatus(429);
    }

    public function test_submit_contact_message_saves_ip_address(): void
    {
        $payload = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Test Subject',
            'message' => 'This is a test message with enough content.',
        ];

        $response = $this->postJson('/api/contact', $payload);

        $response->assertStatus(201);

        $message = ContactMessageModel::first();
        $this->assertNotNull($message->ip_address);
    }
}
