<?php

declare(strict_types=1);

namespace App\Application\Article\Commands;

use App\Domain\Shared\Uuid;

/**
 * Command to publish an article.
 *
 * Changes article status from draft to published.
 * Uses Uuid for type-safe identifier.
 */
final readonly class PublishArticleCommand
{
    /**
     * @param Uuid $articleId Article UUID to publish (VO - type-safe ID)
     */
    public function __construct(
        public Uuid $articleId,
    ) {}
}