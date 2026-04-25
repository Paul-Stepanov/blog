<?php

declare(strict_types=1);

namespace App\Application\Article\DTOs;

use App\Application\Shared\{DTOFormattingTrait, DTOInterface};
use App\Application\Shared\Exceptions\InvalidEntityTypeException;
use App\Domain\Article\Entities\Category;
use App\Domain\Shared\Entity;

/**
 * Category Data Transfer Object.
 *
 * Represents a category for transfer between layers.
 * Immutable and serializable for API responses.
 */
final readonly class CategoryDTO implements DTOInterface
{
    use DTOFormattingTrait;

    /**
     * @param string $id UUID string
     * @param string $name Category name
     * @param string $slug URL-friendly identifier
     * @param string $description Category description
     * @param string $createdAt ISO 8601 datetime
     * @param string $updatedAt ISO 8601 datetime
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $slug,
        public string $description,
        public string $createdAt,
        public string $updatedAt,
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

        $timestamps = $entity->getTimestamps();

        return new self(
            id: $entity->getId()->getValue(),
            name: $entity->getName(),
            slug: $entity->getSlug()->getValue(),
            description: $entity->getDescription(),
            createdAt: self::formatDate($timestamps->getCreatedAt()),
            updatedAt: self::formatDate($timestamps->getUpdatedAt()),
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
            'description' => $this->description,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}