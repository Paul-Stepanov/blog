<?php

declare(strict_types=1);

namespace App\Application\Media\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

/**
 * Exception thrown when a media file is not found.
 */
final class MediaFileNotFoundException extends ApplicationException
{
    /**
     * @param string $identifier File ID that was not found
     */
    public static function byId(string $identifier): self
    {
        return new self("Media file not found: {$identifier}");
    }

    /**
     * @param string $path File path that was not found
     */
    public static function byPath(string $path): self
    {
        return new self("Media file not found at path: {$path}");
    }

    /**
     * @return non-empty-string
     */
    public function getErrorType(): string
    {
        return 'media_file_not_found';
    }

    /**
     * @return array<string, string>
     */
    public function getContext(): array
    {
        return ['resource' => 'media_file'];
    }
}