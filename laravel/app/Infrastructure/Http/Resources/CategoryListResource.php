<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Resources;

use App\Application\Article\DTOs\CategoryListDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Category List Resource.
 *
 * Lightweight representation for category listings (admin panel, etc.).
 */
final class CategoryListResource extends JsonResource
{
    public function __construct(CategoryListDTO $resource)
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
        /** @var CategoryListDTO $category */
        $category = $this->resource;

        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'article_count' => $category->articleCount,
        ];
    }
}
