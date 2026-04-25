<?php

declare(strict_types=1);

namespace App\Application\Article\Commands;

use App\Infrastructure\Http\Requests\Admin\TagRequest;

/**
 * Command to update an existing tag.
 *
 * Immutable command object for CQRS pattern.
 * All parameters are optional - only provided fields will be updated.
 */
final readonly class UpdateTagCommand
{
    /**
     * @param string $tagId Tag ID (primitive for parsing)
     * @param string|null $name New name
     * @param string|null $slug New slug (primitive - converted to Slug in Service)
     */
    public function __construct(
        public string $tagId,
        public ?string $name = null,
        public ?string $slug = null,
    ) {}

    /**
     * Create command from Form Request.
     *
     * Handles transformation from HTTP layer to Application layer.
     */
    public static function fromRequest(TagRequest $request, string $id): self
    {
        return new self(
            tagId: $id,
            name: $request->validated('name'),
            slug: $request->validated('slug'),
        );
    }
}