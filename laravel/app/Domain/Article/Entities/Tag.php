<?php

declare(strict_types=1);

namespace App\Domain\Article\Entities;

use App\Domain\Article\ValueObjects\Slug;
use App\Domain\Shared\Entity;
use App\Domain\Shared\Exceptions\ValidationException;
use App\Domain\Shared\Timestamps;
use App\Domain\Shared\Uuid;

/**
 * Tag Entity.
 *
 * Represents a tag for labeling and grouping articles.
 */
final class Tag extends Entity
{
    private string $name;
    private Slug $slug;
    private Timestamps $timestamps;

    public function __construct(
        Uuid $id,
        string $name,
        Slug $slug,
        Timestamps $timestamps,
    ) {
        parent::__construct($id);

        $this->name = $name;
        $this->slug = $slug;
        $this->timestamps = $timestamps;
    }

    /**
     * Create a new tag.
     */
    public static function create(
        Uuid $id,
        string $name,
        Slug $slug,
    ): self {
        return new self(
            id: $id,
            name: $name,
            slug: $slug,
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
        Timestamps $timestamps,
    ): self {
        return new self(
            id: $id,
            name: $name,
            slug: $slug,
            timestamps: $timestamps,
        );
    }

    /**
     * Rename the tag.
     *
     * @throws ValidationException
     */
    public function rename(string $name, ?Slug $newSlug = null): void
    {
        if (empty(trim($name))) {
            throw ValidationException::forField('name', 'Tag name cannot be empty');
        }

        $this->name = $name;

        if ($newSlug !== null) {
            $this->slug = $newSlug;
        }

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

    public function getTimestamps(): Timestamps
    {
        return $this->timestamps;
    }
}