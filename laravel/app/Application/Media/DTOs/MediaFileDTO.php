<?php

declare(strict_types=1);

namespace App\Application\Media\DTOs;

use App\Application\Shared\{DTOFormattingTrait, DTOInterface};
use App\Application\Shared\Exceptions\InvalidEntityTypeException;
use App\Domain\Media\Entities\MediaFile;
use App\Domain\Shared\Entity;

/**
 * Media File Data Transfer Object.
 *
 * Represents an uploaded file for API responses.
 */
final readonly class MediaFileDTO implements DTOInterface
{
    use DTOFormattingTrait;

    /**
     * @param string $id UUID string
     * @param string $filename Original filename
     * @param string $path Storage path
     * @param string $publicUrl Public URL
     * @param string $mimeType MIME type
     * @param int $sizeBytes File size in bytes
     * @param string $sizeHuman Human-readable size
     * @param int|null $width Image width (null for non-images)
     * @param int|null $height Image height (null for non-images)
     * @param string $altText Alt text for accessibility
     * @param bool $isImage Whether file is an image
     * @param string $createdAt ISO 8601 datetime
     * @param string $updatedAt ISO 8601 datetime
     */
    public function __construct(
        public string $id,
        public string $filename,
        public string $path,
        public string $publicUrl,
        public string $mimeType,
        public int $sizeBytes,
        public string $sizeHuman,
        public ?int $width,
        public ?int $height,
        public string $altText,
        public bool $isImage,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    /**
     * Create from Domain Entity.
     *
     * @param Entity $entity Domain media file entity
     */
    public static function fromEntity(Entity $entity): static
    {
        if (!$entity instanceof MediaFile) {
            throw new InvalidEntityTypeException(
                expectedType: MediaFile::class,
                actualType: $entity::class
            );
        }

        $timestamps = $entity->getTimestamps();
        $dimensions = $entity->getDimensions();

        return new self(
            id: $entity->getId()->getValue(),
            filename: $entity->getFilename(),
            path: $entity->getPath()->getValue(),
            publicUrl: $entity->getPublicUrl(),
            mimeType: $entity->getMimeType()->getValue(),
            sizeBytes: $entity->getSizeBytes(),
            sizeHuman: $entity->getSizeHuman(),
            width: $dimensions?->getWidth(),
            height: $dimensions?->getHeight(),
            altText: $entity->getAltText(),
            isImage: $entity->isImage(),
            createdAt: self::formatDate($timestamps->getCreatedAt()),
            updatedAt: self::formatDate($timestamps->getUpdatedAt()),
        );
    }

    /**
     * Convert DTO to associative array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'filename' => $this->filename,
            'path' => $this->path,
            'public_url' => $this->publicUrl,
            'mime_type' => $this->mimeType,
            'size_bytes' => $this->sizeBytes,
            'size_human' => $this->sizeHuman,
            'width' => $this->width,
            'height' => $this->height,
            'alt_text' => $this->altText,
            'is_image' => $this->isImage,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    /**
     * Check if file is a video.
     */
    public function isVideo(): bool
    {
        return str_starts_with($this->mimeType, 'video/');
    }

    /**
     * Check if file is a document.
     */
    public function isDocument(): bool
    {
        return !$this->isImage && !$this->isVideo();
    }

    /**
     * Get aspect ratio for images (width/height).
     */
    public function getAspectRatio(): ?float
    {
        if ($this->width === null || $this->height === null || $this->height === 0) {
            return null;
        }

        return $this->width / $this->height;
    }
}