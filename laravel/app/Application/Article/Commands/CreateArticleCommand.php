<?php

declare(strict_types=1);

namespace App\Application\Article\Commands;

use App\Domain\Article\ValueObjects\Slug;
use App\Domain\Shared\Uuid;

/**
 * Command to create a new article.
 *
 * Immutable command object for CQRS pattern.
 * Uses hybrid typing: Uuid for IDs, Slug for business logic, primitives for simple data.
 */
final readonly class CreateArticleCommand
{
    /**
     * @param string $title Article title (primitive - simple text)
     * @param string $content Article content (primitive - validated in Service)
     * @param Slug|null $slug Optional custom slug (VO - business logic)
     * @param Uuid|null $categoryId Category UUID (VO - type-safe ID)
     * @param Uuid|null $authorId Author UUID (VO - type-safe ID)
     */
    public function __construct(
        public string $title,
        public string $content,
        public ?Slug $slug = null,
        public ?Uuid $categoryId = null,
        public ?Uuid $authorId = null,
    ) {}
}