<?php

declare(strict_types=1);

namespace App\Domain\Article\Repositories;

use App\Domain\Article\Entities\Tag;
use App\Domain\Shared\Exceptions\EntityNotFoundException;
use App\Domain\Shared\Uuid;

/**
 * Tag Repository Interface.
 *
 * Contract for tag persistence operations.
 */
interface TagRepositoryInterface
{
    /**
     * Find tag by ID - optional lookup.
     *
     * Use this when the tag may or may not exist.
     * For mandatory lookups, use getById().
     */
    public function findById(Uuid $id): ?Tag;

    /**
     * Get tag by ID - mandatory lookup.
     *
     * Use this when the tag MUST exist by business logic.
     *
     * @throws EntityNotFoundException If tag not found
     */
    public function getById(Uuid $id): Tag;

    /**
     * Find tag by slug - optional lookup.
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