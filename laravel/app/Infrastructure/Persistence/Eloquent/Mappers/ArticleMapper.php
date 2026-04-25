<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Mappers;

use App\Domain\Article\Entities\Article;
use App\Domain\Article\ValueObjects\{ArticleContent, ArticleStatus};
use App\Infrastructure\Persistence\Eloquent\Models\ArticleModel;

/**
 * Mapper for Article Entity <-> ArticleModel.
 */
final class ArticleMapper
{
    use BaseMapper;

    /**
     * Convert Eloquent Model to Domain Entity.
     */
    public function toDomain(ArticleModel $model): Article
    {
        return Article::reconstitute(
            id: $this->mapUuid($model->uuid),
            title: $model->title,
            slug: $this->mapSlug($model->slug),
            content: $this->mapContent($model->content),
            excerpt: $model->excerpt ?? '',
            status: $this->mapStatus($model->status),
            categoryId: $this->mapNullableUuid($model->category_uuid),
            authorId: $this->mapNullableUuid($model->author_uuid),
            coverImageId: $this->mapNullableUuid($model->cover_image_uuid),
            publishedAt: $this->parseDateTime($model->published_at?->toDateTimeString()),
            timestamps: $this->mapTimestamps($model),
        );
    }

    /**
     * Convert Domain Entity to Eloquent data array.
     *
     * @return array<string, mixed>
     */
    public function toEloquent(Article $entity): array
    {
        return [
            'uuid' => $entity->getId()->getValue(),
            'title' => $entity->getTitle(),
            'slug' => $this->getSlugValue($entity->getSlug()),
            'content' => $entity->getContent()->getValue(),
            'excerpt' => $entity->getExcerpt(),
            'status' => $entity->getStatus()->value,
            'category_uuid' => $this->getUuidValue($entity->getCategoryId()),
            'author_uuid' => $this->getUuidValue($entity->getAuthorId()),
            'cover_image_uuid' => $this->getUuidValue($entity->getCoverImageId()),
            'published_at' => $this->formatDateTime($entity->getPublishedAt()),
        ];
    }

    /**
     * Convert collection of models to domain entities.
     *
     * @param ArticleModel[] $models
     * @return Article[]
     */
    public function toDomainCollection(array $models): array
    {
        return array_map(
            fn(ArticleModel $model): Article => $this->toDomain($model),
            $models
        );
    }

    /**
     * Map content string to ArticleContent VO.
     */
    private function mapContent(string $content): ArticleContent
    {
        return ArticleContent::fromString($content);
    }

    /**
     * Map status string to ArticleStatus enum.
     */
    private function mapStatus(ArticleStatus|string $status): ArticleStatus
    {
        if ($status instanceof ArticleStatus) {
            return $status;
        }

        return ArticleStatus::fromString($status);
    }
}