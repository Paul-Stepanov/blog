<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Resources;

use App\Application\Media\DTOs\MediaFileDTO;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Media Collection Resource.
 *
 * Collection representation of media files.
 */
final class MediaCollectionResource extends JsonResource
{
    /**
     * @param LengthAwarePaginator<MediaFileDTO>|array<MediaFileDTO> $resource
     */
    public function __construct($resource)
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
        if ($this->resource instanceof LengthAwarePaginator) {
            return [
                'data' => MediaResource::collection($this->resource->items()),
                'meta' => [
                    'current_page' => $this->resource->currentPage(),
                    'last_page' => $this->resource->lastPage(),
                    'per_page' => $this->resource->perPage(),
                    'total' => $this->resource->total(),
                    'from' => $this->resource->firstItem(),
                    'to' => $this->resource->lastItem(),
                ],
            ];
        }

        return [
            'data' => MediaResource::collection($this->resource),
        ];
    }
}