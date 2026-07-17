<?php

declare(strict_types=1);

namespace Tests\Unit\Application\User;

use App\Application\User\Commands\UpdateUserCommand;
use App\Application\User\Services\UserService;
use App\Domain\Shared\Exceptions\ValidationException;
use App\Domain\User\ValueObjects\UserRole;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * User role invariants at the Application layer.
 */
final class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_demoting_last_admin_throws_validation_exception(): void
    {
        // Only one admin exists; demoting them (by a different actor) is forbidden.
        $admin = UserModel::factory()->admin()->create();
        $service = app(UserService::class);

        $command = new UpdateUserCommand(
            userId: $admin->uuid,
            actorId: '00000000-0000-0000-0000-000000000000', // a different actor
            role: UserRole::EDITOR,
        );

        $this->expectException(ValidationException::class);
        $service->updateUser($command);
    }

    public function test_self_role_change_throws_validation_exception(): void
    {
        $admin = UserModel::factory()->admin()->create();
        $service = app(UserService::class);

        $command = new UpdateUserCommand(
            userId: $admin->uuid,
            actorId: $admin->uuid, // actor == target
            role: UserRole::EDITOR,
        );

        $this->expectException(ValidationException::class);
        $service->updateUser($command);
    }

    public function test_demoting_admin_allowed_when_multiple_admins(): void
    {
        UserModel::factory()->admin()->create(); // admin #1
        $target = UserModel::factory()->admin()->create(); // admin #2

        $service = app(UserService::class);

        $command = new UpdateUserCommand(
            userId: $target->uuid,
            actorId: '00000000-0000-0000-0000-000000000000',
            role: UserRole::EDITOR,
        );

        $result = $service->updateUser($command);

        $this->assertNotNull($result);
        $this->assertSame(UserRole::EDITOR->value, $result->role);
    }
}
