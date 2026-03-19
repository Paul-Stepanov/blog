<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers;

use App\Domain\Media\Services\FileStorageInterface;
use App\Domain\Media\ValueObjects\FilePath;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * File Download Controller.
 *
 * Handles private file downloads via signed URLs.
 * Uses X-Accel-Redirect for efficient file serving through Nginx.
 */
final readonly class FileDownloadController
{
    public function __construct(
        private FileStorageInterface $storage
    ) {}

    /**
     * Download a private file via signed URL.
     *
     * @param Request $request The HTTP request
     * @param string $path Base64-encoded file path
     * @return Response|StreamedResponse
     */
    public function download(Request $request, string $path): Response|StreamedResponse
    {
        // Validate signature
        if (!$request->hasValidSignature()) {
            abort(403, 'Invalid or expired download link');
        }

        // Decode path
        $decodedPath = base64_decode($path, strict: true);
        if ($decodedPath === false) {
            abort(400, 'Invalid file path encoding');
        }

        // Create FilePath value object (validates path)
        try {
            $filePath = FilePath::fromString('private/' . $decodedPath);
        } catch (\InvalidArgumentException $e) {
            abort(400, 'Invalid file path');
        }

        // Check file exists
        if (!$this->storage->exists($filePath)) {
            abort(404, 'File not found');
        }

        // Get MIME type and filename
        $mimeType = $this->storage->getMimeType($filePath);
        $filename = $filePath->getFilename();

        // Use X-Accel-Redirect for efficient file serving
        // Nginx handles the actual file transfer
        return response()->streamDownload(
            fn() => null, // Empty callback - Nginx handles the file
            $filename,
            [
                'Content-Type' => $mimeType->getValue(),
                'X-Accel-Redirect' => '/private-files/' . $decodedPath,
                'Cache-Control' => 'private, max-age=3600',
            ]
        );
    }

    /**
     * Stream a private file directly (alternative method).
     *
     * Use this when X-Accel-Redirect is not available.
     *
     * @param Request $request The HTTP request
     * @param string $path Base64-encoded file path
     * @return StreamedResponse
     */
    public function stream(Request $request, string $path): StreamedResponse
    {
        // Validate signature
        if (!$request->hasValidSignature()) {
            abort(403, 'Invalid or expired download link');
        }

        // Decode path
        $decodedPath = base64_decode($path, strict: true);
        if ($decodedPath === false) {
            abort(400, 'Invalid file path encoding');
        }

        // Create FilePath value object
        try {
            $filePath = FilePath::fromString('private/' . $decodedPath);
        } catch (\InvalidArgumentException) {
            abort(400, 'Invalid file path');
        }

        // Check file exists
        if (!$this->storage->exists($filePath)) {
            abort(404, 'File not found');
        }

        $mimeType = $this->storage->getMimeType($filePath);
        $filename = $filePath->getFilename();

        return response()->stream(
            function () use ($filePath): void {
                echo $this->storage->get($filePath);
            },
            200,
            [
                'Content-Type' => $mimeType->getValue(),
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Cache-Control' => 'private, max-age=3600',
            ]
        );
    }
}