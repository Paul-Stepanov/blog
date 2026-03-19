<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Shared\Exceptions\EntityNotFoundException;
use App\Domain\Shared\PaginatedResult;
use App\Domain\Shared\Uuid;
use App\Domain\User\Entities\User;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Domain\User\ValueObjects\UserRole;
use App\Infrastructure\Persistence\Eloquent\Mappers\UserMapper;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;

/**
 * Eloquent implementation of User Repository.
 */
final readonly class EloquentUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private UserMapper $mapper,
    ) {}

    /**
     * @inheritDoc
     */
    public function findById(Uuid $id): ?User
    {
        $model = $this->findModelById($id);

        return $model !== null
            ? $this->mapper->toDomain($model)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function getById(Uuid $id): User
    {
        $model = $this->findModelById($id);

        if ($model === null) {
            throw EntityNotFoundException::forEntity('User', $id);
        }

        return $this->mapper->toDomain($model);
    }

    /**
     * @inheritDoc
     */
    public function findByEmail(string $email): ?User
    {
        $model = UserModel::query()
            ->where('email', $email)
            ->first();

        return $model !== null
            ? $this->mapper->toDomain($model)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function getByEmailOrFail(string $email): User
    {
        $model = UserModel::query()
            ->where('email', $email)
            ->first();

        if ($model === null) {
            throw EntityNotFoundException::byEmail('User', $email);
        }

        return $this->mapper->toDomain($model);
    }

    /**
     * @inheritDoc
     */
    public function findByEmailForAuth(string $email): ?User
    {
        $model = UserModel::query()
            ->where('email', $email)
            ->first();

        return $model !== null
            ? $this->mapper->toDomain($model)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function findAll(int $page = 1, int $perPage = 20): PaginatedResult
    {
        $query = UserModel::query()
            ->orderBy('created_at', 'desc');

        $total = $query->count();
        $models = $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->all();

        return PaginatedResult::create(
            items: $this->mapper->toDomainCollection($models),
            total: $total,
            page: $page,
            perPage: $perPage,
        );
    }

    /**
     * @inheritDoc
     */
    public function findByRole(UserRole $role, int $page = 1, int $perPage = 20): PaginatedResult
    {
        $query = UserModel::query()
            ->where('role', $role->value)
            ->orderBy('created_at', 'desc');

        $total = $query->count();
        $models = $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->all();

        return PaginatedResult::create(
            items: $this->mapper->toDomainCollection($models),
            total: $total,
            page: $page,
            perPage: $perPage,
        );
    }

    /**
     * @inheritDoc
     */
    public function search(string $query, int $page = 1, int $perPage = 20): PaginatedResult
    {
        $searchTerm = str_replace(
            ['%', '_', '\\'],
            ['\\%', '\\_', '\\\\'],
            $query
        );

        $query = UserModel::query()
            ->where(function ($q) use ($searchTerm): void {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('email', 'LIKE', "%{$searchTerm}%");
            })
            ->orderBy('created_at', 'desc');

        $total = $query->count();
        $models = $query
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->all();

        return PaginatedResult::create(
            items: $this->mapper->toDomainCollection($models),
            total: $total,
            page: $page,
            perPage: $perPage,
        );
    }

    /**
     * @inheritDoc
     */
    public function getAdmins(): array
    {
        $models = UserModel::query()
            ->where('role', UserRole::ADMIN->value)
            ->orderBy('name', 'asc')
            ->get()
            ->all();

        return $this->mapper->toDomainCollection($models);
    }

    /**
     * @inheritDoc
     */
    public function getEditors(): array
    {
        $models = UserModel::query()
            ->where('role', UserRole::EDITOR->value)
            ->orderBy('name', 'asc')
            ->get()
            ->all();

        return $this->mapper->toDomainCollection($models);
    }

    /**
     * @inheritDoc
     */
    public function save(User $user): void
    {
        $data = $this->mapper->toEloquent($user);

        UserModel::query()->updateOrCreate(
            ['uuid' => $data['uuid']],
            $data
        );
    }

    /**
     * @inheritDoc
     */
    public function delete(Uuid $id): void
    {
        UserModel::query()
            ->where('uuid', $id->getValue())
            ->delete();
    }

    /**
     * @inheritDoc
     */
    public function emailExists(string $email, ?Uuid $excludeId = null): bool
    {
        $query = UserModel::query()->where('email', $email);

        if ($excludeId !== null) {
            $query->where('uuid', '!=', $excludeId->getValue());
        }

        return $query->exists();
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return UserModel::query()->count();
    }

    /**
     * @inheritDoc
     */
    public function countByRole(): array
    {
        $results = UserModel::query()
            ->selectRaw('role, COUNT(*) as count')
            ->groupBy('role')
            ->get();

        $counts = [
            'admin' => 0,
            'editor' => 0,
            'author' => 0,
        ];

        foreach ($results as $row) {
            if (isset($counts[$row->role])) {
                $counts[$row->role] = $row->count;
            }
        }

        return $counts;
    }

    /**
     * Find model by UUID.
     */
    private function findModelById(Uuid $id): ?UserModel
    {
        return UserModel::query()
            ->where('uuid', $id->getValue())
            ->first();
    }
}