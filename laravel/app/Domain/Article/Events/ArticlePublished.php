<?php

declare(strict_types=1);

namespace App\Domain\Article\Events;

use App\Domain\Shared\DomainEvent;
use App\Domain\Shared\Uuid;
use DateTimeImmutable;

/**
 * Article Published Event.
 *
 * Dispatched when an article is published.
 */
final  class ArticlePublished extends DomainEvent
{
    /**
     * @param Uuid $articleId Published article ID
     * @param Uuid $authorId Author who published
     * @param string $title Article title
     * @param string $slug Article slug
     * @param string|null $previousStatus Previous status (DRAFT, ARCHIVED)
     */
    public function __construct(
        private Uuid $articleId,
        private Uuid $authorId,
        private string $title,
        private string $slug,
        private ?string $previousStatus,
        DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {
        parent::__construct($occurredAt);
    }

    /**
     * Get published article ID.
     */
    public function getArticleId(): Uuid
    {
        return $this->articleId;
    }

    /**
     * Get author ID.
     */
    public function getAuthorId(): Uuid
    {
        return $this->authorId;
    }

    /**
     * Get article title.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get article slug.
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * Get previous status before publishing.
     */
    public function getPreviousStatus(): ?string
    {
        return $this->previousStatus;
    }

    /**
     * {@inheritdoc}
     */
    public function getPayload(): array
    {
        return [
            'article_id' => $this->articleId->getValue(),
            'author_id' => $this->authorId->getValue(),
            'title' => $this->title,
            'slug' => $this->slug,
            'previous_status' => $this->previousStatus,
        ];
    }

    /**
     * {@inheritdoc}
     * @throws \DateMalformedStringException
     */
    public static function fromArray(array $data): self
    {
        return new self(
            articleId: Uuid::fromString($data['article_id']),
            authorId: Uuid::fromString($data['author_id']),
            title: $data['title'],
            slug: $data['slug'],
            previousStatus: $data['previous_status'] ?? null,
            occurredAt: new DateTimeImmutable($data['occurred_at'])
        );
    }
}