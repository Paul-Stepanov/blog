<?php

declare(strict_types=1);

namespace App\Domain\Media\ValueObjects;

use App\Domain\Shared\Exceptions\ValidationException;
use App\Domain\Shared\ValueObject;

/**
 * MIME Type Value Object.
 *
 * Represents a validated MIME type for media files.
 */
final class MimeType extends ValueObject
{
    private readonly string $value;

    /**
     * Allowed MIME types for uploads.
     */
    private const ALLOWED_TYPES = [
        // Images
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'image/avif',
        // Documents
        'application/pdf',
        'text/plain',
        'text/markdown',
        // Video
        'video/mp4',
        'video/webm',
    ];

    /**
     * Image MIME types.
     */
    private const IMAGE_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'image/avif',
    ];

    private function __construct(string $value)
    {
        $this->validateProperty($value);
        $this->value = strtolower($value);
    }

    /**
     * Create from string.
     *
     * @throws ValidationException
     */
    public static function fromString(string $value): self
    {
        return new self(trim($value));
    }

    /**
     * Detect MIME type from file extension.
     */
    public static function fromExtension(string $extension): self
    {
        $map = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'avif' => 'image/avif',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'md' => 'text/markdown',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
        ];

        $ext = strtolower(trim($extension, '.'));
        $mimeType = $map[$ext] ?? null;

        if ($mimeType === null) {
            throw ValidationException::forField(
                'mime_type',
                sprintf('Unknown MIME type for extension: ".%s"', $ext)
            );
        }

        return new self($mimeType);
    }

    /**
     * Validate MIME type.
     *
     * @throws ValidationException
     */
    protected function validate(mixed $value): void
    {
        if (!is_string($value)) {
            throw ValidationException::forField('mime_type', 'MIME type must be a string');
        }

        if (empty($value)) {
            throw ValidationException::forField('mime_type', 'MIME type cannot be empty');
        }

        if (!preg_match('/^[a-z0-9]+\/[a-z0-9\-\+\.]+$/i', $value)) {
            throw ValidationException::forField('mime_type', sprintf('Invalid MIME type format: "%s"', $value));
        }
    }

    /**
     * Check if this MIME type is allowed for upload.
     */
    public function isAllowed(): bool
    {
        return in_array($this->value, self::ALLOWED_TYPES, true);
    }

    /**
     * Check if this is an image MIME type.
     */
    public function isImage(): bool
    {
        return in_array($this->value, self::IMAGE_TYPES, true);
    }

    /**
     * Check if this is a video MIME type.
     */
    public function isVideo(): bool
    {
        return str_starts_with($this->value, 'video/');
    }

    /**
     * Check if this is a document MIME type.
     */
    public function isDocument(): bool
    {
        return str_starts_with($this->value, 'application/') || str_starts_with($this->value, 'text/');
    }

    /**
     * Get the type category (image, video, application, etc.).
     */
    public function getCategory(): string
    {
        return substr($this->value, 0, strpos($this->value, '/'));
    }

    /**
     * Get the subtype (jpeg, png, etc.).
     */
    public function getSubtype(): string
    {
        return substr($this->value, strpos($this->value, '/') + 1);
    }

    /**
     * Get typical file extension for this MIME type.
     */
    public function getDefaultExtension(): string
    {
        $map = array_flip([
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'image/avif' => 'avif',
            'application/pdf' => 'pdf',
            'text/plain' => 'txt',
            'text/markdown' => 'md',
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
        ]);

        return $map[$this->value] ?? 'bin';
    }

    /**
     * Ensure MIME type is allowed for upload.
     *
     * @throws ValidationException
     */
    public function ensureAllowed(): void
    {
        if (!$this->isAllowed()) {
            throw ValidationException::forField(
                'mime_type',
                sprintf('MIME type "%s" is not allowed. Allowed types: %s', $this->value, implode(', ', self::ALLOWED_TYPES))
            );
        }
    }

    /**
     * Check equality with another MimeType.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Get the MIME type string.
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return ['mime_type' => $this->value];
    }
}