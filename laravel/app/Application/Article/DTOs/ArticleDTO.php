<?php

declare(strict_types=1);

namespace App\Application\Article\DTOs;

use App\Application\Shared\{DTOFormattingTrait, DTOInterface};
use App\Application\Shared\Exceptions\InvalidEntityTypeException;
use App\Domain\Article\Entities\Article;
use App\Domain\Shared\Entity;

/**
 * Article Data Transfer Object.
 *
 * Represents a complete article for transfer between layers.
 * Immutable and serializable for API responses.
 */
final readonly class ArticleDTO implements DTOInterface
{
    use DTOFormattingTrait;

    /**
     * @param string $id UUID string
     * @param string $title Article title
     * @param string $slug URL-friendly identifier
     * @param string $content HTML content
     * @param string $excerpt Short preview text
     * @param string $status draft|published|archived
     * @param string|null $categoryId Category UUID or null
     * @param string|null $authorId Author UUID or null
     * @param string|null $coverImageId Cover image UUID or null
     * @param string|null $publishedAt ISO 8601 datetime or null
     * @param string $createdAt ISO 8601 datetime
     * @param string $updatedAt ISO 8601 datetime
     * @param int $wordCount Content word count
     * @param int $readingTime Estimated reading time in minutes
     */
    public function __construct(
        public string $id,
        public string $title,
        public string $slug,
        public string $content,
        public string $excerpt,
        public string $status,
        public ?string $categoryId,
        public ?string $authorId,
        public ?string $coverImageId,
        public ?string $publishedAt,
        public string $createdAt,
        public string $updatedAt,
        public int $wordCount,
        public int $readingTime,
    ) {}

    /**
     * Create from Domain Entity.
     *
     * @param Entity $entity Domain article entity
     */
    public static function fromEntity(Entity $entity): static
    {
        if (!$entity instanceof Article) {
            throw new InvalidEntityTypeException(
                expectedType: Article::class,
                actualType: $entity::class
            );
        }

        $timestamps = $entity->getTimestamps();
        $content = $entity->getContent();

        return new self(
            id: $entity->getId()->getValue(),
            title: $entity->getTitle(),
            slug: $entity->getSlug()->getValue(),
            content: $content->getValue(),
            excerpt: $entity->getExcerpt(),
            status: $entity->getStatus()->value,
            categoryId: self::formatUuid($entity->getCategoryId()),
            authorId: self::formatUuid($entity->getAuthorId()),
            coverImageId: self::formatUuid($entity->getCoverImageId()),
            publishedAt: self::formatDate($entity->getPublishedAt()),
            createdAt: self::formatDate($timestamps->getCreatedAt()),
            updatedAt: self::formatDate($timestamps->getUpdatedAt()),
            wordCount: $content->wordCount(),
            readingTime: $content->readingTime(),
        );
    }

    /**
     * Convert DTO to associative array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'status' => $this->status,
            'category_id' => $this->categoryId,
            'author_id' => $this->authorId,
            'cover_image_id' => $this->coverImageId,
            'published_at' => $this->publishedAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'word_count' => $this->wordCount,
            'reading_time' => $this->readingTime,
            'reading_time_text' => self::getReadingTimeText($this->readingTime),
        ];
    }

    /**
     * Check if article is published.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * Check if article is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if article is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }
}