<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Admin;

use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RBAC defense-in-depth tests.
 *
 * Verifies the 'admin' middleware denies non-admin roles (editor, author)
 * across the admin API surface, while admins remain authorized.
 */
final class RbacAccessTest extends TestCase
{
    use RefreshDatabase;

    private UserModel $admin;

    private UserModel $editor;

    private UserModel $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserModel::factory()->admin()->create();
        $this->editor = UserModel::factory()->editor()->create();
        $this->author = UserModel::factory()->author()->create();
    }

    public function test_editor_is_forbidden_on_admin_endpoints(): void
    {
        $this->actingAs($this->editor);

        $this->getJson('/api/admin/articles')->assertForbidden();
        $this->getJson('/api/admin/users')->assertForbidden();
        $this->getJson('/api/admin/settings')->assertForbidden();
        $this->getJson('/api/admin/auth/user')->assertForbidden();
    }

    public function test_author_is_forbidden_on_admin_endpoints(): void
    {
        $this->actingAs($this->author);

        $this->getJson('/api/admin/articles')->assertForbidden();
        $this->getJson('/api/admin/users')->assertForbidden();
        $this->getJson('/api/admin/categories')->assertForbidden();
        $this->getJson('/api/admin/tags')->assertForbidden();
    }

    public function test_admin_is_authorized_on_admin_endpoints(): void
    {
        $this->actingAs($this->admin);

        $this->getJson('/api/admin/users')->assertOk();
        $this->getJson('/api/admin/articles')->assertOk();
        $this->getJson('/api/admin/auth/user')->assertOk();
    }

    public function test_unauthenticated_is_unauthorized_not_forbidden(): void
    {
        // auth:sanctum rejects before the 'admin' middleware runs → 401, not 403
        $this->getJson('/api/admin/users')->assertUnauthorized();
        $this->getJson('/api/admin/auth/user')->assertUnauthorized();
    }
}
