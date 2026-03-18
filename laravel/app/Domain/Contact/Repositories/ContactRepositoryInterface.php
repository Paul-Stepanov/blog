<?php

declare(strict_types=1);

namespace App\Domain\Contact\Repositories;

use App\Domain\Contact\Entities\ContactMessage;
use App\Domain\Shared\PaginatedResult;
use App\Domain\Shared\Uuid;

/**
 * Contact Repository Interface.
 *
 * Contract for contact message persistence operations.
 */
interface ContactRepositoryInterface
{
    /**
     * Find contact message by ID.
     */
    public function findById(Uuid $id): ?ContactMessage;

    /**
     * Find all contact messages with pagination.
     *
     * @return PaginatedResult<ContactMessage>
     */
    public function findAll(int $page = 1, int $perPage = 20): PaginatedResult;

    /**
     * Find unread messages.
     *
     * @return PaginatedResult<ContactMessage>
     */
    public function findUnread(int $page = 1, int $perPage = 20): PaginatedResult;

    /**
     * Find read messages.
     *
     * @return PaginatedResult<ContactMessage>
     */
    public function findRead(int $page = 1, int $perPage = 20): PaginatedResult;

    /**
     * Search messages by name, email, or subject.
     *
     * @return PaginatedResult<ContactMessage>
     */
    public function search(string $query, int $page = 1, int $perPage = 20): PaginatedResult;

    /**
     * Get recent messages.
     *
     * @return array<ContactMessage>
     */
    public function getRecent(int $limit = 10): array;

    /**
     * Get messages from specific email.
     *
     * @return array<ContactMessage>
     */
    public function findByEmail(string $email): array;

    /**
     * Get messages from specific IP address.
     *
     * @return array<ContactMessage>
     */
    public function findByIpAddress(string $ipAddress): array;

    /**
     * Save contact message (create or update).
     */
    public function save(ContactMessage $message): void;

    /**
     * Delete contact message by ID.
     */
    public function delete(Uuid $id): void;

    /**
     * Count total messages.
     */
    public function count(): int;

    /**
     * Count unread messages.
     */
    public function countUnread(): int;

    /**
     * Count messages by date range.
     *
     * @return array<string, int> Date => count
     */
    public function countByDate(\DateTimeInterface $from, \DateTimeInterface $to): array;
}