<?php

declare(strict_types=1);

namespace App\Application\Article\DTOs;

use App\Application\Shared\{DTOFormattingTrait, DTOInterface};
use App\Application\Shared\Exceptions\InvalidEntityTypeException;
use App\Domain\Article\Entities\Tag;
use App\Domain\Shared\Entity;

/**
 * Tag List Data Transfer Object.
 *
 * Lightweight representation of a tag for list views.
 * Contains only essential fields needed for tag listings.
 */
final readonly class TagListDTO implements DTOInterface
{
    use DTOFormattingTrait;

    /**
     * @param string $id UUID string
     * @param string $name Tag name
     * @param string $slug URL-friendly identifier
     * @param int $articleCount Number of articles with this tag
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
     * @param Entity $entity Domain tag entity
     */
    public static function fromEntity(Entity $entity): static
    {
        if (!$entity instanceof Tag) {
            throw new InvalidEntityTypeException(
                expectedType: Tag::class,
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
     * Create from array data (for tag with article count).
     *
     * @param array{tag: Tag, count: int} $data
     */
    public static function fromArrayData(array $data): static
    {
        return new self(
            id: $data['tag']->getId()->getValue(),
            name: $data['tag']->getName(),
            slug: $data['tag']->getSlug()->getValue(),
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