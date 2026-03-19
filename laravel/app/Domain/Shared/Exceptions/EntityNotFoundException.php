<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

use App\Domain\Shared\Uuid;

/**
 * Exception thrown when an entity is not found during a mandatory lookup.
 *
 * Use this exception when the business logic expects an entity to exist.
 * For optional lookups, use findById() methods that return null.
 */
final class EntityNotFoundException extends DomainException
{
    /**
     * @param string $entityType The type of entity (Article, Tag, User, etc.)
     * @param string $identifier The identifier used for lookup
     * @param string $identifierType The type of identifier (id, slug, email)
     */
    private function __construct(
        private readonly string $entityType,
        private readonly string $identifier,
        private readonly string $identifierType = 'id'
    ) {
        parent::__construct(
            sprintf('%s not found with %s: %s', $entityType, $identifierType, $identifier)
        );
    }

    /**
     * Create exception for entity lookup by UUID.
     *
     * @param string $entityType The type of entity (e.g., 'Article', 'Tag', 'User')
     */
    public static function forEntity(string $entityType, Uuid $id): self
    {
        return new self($entityType, $id->getValue(), 'id');
    }

    /**
     * Create exception for entity lookup by slug.
     */
    public static function bySlug(string $entityType, string $slug): self
    {
        return new self($entityType, $slug, 'slug');
    }

    /**
     * Create exception for entity lookup by email.
     */
    public static function byEmail(string $entityType, string $email): self
    {
        return new self($entityType, $email, 'email');
    }

    /**
     * Create exception for entity lookup by custom identifier.
     */
    public static function byIdentifier(string $entityType, string $identifier, string $identifierType): self
    {
        return new self($entityType, $identifier, $identifierType);
    }

    /**
     * Get the entity type that was not found.
     */
    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * Get the identifier used for lookup.
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Get the identifier type (id, slug, email).
     */
    public function getIdentifierType(): string
    {
        return $this->identifierType;
    }

    /**
     * {@inheritdoc}
     */
    public function getContext(): array
    {
        return [
            'entity_type' => $this->entityType,
            'identifier' => $this->identifier,
            'identifier_type' => $this->identifierType,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getErrorType(): string
    {
        return 'entity_not_found';
    }
}