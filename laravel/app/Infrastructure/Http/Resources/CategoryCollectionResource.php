<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Resources;

use App\Domain\Article\Entities\Category;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Category Collection Resource.
 *
 * Transforms array of Category entities for API response.
 */
final class CategoryCollectionResource extends JsonResource
{
    /**
     * @param array<Category> $categories
     */
    public function __construct(private readonly array $categories)
    {
        parent::__construct($categories);
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
            static fn(Category $category) => [
                'id' => $category->getId()->getValue(),
                'name' => $category->getName(),
                'slug' => $category->getSlug()->getValue(),
                'description' => $category->getDescription(),
            ],
            $this->categories
        );
    }
}
