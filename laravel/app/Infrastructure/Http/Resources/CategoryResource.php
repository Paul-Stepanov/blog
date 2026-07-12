<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Resources;

use App\Application\Article\DTOs\CategoryDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Category Resource.
 *
 * Represents a single category.
 */
final class CategoryResource extends JsonResource
{
    public function __construct(CategoryDTO $resource)
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
        /** @var CategoryDTO $category */
        $category = $this->resource;

        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'created_at' => $category->createdAt,
            'updated_at' => $category->updatedAt,
        ];
    }
}
