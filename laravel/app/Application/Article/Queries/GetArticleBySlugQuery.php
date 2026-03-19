<?php

declare(strict_types=1);

namespace App\Application\Article\Queries;

use App\Domain\Article\ValueObjects\Slug;

/**
 * Query to get a single article by slug.
 *
 * Immutable query object for CQRS pattern.
 * Uses Slug VO for type-safe identifier.
 */
final readonly class GetArticleBySlugQuery
{
    /**
     * @param Slug $slug Article slug to search for (VO - type-safe)
     */
    public function __construct(
        public Slug $slug,
    ) {}
}