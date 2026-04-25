<?php

declare(strict_types=1);

namespace App\Application\Article\Services;

use App\Application\Article\Commands\{CreateTagCommand, UpdateTagCommand};
use App\Application\Article\DTOs\{TagDTO, TagListDTO};
use App\Domain\Article\Entities\Tag;
use App\Domain\Article\Repositories\TagRepositoryInterface;
use App\Domain\Article\ValueObjects\Slug;
use App\Domain\Shared\Uuid;

/**
 * Tag Application Service.
 *
 * Orchestrates tag-related use cases by coordinating
 * domain objects and repository operations.
 */
final readonly class TagService
{
    public function __construct(
        private TagRepositoryInterface $tagRepository,
    ) {}

    /**
     * Get all tags for admin panel.
     *
     * @return array<TagListDTO>
     */
    public function getAllTags(): array
    {
        $tagsWithCount = $this->tagRepository->getWithArticleCount();

        return array_map(
            fn(array $data) => TagListDTO::fromArrayData($data),
            $tagsWithCount
        );
    }

    /**
     * Get tag by ID.
     */
    public function getTagById(string $id): ?TagDTO
    {
        $tag = $this->tagRepository->findById(Uuid::fromString($id));

        if ($tag === null) {
            return null;
        }

        return TagDTO::fromEntity($tag);
    }

    /**
     * Get tag by slug.
     */
    public function getTagBySlug(string $slug): ?TagDTO
    {
        $tag = $this->tagRepository->findBySlug($slug);

        if ($tag === null) {
            return null;
        }

        return TagDTO::fromEntity($tag);
    }

    /**
     * Get popular tags.
     *
     * @return array<TagListDTO>
     */
    public function getPopularTags(int $limit = 10): array
    {
        $tags = $this->tagRepository->getPopular($limit);

        $tagsWithCount = $this->tagRepository->getWithArticleCount();

        $countMap = [];
        foreach ($tagsWithCount as $data) {
            $countMap[$data['tag']->getId()->getValue()] = $data['count'];
        }

        return array_map(
            fn(Tag $tag) => new TagListDTO(
                id: $tag->getId()->getValue(),
                name: $tag->getName(),
                slug: $tag->getSlug()->getValue(),
                articleCount: $countMap[$tag->getId()->getValue()] ?? 0,
            ),
            $tags
        );
    }

    /**
     * Create a new tag.
     */
    public function createTag(CreateTagCommand $command): TagDTO
    {
        $slug = $command->slug ?? Slug::fromTitle($command->name);

        $tag = Tag::create(
            id: Uuid::generate(),
            name: $command->name,
            slug: $slug,
        );

        $this->tagRepository->save($tag);

        return TagDTO::fromEntity($tag);
    }

    /**
     * Update an existing tag.
     */
    public function updateTag(UpdateTagCommand $command): ?TagDTO
    {
        $tag = $this->tagRepository->findById(
            Uuid::fromString($command->tagId)
        );

        if ($tag === null) {
            return null;
        }

        // Update name and optionally slug
        if ($command->name !== null) {
            $newSlug = $command->slug !== null
                ? Slug::fromString($command->slug)
                : null;

            $tag->rename($command->name, $newSlug);
        }

        $this->tagRepository->save($tag);

        return TagDTO::fromEntity($tag);
    }

    /**
     * Delete a tag.
     */
    public function deleteTag(string $id): bool
    {
        $tagId = Uuid::fromString($id);
        $tag = $this->tagRepository->findById($tagId);

        if ($tag === null) {
            return false;
        }

        $this->tagRepository->delete($tagId);

        return true;
    }
}