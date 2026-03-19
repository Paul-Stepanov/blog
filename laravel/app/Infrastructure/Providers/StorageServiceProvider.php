<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use App\Domain\Media\Services\FileStorageInterface;
use App\Domain\Media\Services\ImageProcessorInterface;
use App\Infrastructure\Storage\InterventionImageProcessor;
use App\Infrastructure\Storage\LocalStorageAdapter;
use Illuminate\Support\ServiceProvider;

/**
 * Storage Service Provider.
 *
 * Registers storage and image processing services in the DI container.
 */
final class StorageServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // File Storage - singleton for performance
        $this->app->singleton(FileStorageInterface::class, LocalStorageAdapter::class);

        // Alias for convenience
        $this->app->alias(FileStorageInterface::class, 'storage.files');

        // Image Processor - singleton with configured driver
        $this->app->singleton(ImageProcessorInterface::class, function (): InterventionImageProcessor {
            $driver = config('image.driver', 'gd');

            return new InterventionImageProcessor($driver);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Ensure storage directories exist
        $this->ensureStorageDirectories();

        // Publish configuration
        $this->publishes([
            __DIR__ . '/../../config/image.php' => config_path('image.php'),
        ], 'config');
    }

    /**
     * Ensure required storage directories exist.
     */
    private function ensureStorageDirectories(): void
    {
        $directories = [
            storage_path('app/public/uploads'),
            storage_path('app/public/uploads/images'),
            storage_path('app/public/uploads/documents'),
            storage_path('app/private/uploads'),
            storage_path('app/private/uploads/images'),
            storage_path('app/private/uploads/documents'),
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
        }
    }
}