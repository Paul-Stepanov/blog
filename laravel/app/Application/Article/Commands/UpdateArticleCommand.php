<?php

declare(strict_types=1);

namespace App\Application\Article\Commands;

use App\Domain\Article\ValueObjects\Slug;
use App\Domain\Shared\Uuid;
use App\Infrastructure\Http\Requests\Api\UpdateArticleRequest;

/**
 * Command to update an existing article.
 *
 * Immutable command object for CQRS pattern.
 * All parameters are optional - only provided fields will be updated.
 */
final readonly class UpdateArticleCommand
{
    /**
     * @param string $articleId Article ID (primitive for parsing)
     * @param string|null $title New title
     * @param string|null $content New content
     * @param string|null $slug New slug (primitive - converted to Slug in Service)
     * @param Uuid|null $categoryId New category ID
     * @param Uuid|null $coverImageId New cover image ID
     */
    public function __construct(
        public string $articleId,
        public ?string $title = null,
        public ?string $content = null,
        public ?string $slug = null,
        public ?Uuid $categoryId = null,
        public ?Uuid $coverImageId = null,
    ) {}

    /**
     * Create command from Form Request.
     *
     * Handles transformation from HTTP layer to Application layer.
     */
    public static function fromRequest(UpdateArticleRequest $request, string $id): self
    {
        return new self(
            articleId: $id,
            title: $request->validated('title'),
            content: $request->validated('content'),
            slug: $request->validated('slug'),
            categoryId: $request->validated('category_id') !== null
                ? Uuid::fromString($request->validated('category_id'))
                : null,
            coverImageId: $request->validated('cover_image_id') !== null
                ? Uuid::fromString($request->validated('cover_image_id'))
                : null,
        );
    }
}