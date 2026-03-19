<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Mappers;

use App\Domain\Contact\ValueObjects\Email;
use App\Domain\User\Entities\User;
use App\Domain\User\ValueObjects\{Password, UserRole};
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;

/**
 * Mapper for User Entity <-> UserModel.
 */
final class UserMapper
{
    use BaseMapper;

    /**
     * Convert Eloquent Model to Domain Entity.
     */
    public function toDomain(UserModel $model): User
    {
        return User::reconstitute(
            id: $this->mapUuid($model->uuid),
            name: $model->name,
            email: $this->mapEmail($model->email),
            password: $this->mapPassword($model->password),
            role: $this->mapRole($model->role),
            timestamps: $this->mapTimestamps($model),
        );
    }

    /**
     * Convert Domain Entity to Eloquent data array.
     *
     * @return array<string, mixed>
     */
    public function toEloquent(User $entity): array
    {
        return [
            'uuid' => $entity->getId()->getValue(),
            'name' => $entity->getName(),
            'email' => $this->getEmailValue($entity->getEmail()),
            'password' => $entity->getPassword()->getValue(),
            'role' => $entity->getRole()->value,
            'created_at' => $entity->getTimestamps()->getCreatedAt(),
            'updated_at' => $entity->getTimestamps()->getUpdatedAt(),
        ];
    }

    /**
     * Convert collection of models to domain entities.
     *
     * @param UserModel[] $models
     * @return User[]
     */
    public function toDomainCollection(array $models): array
    {
        return array_map(
            fn(UserModel $model): User => $this->toDomain($model),
            $models
        );
    }

    /**
     * Map hashed password to Password VO.
     */
    private function mapPassword(string $hashedPassword): Password
    {
        return Password::fromHash($hashedPassword);
    }

    /**
     * Map role string to UserRole enum.
     */
    private function mapRole(UserRole|string $role): UserRole
    {
        if ($role instanceof UserRole) {
            return $role;
        }

        return UserRole::fromString($role);
    }
}