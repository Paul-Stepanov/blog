<?php

declare(strict_types=1);

namespace App\Application\Article\Commands;

use App\Domain\Article\ValueObjects\Slug;
use App\Infrastructure\Http\Requests\Admin\TagRequest;

/**
 * Command to create a new tag.
 *
 * Immutable command object for CQRS pattern.
 */
final readonly class CreateTagCommand
{
    /**
     * @param string $name Tag name
     * @param Slug|null $slug Optional custom slug
     */
    public function __construct(
        public string $name,
        public ?Slug $slug = null,
    ) {}

    /**
     * Create command from Form Request.
     *
     * Handles transformation from HTTP layer to Application layer.
     */
    public static function fromRequest(TagRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            slug: $request->validated('slug') !== null
                ? Slug::fromString($request->validated('slug'))
                : null,
        );
    }
}