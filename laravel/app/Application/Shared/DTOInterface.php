<?php

declare(strict_types=1);

namespace App\Application\Shared;

use App\Domain\Shared\Entity;
use JsonSerializable;

/**
 * Data Transfer Object Interface.
 *
 * Contract for all DTOs in the Application layer.
 * Ensures consistent transformation from Domain entities and serialization.
 *
 * @template TEntity of Entity
 */
interface DTOInterface extends JsonSerializable
{
    /**
     * Create DTO from Domain Entity.
     *
     * @param TEntity $entity
     * @return static
     */
    public static function fromEntity(Entity $entity): static;

    /**
     * Convert DTO to associative array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}