<?php

declare(strict_types=1);

namespace App\Domain\Article\Events;

use App\Domain\Shared\DomainEvent;
use App\Domain\Shared\Uuid;
use DateTimeImmutable;

/**
 * Article Archived Event.
 *
 * Dispatched when an article is archived.
 */
final class ArticleArchived extends DomainEvent
{
    /**
     * @param Uuid $articleId Archived article ID
     * @param Uuid $authorId Author who archived
     * @param string $title Article title
     * @param string $slug Article slug
     * @param string $previousStatus Previous status (DRAFT, PUBLISHED)
     * @param string|null $reason Optional reason for archiving
     */
    public function __construct(
        private Uuid $articleId,
        private Uuid $authorId,
        private string $title,
        private string $slug,
        private string $previousStatus,
        private ?string $reason,
        DateTimeImmutable $occurredAt = new DateTimeImmutable()
    ) {
        parent::__construct($occurredAt);
    }

    /**
     * Get archived article ID.
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
     * Get previous status before archiving.
     */
    public function getPreviousStatus(): string
    {
        return $this->previousStatus;
    }

    /**
     * Get archiving reason.
     */
    public function getReason(): ?string
    {
        return $this->reason;
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
            'reason' => $this->reason,
        ];
    }

    /**
     * {@inheritdoc}
     * @throws \DateMalformedStringException
     */
    public static function fromArray(array $data): static
    {
        return new self(
            articleId: Uuid::fromString($data['article_id']),
            authorId: Uuid::fromString($data['author_id']),
            title: $data['title'],
            slug: $data['slug'],
            previousStatus: $data['previous_status'],
            reason: $data['reason'] ?? null,
            occurredAt: new DateTimeImmutable($data['occurred_at'])
        );
    }
}