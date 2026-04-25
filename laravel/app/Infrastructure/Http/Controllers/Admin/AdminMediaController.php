<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Admin;

use App\Application\Media\Services\MediaService;
use App\Http\Controllers\Controller;
use App\Infrastructure\Http\Requests\Admin\MediaRequest;
use App\Infrastructure\Http\Resources\MediaResource;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Http\UploadedFile;

/**
 * Admin Media Management Controller.
 *
 * Handles media file operations in the admin panel.
 */
final class AdminMediaController extends Controller
{
    public function __construct(
        private readonly MediaService $mediaService,
    ) {}

    /**
     * Get a media file by ID.
     *
     * @OA\Get(
     *     path="/api/admin/media/{id}",
     *     summary="Get media file by ID (admin)",
     *     tags={"Admin Media"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Media file details"),
     *     @OA\Response(response=404, description="Media file not found")
     * )
     */
    public function getMediaFile(string $id): JsonResponse
    {
        try {
            $media = $this->mediaService->getFile($id);

            return response()->json([
                'success' => true,
                'data' => new MediaResource($media),
            ]);
        } catch (\App\Application\Media\Exceptions\MediaFileNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'media_not_found',
                'message' => "Media file not found with ID: {$id}",
            ], 404);
        }
    }

    /**
     * Upload a media file.
     *
     * @OA\Post(
     *     path="/api/admin/media/upload",
     *     summary="Upload media file",
     *     tags={"Admin Media"},
     *     @OA\RequestBody(required=true, @OA\MediaType(
     *         mediaType="multipart/form-data",
     *         @OA\Schema(
     *             @OA\Property(property="file", type="string", format="binary"),
     *             @OA\Property(property="alt_text", type="string")
     *         )
     *     )),
     *     @OA\Response(response=201, description="File uploaded successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function uploadFile(MediaRequest $request): JsonResponse
    {
        /** @var UploadedFile $file */
        $file = $request->file('file');

        try {
            $media = $this->mediaService->uploadFile(
                filename: $file->getClientOriginalName(),
                content: $file->getContent(),
                mimeTypeString: $file->getMimeType(),
                sizeBytes: $file->getSize(),
                width: $request->input('width'),
                height: $request->input('height'),
                altText: $request->input('alt_text', ''),
            );

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully.',
                'data' => new MediaResource($media),
            ], 201);
        } catch (\App\Application\Media\Exceptions\FileUploadFailedException $e) {
            return response()->json([
                'success' => false,
                'error' => 'upload_failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update media file alt text.
     *
     * @OA\Put(
     *     path="/api/admin/media/{id}/alt-text",
     *     summary="Update media alt text",
     *     tags={"Admin Media"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"alt_text"},
     *         @OA\Property(property="alt_text", type="string")
     *     )),
     *     @OA\Response(response=200, description="Alt text updated"),
     *     @OA\Response(response=404, description="Media file not found")
     * )
     */
    public function updateAltText(Request $request, string $id): JsonResponse
    {
        $request->validate(['alt_text' => 'required|string']);

        try {
            $media = $this->mediaService->updateAltText(
                fileId: $id,
                altText: $request->input('alt_text')
            );

            return response()->json([
                'success' => true,
                'message' => 'Alt text updated successfully.',
                'data' => new MediaResource($media),
            ]);
        } catch (\App\Application\Media\Exceptions\MediaFileNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'media_not_found',
                'message' => "Media file not found with ID: {$id}",
            ], 404);
        }
    }

    /**
     * Rename a media file.
     *
     * @OA\Put(
     *     path="/api/admin/media/{id}/rename",
     *     summary="Rename media file",
     *     tags={"Admin Media"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"filename"},
     *         @OA\Property(property="filename", type="string")
     *     )),
     *     @OA\Response(response=200, description="File renamed successfully"),
     *     @OA\Response(response=404, description="Media file not found")
     * )
     */
    public function renameFile(Request $request, string $id): JsonResponse
    {
        $request->validate(['filename' => 'required|string']);

        try {
            $media = $this->mediaService->renameFile(
                fileId: $id,
                newFilename: $request->input('filename')
            );

            return response()->json([
                'success' => true,
                'message' => 'File renamed successfully.',
                'data' => new MediaResource($media),
            ]);
        } catch (\App\Application\Media\Exceptions\MediaFileNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'media_not_found',
                'message' => "Media file not found with ID: {$id}",
            ], 404);
        }
    }

    /**
     * Delete a media file.
     *
     * @OA\Delete(
     *     path="/api/admin/media/{id}",
     *     summary="Delete media file",
     *     tags={"Admin Media"},
     *     @OA\Response(response=200, description="File deleted successfully"),
     *     @OA\Response(response=404, description="Media file not found")
     * )
     */
    public function deleteFile(string $id): JsonResponse
    {
        try {
            $this->mediaService->deleteFile($id);

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully.',
            ]);
        } catch (\App\Application\Media\Exceptions\MediaFileNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'media_not_found',
                'message' => "Media file not found with ID: {$id}",
            ], 404);
        }
    }
}