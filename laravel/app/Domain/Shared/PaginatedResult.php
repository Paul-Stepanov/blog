<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Generic paginated result container (DTO).
 *
 * Used for transferring paginated data between layers.
 *
 * @template T
 * @implements IteratorAggregate<T>
 */
final readonly class PaginatedResult implements IteratorAggregate, JsonSerializable
{
    /**
     * @param array<T> $items Items on current page
     * @param int $total Total number of items across all pages
     * @param int $page Current page number (1-based)
     * @param int $perPage Items per page
     * @param int $lastPage Last page number
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
        public readonly int $lastPage
    ) {}

    /**
     * Create from Laravel paginator.
     *
     * @template TItem
     * @param iterable<TItem> $laravelPaginator
     * @param callable(TItem): T $mapper
     * @return self<T>
     */
    public static function fromLaravel(iterable $laravelPaginator, callable $mapper): self
    {
        $items = [];
        foreach ($laravelPaginator->items() as $item) {
            $items[] = $mapper($item);
        }

        return new self(
            items: $items,
            total: $laravelPaginator->total(),
            page: $laravelPaginator->currentPage(),
            perPage: $laravelPaginator->perPage(),
            lastPage: $laravelPaginator->lastPage()
        );
    }

    /**
     * Create an empty result.
     *
     * @return self<T>
     */
    public static function empty(int $page = 1, int $perPage = 12): self
    {
        return new self(
            items: [],
            total: 0,
            page: $page,
            perPage: $perPage,
            lastPage: 1
        );
    }

    /**
     * Check if there are more pages after current.
     */
    public function hasMore(): bool
    {
        return $this->page < $this->lastPage;
    }

    /**
     * Check if result is empty.
     */
    public function isEmpty(): bool
    {
        return count($this->items) === 0;
    }

    /**
     * Check if result has items.
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * Get the first item or null if empty.
     *
     * @return T|null
     */
    public function first(): mixed
    {
        return $this->items[0] ?? null;
    }

    /**
     * Map items to a new type.
     *
     * @template TNew
     * @param callable(T): TNew $callback
     * @return self<TNew>
     */
    public function map(callable $callback): self
    {
        return new self(
            items: array_map($callback, $this->items),
            total: $this->total,
            page: $this->page,
            perPage: $this->perPage,
            lastPage: $this->lastPage
        );
    }

    /**
     * Get iterator for items.
     *
     * @return Traversable<T>
     */
    public function getIterator(): Traversable
    {
        foreach ($this->items as $item) {
            yield $item;
        }
    }

    /**
     * Count items on current page.
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Get pagination metadata.
     *
     * @return array{total: int, page: int, perPage: int, lastPage: int, hasMore: bool}
     */
    public function getMeta(): array
    {
        return [
            'total' => $this->total,
            'page' => $this->page,
            'perPage' => $this->perPage,
            'lastPage' => $this->lastPage,
            'hasMore' => $this->hasMore(),
        ];
    }

    /**
     * @return array{items: array<T>, meta: array<string, mixed>}
     */
    public function jsonSerialize(): array
    {
        return [
            'items' => $this->items,
            'meta' => $this->getMeta(),
        ];
    }
}