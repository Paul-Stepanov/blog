<?php

declare(strict_types=1);

namespace App\Domain\Article\Repositories;

use App\Domain\Article\Entities\Category;
use App\Domain\Shared\Exceptions\EntityNotFoundException;
use App\Domain\Shared\Uuid;

/**
 * Category Repository Interface.
 *
 * Contract for category persistence operations.
 */
interface CategoryRepositoryInterface
{
    /**
     * Find category by ID - optional lookup.
     *
     * Use this when the category may or may not exist.
     * For mandatory lookups, use getById().
     */
    public function findById(Uuid $id): ?Category;

    /**
     * Get category by ID - mandatory lookup.
     *
     * Use this when the category MUST exist by business logic.
     *
     * @throws EntityNotFoundException If category not found
     */
    public function getById(Uuid $id): Category;

    /**
     * Find category by slug - optional lookup.
     *
     * Use this when the category may or may not exist.
     * For mandatory lookups, use getBySlug().
     */
    public function findBySlug(string $slug): ?Category;

    /**
     * Get category by slug - mandatory lookup.
     *
     * Use this when the category MUST exist by business logic.
     *
     * @throws EntityNotFoundException If category not found
     */
    public function getBySlug(string $slug): Category;

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