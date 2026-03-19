<?php

declare(strict_types=1);

namespace App\Application\Media\Services;

use App\Application\Media\DTOs\MediaFileDTO;
use App\Application\Media\Exceptions\{FileUploadFailedException, MediaFileNotFoundException};
use App\Domain\Media\Entities\MediaFile;
use App\Domain\Media\Repositories\MediaRepositoryInterface;
use App\Domain\Media\Services\FileStorageInterface;
use App\Domain\Media\ValueObjects\{FilePath, ImageDimensions, MimeType};
use App\Domain\Shared\Exceptions\ValidationException;
use App\Domain\Shared\Uuid;

/**
 * Media Application Service.
 *
 * Handles file uploads and media management.
 */
final readonly class MediaService
{
    public function __construct(
        private MediaRepositoryInterface $mediaRepository,
        private FileStorageInterface $fileStorage,
    ) {}

    /**
     * Upload a file.
     *
     * @param string $filename Original filename
     * @param string $content File content (binary)
     * @param string $mimeTypeString MIME type string
     * @param int $sizeBytes File size in bytes
     * @param int|null $width Image width (if applicable)
     * @param int|null $height Image height (if applicable)
     * @param string $altText Alt text for accessibility
     * @return MediaFileDTO Uploaded file data
     * @throws FileUploadFailedException If storage fails
     * @throws ValidationException If MIME type, path, or dimensions are invalid
     */
    public function uploadFile(
        string $filename,
        string $content,
        string $mimeTypeString,
        int $sizeBytes,
        ?int $width = null,
        ?int $height = null,
        string $altText = '',
    ): MediaFileDTO {
        $mimeType = MimeType::fromString($mimeTypeString);

        $path = FilePath::generateForUpload(
            directory: 'uploads',
            filename: $filename
        );

        $stored = $this->fileStorage->store($content, $path, $mimeType);

        if (!$stored) {
            throw FileUploadFailedException::storageFailed(
                filename: $filename,
                reason: 'Storage driver returned false'
            );
        }

        $dimensions = ($width !== null && $height !== null)
            ? ImageDimensions::fromIntegers($width, $height)
            : null;

        $mediaFile = MediaFile::upload(
            id: Uuid::generate(),
            filename: $filename,
            path: $path,
            mimeType: $mimeType,
            sizeBytes: $sizeBytes,
            dimensions: $dimensions,
            altText: $altText,
        );

        $this->mediaRepository->save($mediaFile);

        return MediaFileDTO::fromEntity($mediaFile);
    }

    /**
     * Update alt text for a file.
     *
     * @throws MediaFileNotFoundException If file not found
     * @throws ValidationException If UUID format is invalid
     */
    public function updateAltText(string $fileId, string $altText): MediaFileDTO
    {
        $uuid = Uuid::fromString($fileId);
        $mediaFile = $this->findOrFail($uuid);

        $mediaFile->updateAltText($altText);
        $this->mediaRepository->save($mediaFile);

        return MediaFileDTO::fromEntity($mediaFile);
    }

    /**
     * Rename a file.
     *
     * @throws MediaFileNotFoundException If file not found
     * @throws ValidationException If UUID or filename is invalid
     */
    public function renameFile(string $fileId, string $newFilename): MediaFileDTO
    {
        $uuid = Uuid::fromString($fileId);
        $mediaFile = $this->findOrFail($uuid);

        $mediaFile->rename($newFilename);
        $this->mediaRepository->save($mediaFile);

        return MediaFileDTO::fromEntity($mediaFile);
    }

    /**
     * Delete a file.
     *
     * @throws ValidationException If UUID format is invalid
     */
    public function deleteFile(string $fileId): void
    {
        $uuid = Uuid::fromString($fileId);
        $mediaFile = $this->mediaRepository->findById($uuid);

        if ($mediaFile !== null) {
            $this->fileStorage->delete($mediaFile->getPath());
            $this->mediaRepository->delete($uuid);
        }
    }

    /**
     * Get file by ID.
     *
     * @throws MediaFileNotFoundException If file not found
     * @throws ValidationException If UUID format is invalid
     */
    public function getFile(string $fileId): MediaFileDTO
    {
        $uuid = Uuid::fromString($fileId);
        $mediaFile = $this->findOrFail($uuid);

        return MediaFileDTO::fromEntity($mediaFile);
    }

    /**
     * Find media file or throw exception.
     *
     * @throws MediaFileNotFoundException If file not found
     */
    private function findOrFail(Uuid $uuid): MediaFile
    {
        $mediaFile = $this->mediaRepository->findById($uuid);

        if ($mediaFile === null) {
            throw MediaFileNotFoundException::byId($uuid->getValue());
        }

        return $mediaFile;
    }
}