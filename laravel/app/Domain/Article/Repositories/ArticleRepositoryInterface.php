<?php

declare(strict_types=1);

namespace App\Domain\Article\Repositories;

use App\Domain\Article\Entities\Article;
use App\Domain\Article\ValueObjects\ArticleFilters;
use App\Domain\Shared\Exceptions\EntityNotFoundException;
use App\Domain\Shared\PaginatedResult;
use App\Domain\Shared\Uuid;

/**
 * Article Repository Interface.
 *
 * Contract for article persistence operations.
 */
interface ArticleRepositoryInterface
{
    /**
     * Find articles by filters with pagination.
     *
     * Universal method for all article queries with filtering.
     * Replaces multiple specific query methods (findPublished, search, etc).
     *
     * @return PaginatedResult<Article>
     */
    public function findByFilters(
        ArticleFilters $filters,
        int $page = 1,
        int $perPage = 12
    ): PaginatedResult;

    /**
     * Find article by ID - optional lookup.
     *
     * Use this when the article may or may not exist.
     * For mandatory lookups, use getById().
     */
    public function findById(Uuid $id): ?Article;

    /**
     * Get article by ID - mandatory lookup.
     *
     * Use this when the article MUST exist by business logic.
     *
     * @throws EntityNotFoundException If article not found
     */
    public function getById(Uuid $id): Article;

    /**
     * Find article by slug - optional lookup.
     *
     * Use this when the article may or may not exist.
     * For mandatory lookups, use getBySlug().
     */
    public function findBySlug(string $slug): ?Article;

    /**
     * Get article by slug - mandatory lookup.
     *
     * Use this when the article MUST exist by business logic.
     *
     * @throws EntityNotFoundException If article not found
     */
    public function getBySlug(string $slug): Article;

    /**
     * Find all published articles with pagination.
     *
     * @deprecated Use findByFilters(ArticleFilters::published(), ...)
     * @return PaginatedResult<Article>
     */
    public function findPublished(int $page = 1, int $perPage = 12): PaginatedResult;

    /**
     * Find articles by category with pagination.
     *
     * @return PaginatedResult<Article>
     */
    public function findByCategory(string $categorySlug, int $page = 1, int $perPage = 12): PaginatedResult;

    /**
     * Find articles by tag with pagination.
     *
     * @return PaginatedResult<Article>
     */
    public function findByTag(string $tagSlug, int $page = 1, int $perPage = 12): PaginatedResult;

    /**
     * Find articles by author with pagination.
     *
     * @return PaginatedResult<Article>
     */
    public function findByAuthor(Uuid $authorId, int $page = 1, int $perPage = 12): PaginatedResult;

    /**
     * Search articles by query.
     *
     * @return PaginatedResult<Article>
     */
    public function search(string $query, int $page = 1, int $perPage = 12): PaginatedResult;

    /**
     * Get latest published articles.
     *
     * @return array<Article>
     */
    public function getLatest(int $limit = 5): array;

    /**
     * Get featured/pinned articles.
     *
     * @return array<Article>
     */
    public function getFeatured(int $limit = 3): array;

    /**
     * Find all articles (for admin).
     *
     * @return PaginatedResult<Article>
     */
    public function findAll(int $page = 1, int $perPage = 20): PaginatedResult;

    /**
     * Find articles by status.
     *
     * @return PaginatedResult<Article>
     */
    public function findByStatus(string $status, int $page = 1, int $perPage = 20): PaginatedResult;

    /**
     * Save article (create or update).
     */
    public function save(Article $article): void;

    /**
     * Delete article by ID.
     */
    public function delete(Uuid $id): void;

    /**
     * Check if slug exists (for uniqueness validation).
     */
    public function slugExists(string $slug, ?Uuid $excludeId = null): bool;

    /**
     * Count articles by status.
     */
    public function countByStatus(): array;

    /**
     * Sync tags for an article.
     *
     * Article owns the relationship with tags (article_tag pivot table).
     * This method manages the many-to-many relationship.
     *
     * @param Uuid $articleId Article UUID
     * @param array<Uuid> $tagIds Array of tag UUIDs to sync
     * @throws EntityNotFoundException If article not found
     */
    public function syncTags(Uuid $articleId, array $tagIds): void;
}