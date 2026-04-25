<?php

declare(strict_types=1);

namespace App\Application\Contact\Services;

use App\Application\Contact\Commands\SendMessageCommand;
use App\Application\Contact\DTOs\ContactMessageDTO;
use App\Domain\Contact\Entities\ContactMessage;
use App\Domain\Contact\Repositories\ContactRepositoryInterface;
use App\Domain\Shared\PaginatedResult;
use App\Domain\Shared\Uuid;

/**
 * Contact Application Service.
 *
 * Handles contact form submissions and message management.
 */
final readonly class ContactService
{
    public function __construct(
        private ContactRepositoryInterface $contactRepository,
    ) {}

    /**
     * Send a contact message.
     */
    public function sendMessage(SendMessageCommand $command): ContactMessageDTO
    {
        $message = ContactMessage::submit(
            id: Uuid::generate(),
            name: $command->name,
            email: $command->email,
            subject: $command->subject,
            message: $command->message,
            ipAddress: $command->ipAddress,
            userAgent: $command->userAgent,
        );

        $this->contactRepository->save($message);

        return ContactMessageDTO::fromEntity($message);
    }

    /**
     * Mark a message as read.
     */
    public function markAsRead(string $messageId): void
    {
        $uuid = Uuid::fromString($messageId);
        $message = $this->contactRepository->findById($uuid);

        if ($message !== null) {
            $message->markAsRead();
            $this->contactRepository->save($message);
        }
    }

    /**
     * Mark a message as unread.
     */
    public function markAsUnread(string $messageId): void
    {
        $uuid = Uuid::fromString($messageId);
        $message = $this->contactRepository->findById($uuid);

        if ($message !== null) {
            $message->markAsUnread();
            $this->contactRepository->save($message);
        }
    }

    /**
     * Get all messages with pagination.
     *
     * @return PaginatedResult<ContactMessageDTO>
     */
    public function getAllMessages(int $page = 1, int $perPage = 20): PaginatedResult
    {
        return $this->contactRepository->findAll($page, $perPage)
            ->map(fn(ContactMessage $message) => ContactMessageDTO::fromEntity($message));
    }

    /**
     * Get message by ID.
     */
    public function getMessageById(string $id): ?ContactMessageDTO
    {
        $message = $this->contactRepository->findById(Uuid::fromString($id));

        if ($message === null) {
            return null;
        }

        return ContactMessageDTO::fromEntity($message);
    }

    /**
     * Delete a message.
     */
    public function deleteMessage(string $id): bool
    {
        $uuid = Uuid::fromString($id);
        $message = $this->contactRepository->findById($uuid);

        if ($message === null) {
            return false;
        }

        $this->contactRepository->delete($uuid);

        return true;
    }
}