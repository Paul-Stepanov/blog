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

        return [
            'id' => $article->id,
            'title' => $article->title,
            'slug' => $article->slug,
            'content' => $article->content,
            'excerpt' => $article->excerpt,
            'status' => $article->status,
            'category' => $article->category ? [
                'id' => $article->category['id'],
                'name' => $article->category['name'],
                'slug' => $article->category['slug'],
            ] : null,
            'tags' => array_map(fn(array $tag) => [
                'id' => $tag['id'],
                'name' => $tag['name'],
                'slug' => $tag['slug'],
            ], $article->tags),
            'author' => [
                'id' => $article->author['id'],
                'name' => $article->author['name'],
                'email' => $article->author['email'],
            ],
            'cover_image' => $article->coverImage ? [
                'id' => $article->coverImage['id'],
                'url' => $article->coverImage['url'],
                'alt_text' => $article->coverImage['alt_text'],
            ] : null,
            'published_at' => $article->publishedAt,
            'created_at' => $article->createdAt,
            'updated_at' => $article->updatedAt,
            'reading_time' => $article->readingTime,
            'word_count' => $article->wordCount,
        ];
    }
}