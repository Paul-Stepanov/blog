<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Resources;

use App\Application\Media\DTOs\MediaFileDTO;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Media File Resource.
 *
 * Represents a single media file.
 */
final class MediaResource extends JsonResource
{
    /**
     * @param MediaFileDTO $resource
     */
    public function __construct(MediaFileDTO $resource)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var MediaFileDTO $media */
        $media = $this->resource;

        $data = [
            'id' => $media->id,
            'filename' => $media->filename,
            'path' => $media->path,
            'public_url' => $media->publicUrl,
            'mime_type' => $media->mimeType,
            'size_bytes' => $media->sizeBytes,
            'alt_text' => $media->altText,
            'created_at' => $media->createdAt,
            'updated_at' => $media->updatedAt,
        ];

        // Add dimensions for images
        if ($media->width !== null && $media->height !== null) {
            $data['dimensions'] = [
                'width' => $media->width,
                'height' => $media->height,
                'aspect_ratio' => $media->width / $media->height,
            ];
        }

        return $data;
    }
}