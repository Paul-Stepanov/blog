<?php

declare(strict_types=1);

namespace App\Domain\Article\Entities;

use App\Domain\Article\ValueObjects\{ArticleContent, ArticleStatus, Slug};
use App\Domain\Shared\Entity;
use App\Domain\Shared\Exceptions\ValidationException;
use App\Domain\Shared\Timestamps;
use App\Domain\Shared\Uuid;
use App\Domain\Shared\DomainEvent;
use DateTimeImmutable;

/**
 * Article Entity - Aggregate Root.
 *
 * Articles are mutable entities that can change state through their lifecycle:
 * Draft → Published → Archived
 */
final class Article extends Entity
{
    /** @var DomainEvent[] */
    private array $events = [];

    private readonly ?Uuid $authorId;
    private string $title;
    private Slug $slug;
    private ArticleContent $content;
    private string $excerpt;
    private ArticleStatus $status;
    private ?Uuid $categoryId;
    private ?Uuid $coverImageId;
    private ?DateTimeImmutable $publishedAt;
    private Timestamps $timestamps;

    public function __construct(
        Uuid $id,
        string $title,
        Slug $slug,
        ArticleContent $content,
        string $excerpt,
        ArticleStatus $status,
        ?Uuid $categoryId,
        ?Uuid $authorId,
        ?Uuid $coverImageId,
        ?DateTimeImmutable $publishedAt,
        Timestamps $timestamps,
    ) {
        parent::__construct($id);

        $this->title = $title;
        $this->slug = $slug;
        $this->content = $content;
        $this->excerpt = $excerpt;
        $this->status = $status;
        $this->categoryId = $categoryId;
        $this->authorId = $authorId;
        $this->coverImageId = $coverImageId;
        $this->publishedAt = $publishedAt;
        $this->timestamps = $timestamps;
    }

    /**
     * Create a new draft article.
     */
    public static function createDraft(
        Uuid $id,
        string $title,
        Slug $slug,
        ArticleContent $content,
        ?Uuid $categoryId = null,
        ?Uuid $authorId = null,
    ): self {
        $excerpt = $content->getExcerpt(200);

        return new self(
            id: $id,
            title: $title,
            slug: $slug,
            content: $content,
            excerpt: $excerpt,
            status: ArticleStatus::DRAFT,
            categoryId: $categoryId,
            authorId: $authorId,
            coverImageId: null,
            publishedAt: null,
            timestamps: Timestamps::now(),
        );
    }

    /**
     * Reconstruct from persistence.
     */
    public static function reconstitute(
        Uuid $id,
        string $title,
        Slug $slug,
        ArticleContent $content,
        string $excerpt,
        ArticleStatus $status,
        ?Uuid $categoryId,
        ?Uuid $authorId,
        ?Uuid $coverImageId,
        ?DateTimeImmutable $publishedAt,
        Timestamps $timestamps,
    ): self {
        return new self(
            id: $id,
            title: $title,
            slug: $slug,
            content: $content,
            excerpt: $excerpt,
            status: $status,
            categoryId: $categoryId,
            authorId: $authorId,
            coverImageId: $coverImageId,
            publishedAt: $publishedAt,
            timestamps: $timestamps,
        );
    }

    /**
     * Publish the article.
     *
     * @throws ValidationException
     */
    public function publish(): void
    {
        if (!$this->status->canBePublished()) {
            throw ValidationException::forField('status', 'Article cannot be published from current status');
        }

        if ($this->content->isEmpty()) {
            throw ValidationException::forField('content', 'Cannot publish article with empty content');
        }

        if (empty(trim($this->title))) {
            throw ValidationException::forField('title', 'Cannot publish article without title');
        }

        $this->status = ArticleStatus::PUBLISHED;
        $this->publishedAt = new DateTimeImmutable();
        $this->timestamps = $this->timestamps->touch();
    }

    /**
     * Archive the article.
     *
     * @throws ValidationException
     */
    public function archive(): void
    {
        if (!$this->status->canBeArchived()) {
            throw ValidationException::forField('status', 'Article cannot be archived from current status');
        }

        $this->status = ArticleStatus::ARCHIVED;
        $this->timestamps = $this->timestamps->touch();
    }

    /**
     * Revert to draft.
     *
     * @throws ValidationException
     */
    public function revertToDraft(): void
    {
        if ($this->status === ArticleStatus::DRAFT) {
            return;
        }

        $this->status = ArticleStatus::DRAFT;
        $this->timestamps = $this->timestamps->touch();
    }

    /**
     * Update article content.
     *
     * @throws ValidationException
     */
    public function updateContent(
        string $title,
        ArticleContent $content,
        ?Slug $newSlug = null,
    ): void {
        if (!$this->status->isEditable()) {
            throw ValidationException::forField('status', 'Article cannot be edited in current status');
        }

        $this->title = $title;
        $this->content = $content;
        $this->excerpt = $content->getExcerpt(200);
        $this->timestamps = $this->timestamps->touch();

        if ($newSlug !== null) {
            $this->slug = $newSlug;
        }
    }

    /**
     * Change category.
     */
    public function changeCategory(?Uuid $categoryId): void
    {
        $this->categoryId = $categoryId;
        $this->timestamps = $this->timestamps->touch();
    }

    /**
     * Set cover image.
     */
    public function setCoverImage(?Uuid $coverImageId): void
    {
        $this->coverImageId = $coverImageId;
        $this->timestamps = $this->timestamps->touch();
    }

    /**
     * Release and clear domain events.
     *
     * @return DomainEvent[]
     */
    public function releaseEvents(): array
    {
        $events = $this->events;
        $this->events = [];
        return $events;
    }

    // Getters

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSlug(): Slug
    {
        return $this->slug;
    }

    public function getContent(): ArticleContent
    {
        return $this->content;
    }

    public function getExcerpt(): string
    {
        return $this->excerpt;
    }

    public function getStatus(): ArticleStatus
    {
        return $this->status;
    }

    public function getCategoryId(): ?Uuid
    {
        return $this->categoryId;
    }

    public function getAuthorId(): ?Uuid
    {
        return $this->authorId;
    }

    public function getCoverImageId(): ?Uuid
    {
        return $this->coverImageId;
    }

    public function getPublishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function getTimestamps(): Timestamps
    {
        return $this->timestamps;
    }

    public function isPublished(): bool
    {
        return $this->status->isPublic();
    }
}