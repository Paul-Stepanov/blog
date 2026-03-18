<?php

declare(strict_types=1);

namespace App\Domain\Article\Entities;

use App\Domain\Article\ValueObjects\Slug;
use App\Domain\Shared\Entity;
use App\Domain\Shared\Exceptions\ValidationException;
use App\Domain\Shared\Timestamps;
use App\Domain\Shared\Uuid;

/**
 * Category Entity.
 *
 * Represents a category for organizing articles.
 */
final class Category extends Entity
{
    private string $name;
    private Slug $slug;
    private string $description;
    private Timestamps $timestamps;

    public function __construct(
        Uuid $id,
        string $name,
        Slug $slug,
        string $description,
        Timestamps $timestamps,
    ) {
        parent::__construct($id);

        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
        $this->timestamps = $timestamps;
    }

    /**
     * Create a new category.
     */
    public static function create(
        Uuid $id,
        string $name,
        Slug $slug,
        string $description = '',
    ): self {
        return new self(
            id: $id,
            name: $name,
            slug: $slug,
            description: $description,
            timestamps: Timestamps::now(),
        );
    }

    /**
     * Reconstruct from persistence.
     */
    public static function reconstitute(
        Uuid $id,
        string $name,
        Slug $slug,
        string $description,
        Timestamps $timestamps,
    ): self {
        return new self(
            id: $id,
            name: $name,
            slug: $slug,
            description: $description,
            timestamps: $timestamps,
        );
    }

    /**
     * Rename the category.
     *
     * @throws ValidationException
     */
    public function rename(string $name, ?Slug $newSlug = null): void
    {
        if (empty(trim($name))) {
            throw ValidationException::forField('name', 'Category name cannot be empty');
        }

        $this->name = $name;

        if ($newSlug !== null) {
            $this->slug = $newSlug;
        }

        $this->timestamps = $this->timestamps->touch();
    }

    /**
     * Update description.
     */
    public function updateDescription(string $description): void
    {
        $this->description = $description;
        $this->timestamps = $this->timestamps->touch();
    }
    
    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): Slug
    {
        return $this->slug;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getTimestamps(): Timestamps
    {
        return $this->timestamps;
    }
}