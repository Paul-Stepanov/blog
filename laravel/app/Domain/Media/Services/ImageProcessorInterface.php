<?php

declare(strict_types=1);

namespace App\Domain\Media\Services;

use App\Domain\Media\ValueObjects\FilePath;
use App\Domain\Media\ValueObjects\ImageDimensions;

/**
 * Image Processor Interface.
 *
 * Contract for image processing operations.
 * Abstracts the underlying image processing library.
 */
interface ImageProcessorInterface
{
    /**
     * Resize image to fit within max dimensions (maintains aspect ratio).
     *
     * @param FilePath $sourcePath Source file path
     * @param FilePath $targetPath Target file path
     * @param int $maxWidth Maximum width
     * @param int $maxHeight Maximum height
     * @return bool Success status
     */
    public function resize(
        FilePath $sourcePath,
        FilePath $targetPath,
        int $maxWidth,
        int $maxHeight
    ): bool;

    /**
     * Resize image to exact dimensions (may distort).
     *
     * @param FilePath $sourcePath Source file path
     * @param FilePath $targetPath Target file path
     * @param int $width Target width
     * @param int $height Target height
     * @return bool Success status
     */
    public function resizeExact(
        FilePath $sourcePath,
        FilePath $targetPath,
        int $width,
        int $height
    ): bool;

    /**
     * Crop image to specified dimensions from center.
     *
     * @param FilePath $sourcePath Source file path
     * @param FilePath $targetPath Target file path
     * @param int $width Crop width
     * @param int $height Crop height
     * @return bool Success status
     */
    public function crop(
        FilePath $sourcePath,
        FilePath $targetPath,
        int $width,
        int $height
    ): bool;

    /**
     * Convert image to WebP format.
     *
     * @param FilePath $sourcePath Source file path
     * @param FilePath $targetPath Target file path
     * @param int $quality WebP quality (1-100)
     * @return bool Success status
     */
    public function convertToWebP(
        FilePath $sourcePath,
        FilePath $targetPath,
        int $quality = 85
    ): bool;

    /**
     * Convert image to AVIF format.
     *
     * @param FilePath $sourcePath Source file path
     * @param FilePath $targetPath Target file path
     * @param int $quality AVIF quality (1-100)
     * @return bool Success status
     */
    public function convertToAVIF(
        FilePath $sourcePath,
        FilePath $targetPath,
        int $quality = 65
    ): bool;

    /**
     * Get image dimensions.
     *
     * @param FilePath $path Image file path
     * @return ImageDimensions|null Dimensions or null if not readable
     */
    public function getDimensions(FilePath $path): ?ImageDimensions;

    /**
     * Optimize image (reduce file size while maintaining quality).
     *
     * @param FilePath $path Image file path (modified in place)
     * @param int $quality Quality level (1-100)
     * @return bool Success status
     */
    public function optimize(FilePath $path, int $quality = 85): bool;

    /**
     * Check if file is a processable image.
     *
     * @param FilePath $path File path to check
     * @return bool True if processable image
     */
    public function isProcessableImage(FilePath $path): bool;
}