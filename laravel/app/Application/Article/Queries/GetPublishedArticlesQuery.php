<?php

declare(strict_types=1);

namespace App\Application\Article\Queries;

/**
 * Query to get a paginated list of published articles.
 *
 * Immutable query object for CQRS pattern.
 * Uses primitives for pagination (simple filtering).
 */
final readonly class GetPublishedArticlesQuery
{
    /**
     * @param int $page Page number (1-based)
     * @param int $perPage Items per page
     * @param string|null $categoryId Filter by category UUID (primitive - optional filter)
     * @param string|null $searchTerm Search in title/content (primitive - optional filter)
     */
    public function __construct(
        public int $page = 1,
        public int $perPage = 15,
        public ?string $categoryId = null,
        public ?string $searchTerm = null,
    ) {}
}