<?php

declare(strict_types=1);

namespace App\Domain\Article\Repositories;

use App\Domain\Article\Entities\Tag;
use App\Domain\Shared\Uuid;

/**
 * Tag Repository Interface.
 *
 * Contract for tag persistence operations.
 */
interface TagRepositoryInterface
{
    /**
     * Find tag by ID.
     */
    public function findById(Uuid $id): ?Tag;

    /**
     * Find tag by slug.
     */
    public function findBySlug(string $slug): ?Tag;

    /**
     * Find multiple tags by their IDs.
     *
     * @param Uuid[] $ids
     * @return array<Tag>
     */
    public function findByIds(array $ids): array;

    /**
     * Find multiple tags by their slugs.
     *
     * @param string[] $slugs
     * @return array<Tag>
     */
    public function findBySlugs(array $slugs): array;

    /**
     * Get all tags.
     *
     * @return array<Tag>
     */
    public function findAll(): array;

    /**
     * Get tags ordered by name.
     *
     * @return array<Tag>
     */
    public function findAllOrderedByName(): array;

    /**
     * Get tags with article count.
     *
     * @return array{tag: Tag, count: int}[]
     */
    public function getWithArticleCount(): array;

    /**
     * Get most used tags.
     *
     * @return array<Tag>
     */
    public function getPopular(int $limit = 10): array;

    /**
     * Get tags for a specific article.
     *
     * @return array<Tag>
     */
    public function getForArticle(Uuid $articleId): array;

    /**
     * Sync tags for an article.
     *
     * @param Uuid[] $tagIds
     */
    public function syncForArticle(Uuid $articleId, array $tagIds): void;

    /**
     * Save tag (create or update).
     */
    public function save(Tag $tag): void;

    /**
     * Delete tag by ID.
     */
    public function delete(Uuid $id): void;

    /**
     * Check if slug exists.
     */
    public function slugExists(string $slug, ?Uuid $excludeId = null): bool;

    /**
     * Count total tags.
     */
    public function count(): int;
}