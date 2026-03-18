<?php

declare(strict_types=1);

namespace App\Domain\Article\Repositories;

use App\Domain\Article\Entities\Category;
use App\Domain\Shared\Uuid;

/**
 * Category Repository Interface.
 *
 * Contract for category persistence operations.
 */
interface CategoryRepositoryInterface
{
    /**
     * Find category by ID.
     */
    public function findById(Uuid $id): ?Category;

    /**
     * Find category by slug.
     */
    public function findBySlug(string $slug): ?Category;

    /**
     * Find all categories.
     *
     * @return array<Category>
     */
    public function findAll(): array;

    /**
     * Find categories with article count.
     *
     * @return array{category: Category, count: int}[]
     */
    public function findAllWithArticleCount(): array;

    /**
     * Get categories that have published articles.
     *
     * @return array<Category>
     */
    public function getWithPublishedArticles(): array;

    /**
     * Save category (create or update).
     */
    public function save(Category $category): void;

    /**
     * Delete category by ID.
     */
    public function delete(Uuid $id): void;

    /**
     * Check if slug exists.
     */
    public function slugExists(string $slug, ?Uuid $excludeId = null): bool;

    /**
     * Check if category has articles.
     */
    public function hasArticles(Uuid $id): bool;

    /**
     * Count total categories.
     */
    public function count(): int;
}