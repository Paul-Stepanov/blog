<?php

declare(strict_types=1);

namespace App\Application\Article\DTOs;

use App\Application\Shared\{DTOFormattingTrait, DTOInterface};
use App\Application\Shared\Exceptions\InvalidEntityTypeException;
use App\Domain\Article\Entities\Category;
use App\Domain\Shared\Entity;

/**
 * Category List Data Transfer Object.
 *
 * Lightweight representation of a category for list views.
 * Contains only essential fields needed for category listings.
 */
final readonly class CategoryListDTO implements DTOInterface
{
    use DTOFormattingTrait;

    /**
     * @param string $id UUID string
     * @param string $name Category name
     * @param string $slug URL-friendly identifier
     * @param int $articleCount Number of articles in this category
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public int $articleCount = 0,
    ) {}

    /**
     * Create from Domain Entity.
     *
     * @param Entity $entity Domain category entity
     */
    public static function fromEntity(Entity $entity): static
    {
        if (!$entity instanceof Category) {
            throw new InvalidEntityTypeException(
                expectedType: Category::class,
                actualType: $entity::class
            );
        }

        return new self(
            id: $entity->getId()->getValue(),
            name: $entity->getName(),
            slug: $entity->getSlug()->getValue(),
        );
    }

    /**
     * Create from array data (for category with article count).
     *
     * @param array{category: Category, count: int} $data
     */
    public static function fromArrayData(array $data): static
    {
        return new self(
            id: $data['category']->getId()->getValue(),
            name: $data['category']->getName(),
            slug: $data['category']->getSlug()->getValue(),
            articleCount: $data['count'],
        );
    }

    /**
     * Convert DTO to associative array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'article_count' => $this->articleCount,
        ];
    }
}