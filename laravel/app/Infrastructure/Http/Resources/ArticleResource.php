<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Resources;

use App\Application\Article\DTOs\ArticleDTO;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Article Resource.
 *
 * Full representation of an article with all details.
 */
final class ArticleResource extends JsonResource
{
    /**
     * @param ArticleDTO $resource
     */
    public function __construct(ArticleDTO $resource)
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
        /** @var ArticleDTO $article */
        $article = $this->resource;

        return $article->toArray();
    }
}