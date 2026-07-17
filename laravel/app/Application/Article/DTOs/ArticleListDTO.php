<?php

declare(strict_types=1);

namespace App\Application\Article\DTOs;

use App\Application\Shared\DTOFormattingTrait;
use App\Application\Shared\DTOInterface;
use App\Application\Shared\Exceptions\InvalidEntityTypeException;
use App\Domain\Article\Entities\Article;
use App\Domain\Shared\Entity;

/**
 * Article List Data Transfer Object.
 *
 * Lightweight representation of an article for list views.
 * Contains only essential fields needed for article listings.
 */
final readonly class ArticleListDTO implements DTOInterface
{
    use DTOFormattingTrait;

    /**
     * @param  string  $id  UUID string
     * @param  string  $title  Article title
     * @param  string  $slug  URL-friendly identifier
     * @param  string  $excerpt  Short preview text
     * @param  string  $status  draft|published|archived
     * @param  string|null  $categoryId  Category UUID or null
     * @param  string|null  $publishedAt  ISO 8601 datetime or null
     * @param  int  $readingTime  Estimated reading time in minutes
     */
    public function __construct(
        public string $id,
        public string $title,
        public string $slug,
        public string $excerpt,
        public string $status,
        public ?string $categoryId,
        public ?string $publishedAt,
        public int $readingTime,
        public string $createdAt,
        public string $updatedAt,
        public ?array $category = null,
        /** @var list<array{name: string, slug: string}> */
        public array $tags = [],
        public ?string $coverImageUrl = null,
    ) {}

    /**
     * Create from Domain Entity.
     *
     * @param  Entity  $entity  Domain article entity
     */
    public static function fromEntity(Entity $entity): static
    {
        if (! $entity instanceof Article) {
            throw new InvalidEntityTypeException(
                expectedType: Article::class,
                actualType: $entity::class
            );
        }

        $content = $entity->getContent();
        $timestamps = $entity->getTimestamps();
        $readContext = $entity->getReadContext();

        return new self(
            id: $entity->getId()->getValue(),
            title: $entity->getTitle(),
            slug: $entity->getSlug()->getValue(),
            excerpt: $entity->getExcerpt(),
            status: $entity->getStatus()->value,
            categoryId: self::formatUuid($entity->getCategoryId()),
            publishedAt: self::formatDate($entity->getPublishedAt()),
            readingTime: $content->readingTime(),
            createdAt: self::formatDate($timestamps->getCreatedAt()),
            updatedAt: self::formatDate($timestamps->getUpdatedAt()),
            category: $readContext->category,
            tags: $readContext->tags,
            coverImageUrl: $readContext->coverImageUrl,
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
            'excerpt' => $this->excerpt,
            'status' => $this->status,
            'category_id' => $this->categoryId,
            'category' => $this->category,
            'tags' => $this->tags,
            'cover_image_url' => $this->coverImageUrl,
            'published_at' => $this->publishedAt,
            'reading_time' => $this->readingTime,
            'reading_time_text' => self::getReadingTimeText($this->readingTime),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
