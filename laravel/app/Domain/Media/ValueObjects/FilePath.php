<?php

declare(strict_types=1);

namespace App\Domain\Media\ValueObjects;

use App\Domain\Shared\Exceptions\ValidationException;
use App\Domain\Shared\ValueObject;
use Random\RandomException;

/**
 * File Path Value Object.
 *
 * Represents a validated file path for media storage.
 */
final class FilePath extends ValueObject
{
    private readonly string $value;

    private function __construct(string $value)
    {
        $this->validateProperty($value);
        $this->value = $value;
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
     * Create a new file path from parts.
     */
    public static function fromParts(string ...$parts): self
    {
        $path = implode('/', array_map('trim', $parts));
        $path = preg_replace('#/+#', '/', $path); // Remove duplicate slashes

        return new self($path);
    }

    /**
     * Generate a unique file path for upload.
     *
     * @param string $directory Base directory
     * @param string $filename Original filename
     * @param string|null $extension Override extension (optional)
     * @throws ValidationException If random bytes generation fails
     */
    public static function generateForUpload(
        string $directory,
        string $filename,
        ?string $extension = null
    ): self {
        $timestamp = date('Y/m/d');

        try {
            $hash = bin2hex(random_bytes(8));
        } catch (RandomException) {
            throw ValidationException::forField(
                'path',
                'Failed to generate unique file path: insufficient system entropy'
            );
        }

        $name = pathinfo($filename, PATHINFO_FILENAME);
        $ext = $extension ?? pathinfo($filename, PATHINFO_EXTENSION);

        // Sanitize filename
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
        $name = substr($name, 0, 50);

        $fullFilename = $name . '_' . $hash . '.' . $ext;

        return self::fromParts($directory, $timestamp, $fullFilename);
    }

    /**
     * Validate file path.
     *
     * @throws ValidationException
     */
    protected function validate(mixed $value): void
    {
        if (!is_string($value)) {
            throw ValidationException::forField('path', 'File path must be a string');
        }

        if (empty($value)) {
            throw ValidationException::forField('path', 'File path cannot be empty');
        }

        if (strlen($value) > 500) {
            throw ValidationException::forField('path', 'File path cannot exceed 500 characters');
        }

        // Prevent directory traversal
        if (str_contains($value, '..')) {
            throw ValidationException::forField('path', 'File path cannot contain ".."');
        }

        // Prevent absolute paths
        if (str_starts_with($value, '/') || preg_match('/^[A-Z]:/i', $value)) {
            throw ValidationException::forField('path', 'File path must be relative');
        }
    }

    /**
     * Get the directory part.
     */
    public function getDirectory(): string
    {
        return pathinfo($this->value, PATHINFO_DIRNAME);
    }

    /**
     * Get the filename with extension.
     */
    public function getFilename(): string
    {
        return pathinfo($this->value, PATHINFO_BASENAME);
    }

    /**
     * Get the filename without extension.
     */
    public function getNameWithoutExtension(): string
    {
        return pathinfo($this->value, PATHINFO_FILENAME);
    }

    /**
     * Get the file extension.
     */
    public function getExtension(): string
    {
        return strtolower(pathinfo($this->value, PATHINFO_EXTENSION));
    }

    /**
     * Check if path has a specific extension.
     */
    public function hasExtension(string $extension): bool
    {
        return $this->getExtension() === strtolower($extension);
    }

    /**
     * Check equality with another FilePath.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Get the path string.
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
        return ['path' => $this->value];
    }
}