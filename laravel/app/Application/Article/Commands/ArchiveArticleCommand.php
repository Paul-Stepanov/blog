<?php

declare(strict_types=1);

namespace App\Application\Article\Commands;

use App\Domain\Shared\Uuid;

/**
 * Command to archive an article.
 *
 * Changes article status to archived (soft delete alternative).
 * Uses Uuid for type-safe identifier.
 */
final readonly class ArchiveArticleCommand
{
    /**
     * @param Uuid $articleId Article UUID to archive (VO - type-safe ID)
     */
    public function __construct(
        public Uuid $articleId,
    ) {}

    /**
     * Create command from article ID string.
     */
    public static function fromId(string $articleId): self
    {
        return new self(
            articleId: Uuid::fromString($articleId),
        );
    }
}