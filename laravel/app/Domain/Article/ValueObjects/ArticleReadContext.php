<?php

declare(strict_types=1);

namespace App\Domain\Article\ValueObjects;

/**
 * Read-side snapshot for an Article.
 *
 * Carries the denormalized presentation data (category, tags, author, cover URL)
 * needed to render list/detail views without extra queries. Stored as primitive
 * arrays/strings so it survives cache (de)serialization without depending on
 * the base ValueObject's single-value serialization contract.
 *
 * This is a read-optimized projection carried by the Article aggregate; it is
 * immutable, optional (empty on the write path), and the aggregate never
 * mutates it.
 */
final class ArticleReadContext
{
    /**
     * @param  array{name: string, slug: string}|null  $category  Category snapshot or null
     * @param  list<array{name: string, slug: string}>  $tags  Tag snapshots
     * @param  array{name: string}|null  $author  Author snapshot or null
     * @param  string|null  $coverImageUrl  Public cover image URL or null
     */
    public function __construct(
        public readonly ?array $category,
        public readonly array $tags,
        public readonly ?array $author,
        public readonly ?string $coverImageUrl,
    ) {}

    /**
     * Empty snapshot for write-path entities (no relations loaded).
     */
    public static function empty(): self
    {
        return new self(
            category: null,
            tags: [],
            author: null,
            coverImageUrl: null,
        );
    }
}
