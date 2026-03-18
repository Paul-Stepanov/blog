<?php

declare(strict_types=1);

namespace App\Domain\Article\Repositories;

use App\Domain\Article\Entities\Article;
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
     * Find article by ID.
     */
    public function findById(Uuid $id): ?Article;

    /**
     * Find article by slug.
     */
    public function findBySlug(string $slug): ?Article;

    /**
     * Find all published articles with pagination.
     *
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
}