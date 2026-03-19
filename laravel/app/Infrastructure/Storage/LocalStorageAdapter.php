<?php

declare(strict_types=1);

namespace App\Infrastructure\Storage;

use App\Domain\Media\Services\FileStorageInterface;
use App\Domain\Media\ValueObjects\FilePath;
use App\Domain\Media\ValueObjects\MimeType;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use RuntimeException;

/**
 * Local Storage Adapter.
 *
 * Implements FileStorageInterface using Laravel's local filesystem.
 * Supports both public and private storage disks.
 */
final readonly class LocalStorageAdapter implements FileStorageInterface
{
    private const PUBLIC_DISK = 'public';
    private const PRIVATE_DISK = 'private';
    private const PUBLIC_PATH_PREFIX = 'public/';
    private const PRIVATE_PATH_PREFIX = 'private/';

    /**
     * @inheritDoc
     */
    public function store(string $content, FilePath $path, MimeType $mimeType): bool
    {
        $disk = $this->resolveDisk($path);
        $relativePath = $this->stripPrefix($path->getValue());

        return Storage::disk($disk)->put($relativePath, $content);
    }

    /**
     * @inheritDoc
     */
    public function storeFromTemp(string $tempPath, FilePath $targetPath): bool
    {
        $disk = $this->resolveDisk($targetPath);
        $relativePath = $this->stripPrefix($targetPath->getValue());

        $stream = fopen($tempPath, 'rb');
        if ($stream === false) {
            return false;
        }

        $result = Storage::disk($disk)->put($relativePath, $stream);
        fclose($stream);

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function get(FilePath $path): string
    {
        $disk = $this->resolveDisk($path);
        $relativePath = $this->stripPrefix($path->getValue());

        $content = Storage::disk($disk)->get($relativePath);

        if ($content === null) {
            throw new RuntimeException("File not found: {$path->getValue()}");
        }

        return $content;
    }

    /**
     * @inheritDoc
     */
    public function exists(FilePath $path): bool
    {
        $disk = $this->resolveDisk($path);
        $relativePath = $this->stripPrefix($path->getValue());

        return Storage::disk($disk)->exists($relativePath);
    }

    /**
     * @inheritDoc
     */
    public function delete(FilePath $path): bool
    {
        $disk = $this->resolveDisk($path);
        $relativePath = $this->stripPrefix($path->getValue());

        return Storage::disk($disk)->delete($relativePath);
    }

    /**
     * @inheritDoc
     */
    public function move(FilePath $from, FilePath $to): bool
    {
        $fromDisk = $this->resolveDisk($from);
        $toDisk = $this->resolveDisk($to);

        if ($fromDisk !== $toDisk) {
            // Cross-disk move: copy + delete
            $content = $this->get($from);
            $stored = Storage::disk($toDisk)->put($this->stripPrefix($to->getValue()), $content);

            if (!$stored) {
                return false;
            }

            return $this->delete($from);
        }

        return Storage::disk($fromDisk)->move(
            $this->stripPrefix($from->getValue()),
            $this->stripPrefix($to->getValue())
        );
    }

    /**
     * @inheritDoc
     */
    public function copy(FilePath $from, FilePath $to): bool
    {
        $fromDisk = $this->resolveDisk($from);
        $toDisk = $this->resolveDisk($to);

        if ($fromDisk !== $toDisk) {
            $content = $this->get($from);

            return Storage::disk($toDisk)->put($this->stripPrefix($to->getValue()), $content);
        }

        return Storage::disk($fromDisk)->copy(
            $this->stripPrefix($from->getValue()),
            $this->stripPrefix($to->getValue())
        );
    }

    /**
     * @inheritDoc
     */
    public function size(FilePath $path): int
    {
        $disk = $this->resolveDisk($path);
        $relativePath = $this->stripPrefix($path->getValue());

        return Storage::disk($disk)->size($relativePath);
    }

    /**
     * @inheritDoc
     */
    public function lastModified(FilePath $path): \DateTimeInterface
    {
        $disk = $this->resolveDisk($path);
        $relativePath = $this->stripPrefix($path->getValue());

        $timestamp = Storage::disk($disk)->lastModified($relativePath);

        return (new \DateTimeImmutable())->setTimestamp($timestamp);
    }

    /**
     * @inheritDoc
     */
    public function getUrl(FilePath $path): string
    {
        // Only works for public files
        if (!$this->isPublic($path)) {
            throw new RuntimeException('Cannot get public URL for private file. Use getTemporaryUrl() instead.');
        }

        $relativePath = $this->stripPrefix($path->getValue());

        return Storage::disk(self::PUBLIC_DISK)->url($relativePath);
    }

    /**
     * @inheritDoc
     */
    public function getTemporaryUrl(FilePath $path, int $expirationMinutes = 60): string
    {
        // For public files, return permanent URL
        if ($this->isPublic($path)) {
            return $this->getUrl($path);
        }

        $relativePath = $this->stripPrefix($path->getValue());
        $encodedPath = base64_encode($relativePath);

        return URL::temporarySignedRoute(
            'files.download',
            now()->addMinutes($expirationMinutes),
            ['path' => $encodedPath]
        );
    }

    /**
     * @inheritDoc
     */
    public function isPublic(FilePath $path): bool
    {
        return str_starts_with($path->getValue(), self::PUBLIC_PATH_PREFIX);
    }

    /**
     * @inheritDoc
     */
    public function setVisibility(FilePath $path, bool $public): bool
    {
        $currentIsPublic = $this->isPublic($path);

        // No change needed
        if ($currentIsPublic === $public) {
            return true;
        }

        // Build new path with correct prefix
        $relativePath = $this->stripPrefix($path->getValue());
        $newPrefix = $public ? self::PUBLIC_PATH_PREFIX : self::PRIVATE_PATH_PREFIX;
        $newPath = FilePath::fromString($newPrefix . $relativePath);

        // Visibility change requires move between disks
        return $this->move($path, $newPath);
    }

    /**
     * @inheritDoc
     */
    public function getMimeType(FilePath $path): MimeType
    {
        $disk = $this->resolveDisk($path);
        $relativePath = $this->stripPrefix($path->getValue());

        $mimeType = Storage::disk($disk)->mimeType($relativePath);

        return MimeType::fromString($mimeType);
    }

    /**
     * @inheritDoc
     */
    public function generateUniquePath(string $originalName, MimeType $mimeType): FilePath
    {
        return FilePath::generateForUpload(
            directory: 'public/uploads',
            filename: $originalName
        );
    }

    /**
     * @inheritDoc
     */
    public function getTotalUsage(): int
    {
        $publicSize = $this->getDirectorySize(storage_path('app/public'));
        $privateSize = $this->getDirectorySize(storage_path('app/private'));

        return $publicSize + $privateSize;
    }

    /**
     * @inheritDoc
     */
    public function getAvailableSpace(): int
    {
        $freeSpace = disk_free_space(storage_path('app'));

        return $freeSpace !== false ? (int) $freeSpace : -1;
    }

    /**
     * Resolve storage disk based on path prefix.
     */
    private function resolveDisk(FilePath $path): string
    {
        return $this->isPublic($path) ? self::PUBLIC_DISK : self::PRIVATE_DISK;
    }

    /**
     * Strip public/private prefix from path.
     */
    private function stripPrefix(string $path): string
    {
        if (str_starts_with($path, self::PUBLIC_PATH_PREFIX)) {
            return substr($path, strlen(self::PUBLIC_PATH_PREFIX));
        }

        if (str_starts_with($path, self::PRIVATE_PATH_PREFIX)) {
            return substr($path, strlen(self::PRIVATE_PATH_PREFIX));
        }

        // Default to public for backwards compatibility
        return $path;
    }

    /**
     * Calculate total size of a directory.
     */
    private function getDirectorySize(string $directory): int
    {
        if (!is_dir($directory)) {
            return 0;
        }

        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }
}