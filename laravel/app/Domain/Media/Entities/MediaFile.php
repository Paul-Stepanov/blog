<?php

declare(strict_types=1);

namespace App\Domain\Media\Entities;

use App\Domain\Media\ValueObjects\{FilePath, ImageDimensions, MimeType};
use App\Domain\Shared\Entity;
use App\Domain\Shared\Exceptions\ValidationException;
use App\Domain\Shared\Timestamps;
use App\Domain\Shared\Uuid;

/**
 * MediaFile Entity.
 *
 * Represents an uploaded file (image, document, video).
 */
final class MediaFile extends Entity
{
    private string $filename;
    private string $altText;
    private Timestamps $timestamps;
    private readonly FilePath $path;
    private readonly MimeType $mimeType;
    private readonly int $sizeBytes;
    private readonly ?ImageDimensions $dimensions;

    public function __construct(
        Uuid $id,
        string $filename,
        FilePath $path,
        MimeType $mimeType,
        int $sizeBytes,
        ?ImageDimensions $dimensions,
        string $altText,
        Timestamps $timestamps,
    ) {
        parent::__construct($id);

        $this->filename = $filename;
        $this->path = $path;
        $this->mimeType = $mimeType;
        $this->sizeBytes = $sizeBytes;
        $this->dimensions = $dimensions;
        $this->altText = $altText;
        $this->timestamps = $timestamps;
    }

    /**
     * Create from uploaded file.
     *
     * @throws ValidationException
     */
    public static function upload(
        Uuid $id,
        string $filename,
        FilePath $path,
        MimeType $mimeType,
        int $sizeBytes,
        ?ImageDimensions $dimensions = null,
        string $altText = '',
    ): self {
        return new self(
            id: $id,
            filename: $filename,
            path: $path,
            mimeType: $mimeType,
            sizeBytes: $sizeBytes,
            dimensions: $dimensions,
            altText: $altText,
            timestamps: Timestamps::now(),
        );
    }

    /**
     * Reconstruct from persistence.
     */
    public static function reconstitute(
        Uuid $id,
        string $filename,
        FilePath $path,
        MimeType $mimeType,
        int $sizeBytes,
        ?ImageDimensions $dimensions,
        string $altText,
        Timestamps $timestamps,
    ): self {
        return new self(
            id: $id,
            filename: $filename,
            path: $path,
            mimeType: $mimeType,
            sizeBytes: $sizeBytes,
            dimensions: $dimensions,
            altText: $altText,
            timestamps: $timestamps,
        );
    }

    /**
     * Update alt text for accessibility.
     */
    public function updateAltText(string $altText): void
    {
        $this->altText = $altText;
        $this->timestamps = $this->timestamps->touch();
    }

    /**
     * Rename the file.
     *
     * @throws ValidationException
     */
    public function rename(string $newFilename): void
    {
        if (empty(trim($newFilename))) {
            throw ValidationException::forField('filename', 'Filename cannot be empty');
        }

        $this->filename = $newFilename;
        $this->timestamps = $this->timestamps->touch();
    }

    /**
     * Check if this is an image.
     */
    public function isImage(): bool
    {
        return $this->mimeType->isImage();
    }

    /**
     * Check if this is a video.
     */
    public function isVideo(): bool
    {
        return $this->mimeType->isVideo();
    }

    /**
     * Check if this is a document.
     */
    public function isDocument(): bool
    {
        return $this->mimeType->isDocument();
    }

    /**
     * Get file size in human-readable format.
     */
    public function getSizeHuman(): string
    {
        $bytes = $this->sizeBytes;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get public URL for the file.
     */
    public function getPublicUrl(): string
    {
        return '/storage/' . $this->path->getValue();
    }

    // Getters

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getPath(): FilePath
    {
        return $this->path;
    }

    public function getMimeType(): MimeType
    {
        return $this->mimeType;
    }

    public function getSizeBytes(): int
    {
        return $this->sizeBytes;
    }

    public function getDimensions(): ?ImageDimensions
    {
        return $this->dimensions;
    }

    public function getAltText(): string
    {
        return $this->altText;
    }

    public function getTimestamps(): Timestamps
    {
        return $this->timestamps;
    }
}