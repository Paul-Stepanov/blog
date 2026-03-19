<?php

declare(strict_types=1);

namespace App\Infrastructure\Queue\Jobs;

use App\Domain\Media\Repositories\MediaRepositoryInterface;
use App\Domain\Media\Services\ImageProcessorInterface;
use App\Domain\Media\ValueObjects\FilePath;
use App\Domain\Shared\Uuid;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Process Image Job.
 *
 * Queue job for processing uploaded images:
 * - Create thumbnails in multiple sizes
 * - Convert to WebP format
 * - Optimize original image
 */
final class ProcessImageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 2;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @param Uuid $mediaFileId The UUID of the MediaFile entity
     * @param array<string> $sizes Thumbnail sizes to generate (thumb, small, medium, large)
     */
    public function __construct(
        public readonly Uuid $mediaFileId,
        public readonly array $sizes = ['thumb', 'small', 'medium']
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        MediaRepositoryInterface $mediaRepository,
        ImageProcessorInterface $imageProcessor
    ): void {
        $mediaFile = $mediaRepository->findById($this->mediaFileId);

        if ($mediaFile === null) {
            Log::warning('ProcessImageJob: MediaFile not found', [
                'media_file_id' => $this->mediaFileId->getValue(),
            ]);

            return;
        }

        $originalPath = $mediaFile->getPath();

        // Check if file is a processable image
        if (!$imageProcessor->isProcessableImage($originalPath)) {
            Log::info('ProcessImageJob: File is not a processable image', [
                'media_file_id' => $this->mediaFileId->getValue(),
                'path' => $originalPath->getValue(),
            ]);

            return;
        }

        // Get thumbnail size configurations
        $thumbnailConfigs = config('image.thumbnails', $this->getDefaultThumbnailConfigs());

        $processedFiles = [];

        try {
            // Create thumbnails
            foreach ($this->sizes as $sizeName) {
                if (!isset($thumbnailConfigs[$sizeName])) {
                    continue;
                }

                $config = $thumbnailConfigs[$sizeName];
                $thumbnailPath = $this->generateThumbnailPath($originalPath, $sizeName);

                $success = $imageProcessor->resize(
                    $originalPath,
                    $thumbnailPath,
                    $config['width'],
                    $config['height']
                );

                if ($success) {
                    $processedFiles[] = $thumbnailPath->getValue();

                    Log::debug('ProcessImageJob: Thumbnail created', [
                        'media_file_id' => $this->mediaFileId->getValue(),
                        'size' => $sizeName,
                        'path' => $thumbnailPath->getValue(),
                    ]);
                }
            }

            // Create WebP version if enabled
            if (config('image.create_webp', true)) {
                $webpPath = $this->generateWebPPath($originalPath);
                $quality = config('image.quality.webp', 85);

                $success = $imageProcessor->convertToWebP($originalPath, $webpPath, $quality);

                if ($success) {
                    $processedFiles[] = $webpPath->getValue();

                    Log::debug('ProcessImageJob: WebP version created', [
                        'media_file_id' => $this->mediaFileId->getValue(),
                        'path' => $webpPath->getValue(),
                    ]);
                }
            }

            // Optimize original if enabled
            if (config('image.auto_optimize', true)) {
                $quality = config('image.quality.jpeg', 90);
                $imageProcessor->optimize($originalPath, $quality);

                Log::debug('ProcessImageJob: Original optimized', [
                    'media_file_id' => $this->mediaFileId->getValue(),
                ]);
            }

            Log::info('ProcessImageJob: Image processed successfully', [
                'media_file_id' => $this->mediaFileId->getValue(),
                'original_path' => $originalPath->getValue(),
                'processed_files' => $processedFiles,
            ]);
        } catch (RuntimeException $e) {
            Log::error('ProcessImageJob: Processing failed', [
                'media_file_id' => $this->mediaFileId->getValue(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessImageJob: Job failed permanently', [
            'media_file_id' => $this->mediaFileId->getValue(),
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'media:' . $this->mediaFileId->getValue(),
            'image_processing',
        ];
    }

    /**
     * Generate thumbnail file path.
     */
    private function generateThumbnailPath(FilePath $original, string $sizeName): FilePath
    {
        $directory = $original->getDirectory();
        $name = $original->getNameWithoutExtension();
        $extension = $original->getExtension();

        return FilePath::fromString("{$directory}/{$name}_{$sizeName}.{$extension}");
    }

    /**
     * Generate WebP file path.
     */
    private function generateWebPPath(FilePath $original): FilePath
    {
        $directory = $original->getDirectory();
        $name = $original->getNameWithoutExtension();

        return FilePath::fromString("{$directory}/{$name}.webp");
    }

    /**
     * Get default thumbnail configurations.
     *
     * @return array<string, array{width: int, height: int}>
     */
    private function getDefaultThumbnailConfigs(): array
    {
        return [
            'thumb' => ['width' => 150, 'height' => 150],
            'small' => ['width' => 320, 'height' => 240],
            'medium' => ['width' => 640, 'height' => 480],
            'large' => ['width' => 1280, 'height' => 960],
        ];
    }
}