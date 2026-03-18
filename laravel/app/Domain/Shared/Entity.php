<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/**
 * Base Entity class for Domain-Driven Design.
 *
 * Entities are objects defined by their identity rather than their attributes.
 * Two entities are equal if they have the same identity, regardless of their attributes.
 */
abstract class Entity
{
    /**
     * @param Uuid $id Unique identifier of the entity
     */
    public function __construct(
        protected readonly Uuid $id
    ) {}

    /**
     * Get the entity's unique identifier.
     */
    public function getId(): Uuid
    {
        return $this->id;
    }

    /**
     * Check equality with another entity.
     * Two entities are equal if they have the same class and ID.
     */
    public function equals(self $other): bool
    {
        return $this::class === $other::class
            && $this->id->equals($other->id);
    }
}