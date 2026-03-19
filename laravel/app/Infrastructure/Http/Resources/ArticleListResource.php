<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Resources;

use App\Application\Article\DTOs\ArticleListDTO;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Article List Resource.
 *
 * Lightweight representation for article listings.
 */
final class ArticleListResource extends JsonResource
{
    /**
     * @param ArticleListDTO $resource
     */
    public function __construct(ArticleListDTO $resource)
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
        /** @var ArticleListDTO $article */
        $article = $this->resource;

        return [
            'id' => $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'excerpt' => $article->excerpt,
            'status' => $article->status,
            'category_id' => $article->categoryId,
            'published_at' => $article->publishedAt,
            'reading_time' => $article->readingTime,
        ];
    }
}