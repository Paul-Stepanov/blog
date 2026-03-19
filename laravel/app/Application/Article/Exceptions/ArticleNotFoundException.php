<?php

declare(strict_types=1);

namespace App\Application\Article\Exceptions;

use App\Application\Shared\Exceptions\ApplicationException;

/**
 * Exception thrown when an article is not found.
 */
final class ArticleNotFoundException extends ApplicationException
{
    /**
     * @param string $identifier Article ID or slug that was not found
     */
    public static function byId(string $identifier): self
    {
        return new self("Article not found: {$identifier}");
    }

    /**
     * @param string $slug Article slug that was not found
     */
    public static function bySlug(string $slug): self
    {
        return new self("Article not found with slug: {$slug}");
    }

    /**
     * @return non-empty-string
     */
    public function getErrorType(): string
    {
        return 'article_not_found';
    }

    /**
     * @return array<string, string>
     */
    public function getContext(): array
    {
        return ['resource' => 'article'];
    }
}