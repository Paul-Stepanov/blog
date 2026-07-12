<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Resources;

use App\Application\Article\DTOs\TagDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Tag Resource.
 *
 * Represents a single tag.
 */
final class TagResource extends JsonResource
{
    public function __construct(TagDTO $resource)
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
        /** @var TagDTO $tag */
        $tag = $this->resource;

        return [
            'id' => $tag->id,
            'name' => $tag->name,
            'slug' => $tag->slug,
            'created_at' => $tag->createdAt,
            'updated_at' => $tag->updatedAt,
        ];
    }
}
