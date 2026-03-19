<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Media\Entities\MediaFile;
use App\Domain\Media\Repositories\MediaRepositoryInterface;
use App\Domain\Media\ValueObjects\MimeType;
use App\Domain\Shared\Exceptions\EntityNotFoundException;
use App\Domain\Shared\PaginatedResult;
use App\Domain\Shared\Uuid;
use App\Infrastructure\Persistence\Eloquent\Mappers\MediaFileMapper;
use App\Infrastructure\Persistence\Eloquent\Models\MediaFileModel;

/**
 * Eloquent implementation of Media Repository.
 */
final readonly class EloquentMediaRepository implements MediaRepositoryInterface
{
    public function __construct(
        private MediaFileMapper $mapper,
    ) {}

    /**
     * @inheritDoc
     */
    public function findById(Uuid $id): ?MediaFile
    {
        $model = $this->findModelById($id);

        return $model !== null
            ? $this->mapper->toDomain($model)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function getById(Uuid $id): MediaFile
    {
        $model = $this->findModelById($id);

        if ($model === null) {
            throw EntityNotFoundException::forEntity('MediaFile', $id);
        }

        return $this->mapper->toDomain($model);
    }

    /**
     * @inheritDoc
     */
    public function findByPath(string $path): ?MediaFile
    {
        $model = MediaFileModel::query()
            ->where('path', $path)
            ->first();

        return $model !== null
            ? $this->mapper->toDomain($model)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function findAll(int $page = 1, int $perPage = 30): PaginatedResult
    {
        $query = MediaFileModel::query()
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
    public function findImages(int $page = 1, int $perPage = 30): PaginatedResult
    {
        $query = MediaFileModel::query()
            ->where('mime_type', 'LIKE', 'image/%')
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
    public function findDocuments(int $page = 1, int $perPage = 30): PaginatedResult
    {
        $query = MediaFileModel::query()
            ->where('mime_type', 'LIKE', 'application/%')
            ->orWhere('mime_type', 'LIKE', 'text/%')
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
    public function findVideos(int $page = 1, int $perPage = 30): PaginatedResult
    {
        $query = MediaFileModel::query()
            ->where('mime_type', 'LIKE', 'video/%')
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
    public function findByMimeType(MimeType $mimeType, int $page = 1, int $perPage = 30): PaginatedResult
    {
        $query = MediaFileModel::query()
            ->where('mime_type', $mimeType->getValue())
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
    public function search(string $query, int $page = 1, int $perPage = 30): PaginatedResult
    {
        $searchTerm = str_replace(
            ['%', '_', '\\'],
            ['\\%', '\\_', '\\\\'],
            $query
        );

        $query = MediaFileModel::query()
            ->where('filename', 'LIKE', "%{$searchTerm}%")
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
        $models = MediaFileModel::query()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->all();

        return $this->mapper->toDomainCollection($models);
    }

    /**
     * @inheritDoc
     */
    public function getUnused(int $page = 1, int $perPage = 30): PaginatedResult
    {
        // Files not attached to any article as cover image
        $query = MediaFileModel::query()
            ->whereDoesntHave('articles')
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
    public function save(MediaFile $mediaFile): void
    {
        $data = $this->mapper->toEloquent($mediaFile);

        MediaFileModel::query()->updateOrCreate(
            ['uuid' => $data['uuid']],
            $data
        );
    }

    /**
     * @inheritDoc
     */
    public function delete(Uuid $id): void
    {
        MediaFileModel::query()
            ->where('uuid', $id->getValue())
            ->delete();
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return MediaFileModel::query()->count();
    }

    /**
     * @inheritDoc
     */
    public function countByType(): array
    {
        $results = MediaFileModel::query()
            ->selectRaw("
                CASE
                    WHEN mime_type LIKE 'image/%' THEN 'image'
                    WHEN mime_type LIKE 'video/%' THEN 'video'
                    WHEN mime_type LIKE 'application/%' OR mime_type LIKE 'text/%' THEN 'document'
                    ELSE 'other'
                END as type,
                COUNT(*) as count
            ")
            ->groupBy('type')
            ->get();

        $counts = [
            'image' => 0,
            'video' => 0,
            'document' => 0,
            'other' => 0,
        ];

        foreach ($results as $row) {
            if (isset($counts[$row->type])) {
                $counts[$row->type] = $row->count;
            }
        }

        return $counts;
    }

    /**
     * @inheritDoc
     */
    public function getTotalSize(): int
    {
        return (int) MediaFileModel::query()
            ->sum('size_bytes');
    }

    /**
     * Find model by UUID.
     */
    private function findModelById(Uuid $id): ?MediaFileModel
    {
        return MediaFileModel::query()
            ->where('uuid', $id->getValue())
            ->first();
    }
}