<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Domain\Media\Services\ImageProcessorInterface;
use App\Domain\Media\ValueObjects\FilePath;
use App\Domain\Media\ValueObjects\ImageDimensions;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

/**
 * Intervention Image Processor.
 *
 * Implements ImageProcessorInterface using Intervention Image v3 library.
 * Supports GD and Imagick drivers.
 */
final readonly class InterventionImageProcessor implements ImageProcessorInterface
{
    private ImageManager $manager;

    public function __construct(?string $driver = null)
    {
        $driver = $driver ?? (extension_loaded('imagick') ? 'imagick' : 'gd');
        $this->manager = ImageManager::withDriver($driver);
    }

    /**
     * @inheritDoc
     */
    public function resize(
        FilePath $sourcePath,
        FilePath $targetPath,
        int $maxWidth,
        int $maxHeight
    ): bool {
        try {
            $image = $this->manager->read($sourcePath->getValue());
            $image->scaleDown(width: $maxWidth, height: $maxHeight);

            return $image->save($targetPath->getValue()) !== null;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function resizeExact(
        FilePath $sourcePath,
        FilePath $targetPath,
        int $width,
        int $height
    ): bool {
        try {
            $image = $this->manager->read($sourcePath->getValue());
            $image->resize(width: $width, height: $height);

            return $image->save($targetPath->getValue()) !== null;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function crop(
        FilePath $sourcePath,
        FilePath $targetPath,
        int $width,
        int $height
    ): bool {
        try {
            $image = $this->manager->read($sourcePath->getValue());
            $image->cover(width: $width, height: $height);

            return $image->save($targetPath->getValue()) !== null;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function convertToWebP(
        FilePath $sourcePath,
        FilePath $targetPath,
        int $quality = 85
    ): bool {
        try {
            $image = $this->manager->read($sourcePath->getValue());

            return $image->toWebp(quality: $quality)->save($targetPath->getValue()) !== null;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function convertToAVIF(
        FilePath $sourcePath,
        FilePath $targetPath,
        int $quality = 65
    ): bool {
        try {
            $image = $this->manager->read($sourcePath->getValue());

            return $image->toAvif(quality: $quality)->save($targetPath->getValue()) !== null;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function getDimensions(FilePath $path): ?ImageDimensions
    {
        try {
            $image = $this->manager->read($path->getValue());

            return ImageDimensions::fromIntegers(
                $image->width(),
                $image->height()
            );
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * @inheritDoc
     */
    public function optimize(FilePath $path, int $quality = 85): bool
    {
        try {
            $image = $this->manager->read($path->getValue());

            return $image->save($path->getValue(), quality: $quality) !== null;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function isProcessableImage(FilePath $path): bool
    {
        $processableExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
        $extension = strtolower($path->getExtension());

        return in_array($extension, $processableExtensions, true);
    }
}