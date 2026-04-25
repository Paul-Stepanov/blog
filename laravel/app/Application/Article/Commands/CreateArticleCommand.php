<?php

declare(strict_types=1);

namespace App\Application\Article\Commands;

use App\Domain\Article\ValueObjects\Slug;
use App\Domain\Shared\Uuid;
use App\Infrastructure\Http\Requests\Api\CreateArticleRequest;

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
     * @param string|null $excerpt Optional excerpt (primitive - auto-generated from content if not provided)
     * @param Uuid|null $categoryId Category UUID (VO - type-safe ID)
     * @param Uuid|null $authorId Author UUID (VO - type-safe ID)
     * @param Uuid|null $coverImageId Cover image UUID (VO - type-safe ID)
     * @param array<int, string> $tags Array of tag UUIDs (primitive - validated in Service)
     */
    public function __construct(
        public string $title,
        public string $content,
        public ?Slug $slug = null,
        public ?string $excerpt = null,
        public ?Uuid $categoryId = null,
        public ?Uuid $authorId = null,
        public ?Uuid $coverImageId = null,
        public array $tags = [],
    ) {}

    /**
     * Create command from Form Request.
     *
     * Handles transformation from HTTP layer to Application layer.
     */
    public static function fromRequest(CreateArticleRequest $request): self
    {
        return new self(
            title: $request->validated('title'),
            content: $request->validated('content'),
            slug: $request->validated('slug') !== null
                ? Slug::fromString($request->validated('slug'))
                : null,
            excerpt: $request->validated('excerpt'),
            categoryId: $request->validated('category_id') !== null
                ? Uuid::fromString($request->validated('category_id'))
                : null,
            authorId: $request->validated('author_id') !== null
                ? Uuid::fromString($request->validated('author_id'))
                : null,
            coverImageId: $request->validated('cover_image_id') !== null
                ? Uuid::fromString($request->validated('cover_image_id'))
                : null,
            tags: $request->validated('tags') ?? [],
        );
    }
}