<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Resources;

use App\Application\Media\DTOs\MediaFileDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Media File Resource.
 *
 * Represents a single media file.
 */
final class MediaResource extends JsonResource
{
    public function __construct(MediaFileDTO $resource)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var MediaFileDTO $media */
        $media = $this->resource;

        $data = [
            'id' => $media->id,
            'file_name' => $media->filename,
            'file_path' => $media->path,
            'public_url' => $media->publicUrl,
            'mime_type' => $media->mimeType,
            'file_size' => $media->sizeBytes,
            'size_human' => $media->sizeHuman,
            'width' => $media->width,
            'height' => $media->height,
            'alt_text' => $media->altText,
            'is_image' => $media->isImage,
            'created_at' => $media->createdAt,
            'updated_at' => $media->updatedAt,
        ];

        return $data;
    }
}
