<?php

declare(strict_types=1);

namespace App\Domain\Article\ValueObjects;

use App\Domain\Shared\Uuid;

/**
 * Article Filters Value Object.
 *
 * Immutable query object for filtering articles in repository queries.
 * Encapsulates all possible filter criteria as a single cohesive object.
 **/
final readonly class ArticleFilters
{
    /**
     * Create article filters with all possible criteria.
     *
     * @param string|null $searchTerm Search query for title/content
     * @param Uuid|null $categoryId Filter by category UUID
     * @param Uuid|null $authorId Filter by author UUID
     * @param Uuid|null $tagId Filter by tag UUID
     * @param ArticleStatus|null $status Filter by article status
     */
    private function __construct(
        public ?string $searchTerm = null,
        public ?Uuid $categoryId = null,
        public ?Uuid $authorId = null,
        public ?Uuid $tagId = null,
        public ?ArticleStatus $status = null,
    ) {}

    /**
     * Create filters from an array (typically from Request or Query).
     *
     * Accepts both primitive values and Value Objects.
     * Handles type conversion and validation.
     *
     * @param array{
     *     search?: string|null,
     *     category_id?: string|Uuid|null,
     *     author_id?: string|Uuid|null,
     *     tag_id?: string|Uuid|null,
     *     status?: string|ArticleStatus|null
     * } $filters
     */
    public static function create(array $filters): self
    {
        $searchTerm = isset($filters['search']) && $filters['search'] !== ''
            ? trim($filters['search'])
            : null;

        $categoryId = self::resolveUuid($filters['category_id'] ?? null);
        $authorId = self::resolveUuid($filters['author_id'] ?? null);
        $tagId = self::resolveUuid($filters['tag_id'] ?? null);
        $status = self::resolveStatus($filters['status'] ?? null);

        return new self(
            searchTerm: $searchTerm,
            categoryId: $categoryId,
            authorId: $authorId,
            tagId: $tagId,
            status: $status,
        );
    }

    /**
     * Create empty filters (no filtering applied).
     */
    public static function empty(): self
    {
        return new self();
    }

    /**
     * Create filters for published articles only.
     */
    public static function published(): self
    {
        return new self(status: ArticleStatus::PUBLISHED);
    }

    /**
     * Create filters for admin panel (all statuses visible).
     * Optionally filter by specific status.
     */
    public static function forAdmin(?ArticleStatus $status = null): self
    {
        return new self(status: $status);
    }

    /**
     * Check if search filter is applied.
     */
    public function hasSearch(): bool
    {
        return $this->searchTerm !== null && $this->searchTerm !== '';
    }

    /**
     * Check if category filter is applied.
     */
    public function hasCategory(): bool
    {
        return $this->categoryId !== null;
    }

    /**
     * Check if author filter is applied.
     */
    public function hasAuthor(): bool
    {
        return $this->authorId !== null;
    }

    /**
     * Check if tag filter is applied.
     */
    public function hasTag(): bool
    {
        return $this->tagId !== null;
    }

    /**
     * Check if status filter is applied.
     */
    public function hasStatus(): bool
    {
        return $this->status !== null;
    }

    /**
     * Check if any filter is applied.
     */
    public function hasFilters(): bool
    {
        return $this->hasSearch()
            || $this->hasCategory()
            || $this->hasAuthor()
            || $this->hasTag()
            || $this->hasStatus();
    }

    /**
     * Create a new instance with different search term.
     */
    public function withSearch(?string $searchTerm): self
    {
        return new self(
            searchTerm: $searchTerm !== '' ? $searchTerm : null,
            categoryId: $this->categoryId,
            authorId: $this->authorId,
            tagId: $this->tagId,
            status: $this->status,
        );
    }

    /**
     * Create a new instance with different category.
     */
    public function withCategory(?Uuid $categoryId): self
    {
        return new self(
            searchTerm: $this->searchTerm,
            categoryId: $categoryId,
            authorId: $this->authorId,
            tagId: $this->tagId,
            status: $this->status,
        );
    }

    /**
     * Create a new instance with different status.
     */
    public function withStatus(?ArticleStatus $status): self
    {
        return new self(
            searchTerm: $this->searchTerm,
            categoryId: $this->categoryId,
            authorId: $this->authorId,
            tagId: $this->tagId,
            status: $status,
        );
    }

    /**
     * Get search term safe for LIKE query.
     * Escapes special characters for SQL LIKE.
     */
    public function getSearchTermSafe(): ?string
    {
        if (!$this->hasSearch()) {
            return null;
        }

        // Escape special LIKE characters
        return str_replace(
            ['%', '_', '\\'],
            ['\\%', '\\_', '\\\\'],
            $this->searchTerm
        );
    }

    /**
     * Resolve UUID from string or UUID object.
     */
    private static function resolveUuid(string|Uuid|null $value): ?Uuid
    {
        if ($value instanceof Uuid) {
            return $value;
        }

        if ($value === null || $value === '') {
            return null;
        }

        return Uuid::fromString($value);
    }

    /**
     * Resolve ArticleStatus from string or enum.
     */
    private static function resolveStatus(string|ArticleStatus|null $value): ?ArticleStatus
    {
        if ($value instanceof ArticleStatus) {
            return $value;
        }

        if ($value === null || $value === '') {
            return null;
        }

        return ArticleStatus::fromString($value);
    }
}