<?php

declare(strict_types=1);

namespace App\Domain\Media\Services;

use App\Domain\Media\ValueObjects\FilePath;
use App\Domain\Media\ValueObjects\MimeType;

/**
 * File Storage Interface.
 *
 * Contract for file storage operations.
 * Abstracts the underlying storage mechanism (local, S3, etc.).
 */
interface FileStorageInterface
{
    /**
     * Store a file from uploaded content.
     *
     * @param string $content Binary file content
     * @param FilePath $path Target path
     * @param MimeType $mimeType MIME type of the file
     * @return bool Success status
     */
    public function store(string $content, FilePath $path, MimeType $mimeType): bool;

    /**
     * Store a file from a temporary upload path.
     *
     * @param string $tempPath Path to temporary uploaded file
     * @param FilePath $targetPath Target storage path
     * @return bool Success status
     */
    public function storeFromTemp(string $tempPath, FilePath $targetPath): bool;

    /**
     * Retrieve file content.
     *
     * @return string Binary file content
     * @throws \RuntimeException If file cannot be read
     */
    public function get(FilePath $path): string;

    /**
     * Check if file exists.
     */
    public function exists(FilePath $path): bool;

    /**
     * Delete a file.
     */
    public function delete(FilePath $path): bool;

    /**
     * Move/rename a file.
     */
    public function move(FilePath $from, FilePath $to): bool;

    /**
     * Copy a file.
     */
    public function copy(FilePath $from, FilePath $to): bool;

    /**
     * Get file size in bytes.
     */
    public function size(FilePath $path): int;

    /**
     * Get file's last modification time.
     */
    public function lastModified(FilePath $path): \DateTimeInterface;

    /**
     * Get public URL for a file.
     *
     * @return string Publicly accessible URL
     */
    public function getUrl(FilePath $path): string;

    /**
     * Get temporary URL for private files.
     *
     * @param int $expirationMinutes URL expiration time in minutes
     * @return string Temporary signed URL
     */
    public function getTemporaryUrl(FilePath $path, int $expirationMinutes = 60): string;

    /**
     * Check if file is publicly accessible.
     */
    public function isPublic(FilePath $path): bool;

    /**
     * Set file visibility (public/private).
     */
    public function setVisibility(FilePath $path, bool $public): bool;

    /**
     * Get MIME type of stored file.
     */
    public function getMimeType(FilePath $path): MimeType;

    /**
     * Generate a unique filename for upload.
     *
     * @param string $originalName Original filename
     * @param MimeType $mimeType MIME type
     * @return FilePath Generated unique path
     */
    public function generateUniquePath(string $originalName, MimeType $mimeType): FilePath;

    /**
     * Get total storage usage in bytes.
     */
    public function getTotalUsage(): int;

    /**
     * Get available storage space in bytes.
     * Returns -1 if unlimited or unknown.
     */
    public function getAvailableSpace(): int;
}