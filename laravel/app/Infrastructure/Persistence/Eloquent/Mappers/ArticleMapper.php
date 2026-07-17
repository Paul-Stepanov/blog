<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Mappers;

use App\Domain\Article\Entities\Article;
use App\Domain\Article\ValueObjects\ArticleContent;
use App\Domain\Article\ValueObjects\ArticleReadContext;
use App\Domain\Article\ValueObjects\ArticleStatus;
use App\Infrastructure\Persistence\Eloquent\Models\ArticleModel;
use App\Infrastructure\Persistence\Eloquent\Models\CategoryModel;
use App\Infrastructure\Persistence\Eloquent\Models\MediaFileModel;
use App\Infrastructure\Persistence\Eloquent\Models\TagModel;
use App\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Support\Facades\Storage;

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
            readContext: $this->buildReadContext($model),
        );
    }

    /**
     * Build the read-side snapshot from eager-loaded relations.
     *
     * Falls back to an empty snapshot when relations are not loaded (write path),
     * so the mapper is safe to use outside read queries.
     */
    private function buildReadContext(ArticleModel $model): ArticleReadContext
    {
        $category = null;
        if ($model->relationLoaded('category')) {
            $categoryModel = $model->category;
            if ($categoryModel instanceof CategoryModel) {
                $category = [
                    'name' => $categoryModel->name,
                    'slug' => $categoryModel->slug->getValue(),
                ];
            }
        }

        $tags = [];
        if ($model->relationLoaded('tags')) {
            foreach ($model->tags as $tag) {
                if (! $tag instanceof TagModel) {
                    continue;
                }
                $tags[] = [
                    'name' => $tag->name,
                    'slug' => $tag->slug->getValue(),
                ];
            }
        }

        $author = null;
        if ($model->relationLoaded('author')) {
            $authorModel = $model->author;
            if ($authorModel instanceof UserModel) {
                $author = ['name' => $authorModel->name];
            }
        }

        $coverImageUrl = null;
        if ($model->relationLoaded('coverImage')) {
            $coverModel = $model->coverImage;
            if ($coverModel instanceof MediaFileModel) {
                $coverImageUrl = (string) Storage::disk('public')->url($coverModel->path);
            }
        }

        return new ArticleReadContext(
            category: $category,
            tags: $tags,
            author: $author,
            coverImageUrl: $coverImageUrl,
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
     * @param  ArticleModel[]  $models
     * @return Article[]
     */
    public function toDomainCollection(array $models): array
    {
        return array_map(
            fn (ArticleModel $model): Article => $this->toDomain($model),
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
