<?php

declare(strict_types=1);

namespace App\Application\Media\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

/**
 * Exception thrown when file upload fails.
 */
final class FileUploadFailedException extends ApplicationException
{
    /**
     * @param string $filename File that failed to upload
     * @param string $reason Failure reason
     */
    public static function storageFailed(string $filename, string $reason = ''): self
    {
        $message = "Failed to store file: {$filename}";
        if ($reason !== '') {
            $message .= " - {$reason}";
        }

        return new self($message);
    }

    /**
     * @param string $filename File with invalid type
     * @param string $mimeType Invalid MIME type
     */
    public static function invalidType(string $filename, string $mimeType): self
    {
        return new self("Invalid file type '{$mimeType}' for file: {$filename}");
    }

    /**
     * @param string $filename File that is too large
     * @param int $sizeBytes Actual file size
     * @param int $maxBytes Maximum allowed size
     */
    public static function tooLarge(string $filename, int $sizeBytes, int $maxBytes): self
    {
        return new self(
            "File '{$filename}' is too large: {$sizeBytes} bytes (max: {$maxBytes} bytes)"
        );
    }

    /**
     * @return non-empty-string
     */
    public function getErrorType(): string
    {
        return 'file_upload_failed';
    }

    /**
     * @return array<string, string>
     */
    public function getContext(): array
    {
        return ['resource' => 'media_file', 'operation' => 'upload'];
    }
}