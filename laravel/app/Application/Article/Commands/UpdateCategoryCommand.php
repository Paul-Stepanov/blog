<?php

declare(strict_types=1);

namespace App\Application\Article\Commands;

use App\Infrastructure\Http\Requests\Admin\CategoryRequest;

/**
 * Command to update an existing category.
 *
 * Immutable command object for CQRS pattern.
 * All parameters are optional - only provided fields will be updated.
 */
final readonly class UpdateCategoryCommand
{
    /**
     * @param string $categoryId Category ID (primitive for parsing)
     * @param string|null $name New name
     * @param string|null $slug New slug (primitive - converted to Slug in Service)
     * @param string|null $description New description
     */
    public function __construct(
        public string $categoryId,
        public ?string $name = null,
        public ?string $slug = null,
        public ?string $description = null,
    ) {}

    /**
     * Create command from Form Request.
     *
     * Handles transformation from HTTP layer to Application layer.
     */
    public static function fromRequest(CategoryRequest $request, string $id): self
    {
        return new self(
            categoryId: $id,
            name: $request->validated('name'),
            slug: $request->validated('slug'),
            description: $request->validated('description'),
        );
    }
}