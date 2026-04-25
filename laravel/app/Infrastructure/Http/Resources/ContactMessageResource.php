<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Resources;

use App\Application\Contact\DTOs\ContactMessageDTO;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Contact Message Resource.
 *
 * Represents a contact form submission.
 */
final class ContactMessageResource extends JsonResource
{
    /**
     * @param ContactMessageDTO $resource
     */
    public function __construct(ContactMessageDTO $resource)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var ContactMessageDTO $message */
        $message = $this->resource;

        return [
            'id' => $message->id,
            'name' => $message->name,
            'email' => $message->email,
            'subject' => $message->subject,
            'message' => $message->message,
            'is_read' => $message->isRead,
            'ip_address' => $message->ipAddress,
            'user_agent' => $message->userAgent,
            'created_at' => $message->createdAt,
        ];
    }
}