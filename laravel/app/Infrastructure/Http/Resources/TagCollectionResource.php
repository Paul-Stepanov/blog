<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Resources;

use App\Domain\Article\Entities\Tag;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Tag Collection Resource.
 *
 * Transforms array of Tag entities for API response.
 */
final class TagCollectionResource extends JsonResource
{
    /**
     * @param array<Tag> $tags
     */
    public function __construct(private readonly array $tags)
    {
        parent::__construct($tags);
    }

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<int, array<string, mixed>>
     */
    public function toArray($request): array
    {
        return array_map(
            static fn(Tag $tag) => [
                'id' => $tag->getId()->getValue(),
                'name' => $tag->getName(),
                'slug' => $tag->getSlug()->getValue(),
            ],
            $this->tags
        );
    }
}