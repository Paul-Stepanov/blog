<?php

declare(strict_types=1);

namespace App\Application\Article\Commands;

use App\Domain\Article\ValueObjects\Slug;
use App\Infrastructure\Http\Requests\Admin\CategoryRequest;

/**
 * Command to create a new category.
 *
 * Immutable command object for CQRS pattern.
 */
final readonly class CreateCategoryCommand
{
    /**
     * @param string $name Category name
     * @param Slug|null $slug Optional custom slug
     * @param string $description Category description
     */
    public function __construct(
        public string $name,
        public ?Slug $slug = null,
        public string $description = '',
    ) {}

    /**
     * Create command from Form Request.
     *
     * Handles transformation from HTTP layer to Application layer.
     */
    public static function fromRequest(CategoryRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            slug: $request->validated('slug') !== null
                ? Slug::fromString($request->validated('slug'))
                : null,
            description: $request->validated('description') ?? '',
        );
    }
}