<?php

declare(strict_types=1);

namespace App\Domain\Media\Repositories;

use App\Domain\Media\Entities\MediaFile;
use App\Domain\Media\ValueObjects\MimeType;
use App\Domain\Shared\PaginatedResult;
use App\Domain\Shared\Uuid;

/**
 * Media Repository Interface.
 *
 * Contract for media file persistence operations.
 */
interface MediaRepositoryInterface
{
    /**
     * Find media file by ID.
     */
    public function findById(Uuid $id): ?MediaFile;

    /**
     * Find media file by path.
     */
    public function findByPath(string $path): ?MediaFile;

    /**
     * Find all media files with pagination.
     *
     * @return PaginatedResult<MediaFile>
     */
    public function findAll(int $page = 1, int $perPage = 30): PaginatedResult;

    /**
     * Find images only.
     *
     * @return PaginatedResult<MediaFile>
     */
    public function findImages(int $page = 1, int $perPage = 30): PaginatedResult;

    /**
     * Find documents only.
     *
     * @return PaginatedResult<MediaFile>
     */
    public function findDocuments(int $page = 1, int $perPage = 30): PaginatedResult;

    /**
     * Find videos only.
     *
     * @return PaginatedResult<MediaFile>
     */
    public function findVideos(int $page = 1, int $perPage = 30): PaginatedResult;

    /**
     * Find by MIME type.
     *
     * @return PaginatedResult<MediaFile>
     */
    public function findByMimeType(MimeType $mimeType, int $page = 1, int $perPage = 30): PaginatedResult;

    /**
     * Search media files by filename.
     *
     * @return PaginatedResult<MediaFile>
     */
    public function search(string $query, int $page = 1, int $perPage = 30): PaginatedResult;

    /**
     * Get recent uploads.
     *
     * @return array<MediaFile>
     */
    public function getRecent(int $limit = 10): array;

    /**
     * Get unused media files (not attached to any entity).
     *
     * @return PaginatedResult<MediaFile>
     */
    public function getUnused(int $page = 1, int $perPage = 30): PaginatedResult;

    /**
     * Save media file (create or update).
     */
    public function save(MediaFile $mediaFile): void;

    /**
     * Delete media file by ID.
     */
    public function delete(Uuid $id): void;

    /**
     * Count total media files.
     */
    public function count(): int;

    /**
     * Count by type (image, video, document).
     *
     * @return array<string, int>
     */
    public function countByType(): array;

    /**
     * Get total storage size in bytes.
     */
    public function getTotalSize(): int;
}