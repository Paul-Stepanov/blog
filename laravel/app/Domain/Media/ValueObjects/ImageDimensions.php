<?php

declare(strict_types=1);

namespace App\Domain\Media\ValueObjects;

use App\Domain\Shared\Exceptions\ValidationException;
use App\Domain\Shared\ValueObject;

/**
 * Image Dimensions Value Object.
 *
 * Represents width and height of an image.
 */
final class ImageDimensions extends ValueObject
{
    public function __construct(
        private readonly int $width,
        private readonly int $height
    ) {
        $this->validateProperty(['width' => $width, 'height' => $height]);
    }

    /**
     * Create from width and height.
     *
     * @throws ValidationException
     */
    public static function fromIntegers(int $width, int $height): self
    {
        return new self($width, $height);
    }

    /**
     * Create from an array [width, height].
     *
     * @throws ValidationException
     */
    public static function fromArray(array $dimensions): self
    {
        if (!isset($dimensions[0], $dimensions[1])) {
            throw ValidationException::forField('dimensions', 'Dimensions array must contain [width, height]');
        }

        return new self((int) $dimensions[0], (int) $dimensions[1]);
    }

    /**
     * Create from getimagesize() result.
     *
     * @throws ValidationException
     */
    public static function fromImageSize(array|false $imageSize): self
    {
        if ($imageSize === false || !isset($imageSize[0], $imageSize[1])) {
            throw ValidationException::forField('dimensions', 'Could not determine image dimensions');
        }

        return new self($imageSize[0], $imageSize[1]);
    }

    /**
     * Validate dimensions.
     *
     * @throws ValidationException
     */
    protected function validate(mixed $value): void
    {
        if (!is_array($value)) {
            throw ValidationException::forField('dimensions', 'Dimensions must be an array');
        }

        $width = $value['width'] ?? null;
        $height = $value['height'] ?? null;

        if ($width === null || $height === null) {
            throw ValidationException::forField('dimensions', 'Width and height are required');
        }

        if ($width < 1 || $width > 50000) {
            throw ValidationException::forField('width', 'Width must be between 1 and 50000 pixels');
        }

        if ($height < 1 || $height > 50000) {
            throw ValidationException::forField('height', 'Height must be between 1 and 50000 pixels');
        }
    }

    /**
     * Get width.
     */
    public function getWidth(): int
    {
        return $this->width;
    }

    /**
     * Get height.
     */
    public function getHeight(): int
    {
        return $this->height;
    }

    /**
     * Calculate aspect ratio (width / height).
     */
    public function getAspectRatio(): float
    {
        return $this->width / $this->height;
    }

    /**
     * Check if image is landscape (width > height).
     */
    public function isLandscape(): bool
    {
        return $this->width > $this->height;
    }

    /**
     * Check if image is portrait (height > width).
     */
    public function isPortrait(): bool
    {
        return $this->height > $this->width;
    }

    /**
     * Check if image is square.
     */
    public function isSquare(): bool
    {
        return $this->width === $this->height;
    }

    /**
     * Calculate total pixels (width * height).
     */
    public function getPixelCount(): int
    {
        return $this->width * $this->height;
    }

    /**
     * Get megapixels (for display).
     */
    public function getMegapixels(): float
    {
        return round($this->getPixelCount() / 1000000, 1);
    }

    /**
     * Check if dimensions match a specific aspect ratio.
     *
     * @param float $ratio Target ratio (e.g., 16/9, 4/3)
     * @param float $tolerance Tolerance for comparison (default 0.01)
     */
    public function hasAspectRatio(float $ratio, float $tolerance = 0.01): bool
    {
        return abs($this->getAspectRatio() - $ratio) <= $tolerance;
    }

    /**
     * Get common aspect ratio name if matches.
     */
    public function getAspectRatioName(): ?string
    {
        return match (true) {
            $this->hasAspectRatio(16 / 9) => '16:9',
            $this->hasAspectRatio(4 / 3) => '4:3',
            $this->hasAspectRatio(3 / 2) => '3:2',
            $this->hasAspectRatio(1 / 1) => '1:1',
            $this->hasAspectRatio(21 / 9) => '21:9',
            $this->hasAspectRatio(9 / 16) => '9:16',
            default => null,
        };
    }

    /**
     * Resize to fit within max dimensions while maintaining aspect ratio.
     */
    public function resizeToFit(int $maxWidth, int $maxHeight): self
    {
        $ratio = min($maxWidth / $this->width, $maxHeight / $this->height);

        if ($ratio >= 1) {
            return new self($this->width, $this->height);
        }

        return new self(
            (int) round($this->width * $ratio),
            (int) round($this->height * $ratio)
        );
    }

    /**
     * Check equality with another ImageDimensions.
     */
    public function equals(self $other): bool
    {
        return $this->width === $other->width && $this->height === $other->height;
    }

    /**
     * Get dimensions as array.
     *
     * @return array{width: int, height: int}
     */
    public function getValue(): array
    {
        return ['width' => $this->width, 'height' => $this->height];
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
            'aspect_ratio' => round($this->getAspectRatio(), 2),
        ];
    }
}