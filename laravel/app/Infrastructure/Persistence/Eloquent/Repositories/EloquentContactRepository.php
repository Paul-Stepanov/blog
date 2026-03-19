<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Contact\Entities\ContactMessage;
use App\Domain\Contact\Repositories\ContactRepositoryInterface;
use App\Domain\Shared\Exceptions\EntityNotFoundException;
use App\Domain\Shared\PaginatedResult;
use App\Domain\Shared\Uuid;
use App\Infrastructure\Persistence\Eloquent\Mappers\ContactMessageMapper;
use App\Infrastructure\Persistence\Eloquent\Models\ContactMessageModel;

/**
 * Eloquent implementation of Contact Repository.
 */
final readonly class EloquentContactRepository implements ContactRepositoryInterface
{
    public function __construct(
        private ContactMessageMapper $mapper,
    ) {}

    /**
     * @inheritDoc
     */
    public function findById(Uuid $id): ?ContactMessage
    {
        $model = $this->findModelById($id);

        return $model !== null
            ? $this->mapper->toDomain($model)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function getById(Uuid $id): ContactMessage
    {
        $model = $this->findModelById($id);

        if ($model === null) {
            throw EntityNotFoundException::forEntity('ContactMessage', $id);
        }

        return $this->mapper->toDomain($model);
    }

    /**
     * @inheritDoc
     */
    public function findAll(int $page = 1, int $perPage = 20): PaginatedResult
    {
        $query = ContactMessageModel::query()
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
    public function findUnread(int $page = 1, int $perPage = 20): PaginatedResult
    {
        $query = ContactMessageModel::query()
            ->where('is_read', false)
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
    public function findRead(int $page = 1, int $perPage = 20): PaginatedResult
    {
        $query = ContactMessageModel::query()
            ->where('is_read', true)
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

        $query = ContactMessageModel::query()
            ->where(function ($q) use ($searchTerm): void {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('subject', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('message', 'LIKE', "%{$searchTerm}%");
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
    public function getRecent(int $limit = 10): array
    {
        $models = ContactMessageModel::query()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->all();

        return $this->mapper->toDomainCollection($models);
    }

    /**
     * @inheritDoc
     */
    public function findByEmail(string $email): array
    {
        $models = ContactMessageModel::query()
            ->where('email', $email)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();

        return $this->mapper->toDomainCollection($models);
    }

    /**
     * @inheritDoc
     */
    public function findByIpAddress(string $ipAddress): array
    {
        $models = ContactMessageModel::query()
            ->where('ip_address', $ipAddress)
            ->orderBy('created_at', 'desc')
            ->get()
            ->all();

        return $this->mapper->toDomainCollection($models);
    }

    /**
     * @inheritDoc
     */
    public function save(ContactMessage $message): void
    {
        $data = $this->mapper->toEloquent($message);

        ContactMessageModel::query()->updateOrCreate(
            ['uuid' => $data['uuid']],
            $data
        );
    }

    /**
     * @inheritDoc
     */
    public function delete(Uuid $id): void
    {
        ContactMessageModel::query()
            ->where('uuid', $id->getValue())
            ->delete();
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return ContactMessageModel::query()->count();
    }

    /**
     * @inheritDoc
     */
    public function countUnread(): int
    {
        return ContactMessageModel::query()
            ->where('is_read', false)
            ->count();
    }

    /**
     * @inheritDoc
     */
    public function countByDate(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $results = ContactMessageModel::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $counts = [];
        foreach ($results as $row) {
            $counts[$row->date] = $row->count;
        }

        return $counts;
    }

    /**
     * Find model by UUID.
     */
    private function findModelById(Uuid $id): ?ContactMessageModel
    {
        return ContactMessageModel::query()
            ->where('uuid', $id->getValue())
            ->first();
    }
}