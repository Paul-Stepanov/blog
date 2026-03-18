<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Base class for domain events.
 *
 * Domain events represent something that happened in the domain
 * that other parts of the system need to be aware of.
 */
abstract class DomainEvent
{
    public const string EVENT_NAME = '';

    protected readonly DateTimeImmutable $occurredAt;

    public function __construct(?DateTimeImmutable $occurredAt = null)
    {
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable();
    }

    /**
     * Get the event name for routing/handling.
     */
    public function getEventName(): string
    {
        return static::EVENT_NAME ?: static::class;
    }

    /**
     * Get when the event occurred.
     */
    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    /**
     * Get event payload for serialization.
     *
     * @return array<string, mixed>
     */
    abstract public function getPayload(): array;

    /**
     * Get full event data for serialization/queue.
     *
     * @return array{name: string, occurredAt: string, payload: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->getEventName(),
            'occurredAt' => $this->occurredAt->format(DateTimeInterface::ATOM),
            'payload' => $this->getPayload(),
        ];
    }
}