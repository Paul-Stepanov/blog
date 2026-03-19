<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Resources;

use App\Domain\Shared\PaginatedResult;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Generic paginated response wrapper.
 *
 * Wraps PaginatedResult with items transformed by the specified resource class.
 *
 * @template T
 */
final class PaginatedResource extends JsonResource
{
    /**
     * @param PaginatedResult<T> $paginatedResult
     * @param class-string<JsonResource> $itemResourceClass
     */
    public function __construct(
        private readonly PaginatedResult $paginatedResult,
        private readonly string $itemResourceClass,
    ) {
        parent::__construct($paginatedResult);
    }

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $items = array_map(
            fn($item) => new $this->itemResourceClass($item)->toArray($request),
            $this->paginatedResult->items
        );

        return [
            'success' => true,
            'data' => $items,
            'meta' => [
                'pagination' => [
                    'total' => $this->paginatedResult->total,
                    'count' => count($items),
                    'per_page' => $this->paginatedResult->perPage,
                    'current_page' => $this->paginatedResult->page,
                    'total_pages' => $this->paginatedResult->lastPage,
                    'has_more' => $this->paginatedResult->hasMore(),
                ],
            ],
        ];
    }
}