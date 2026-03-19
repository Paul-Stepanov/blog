<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Mappers;

use App\Domain\Contact\Entities\ContactMessage;
use App\Infrastructure\Persistence\Eloquent\Models\ContactMessageModel;

/**
 * Mapper for ContactMessage Entity <-> ContactMessageModel.
 */
final class ContactMessageMapper
{
    use BaseMapper;

    /**
     * Convert Eloquent Model to Domain Entity.
     */
    public function toDomain(ContactMessageModel $model): ContactMessage
    {
        return ContactMessage::reconstitute(
            id: $this->mapUuid($model->uuid),
            name: $model->name,
            email: $this->mapEmail($model->email),
            subject: $model->subject ?? '',
            message: $model->message,
            ipAddress: $this->mapIPAddress($model->ip_address),
            userAgent: $model->user_agent ?? '',
            isRead: (bool) $model->is_read,
            timestamps: $this->mapTimestamps($model),
        );
    }

    /**
     * Convert Domain Entity to Eloquent data array.
     *
     * @return array<string, mixed>
     */
    public function toEloquent(ContactMessage $entity): array
    {
        return [
            'uuid' => $entity->getId()->getValue(),
            'name' => $entity->getName(),
            'email' => $this->getEmailValue($entity->getEmail()),
            'subject' => $entity->getSubject(),
            'message' => $entity->getMessage(),
            'ip_address' => $this->getIPAddressValue($entity->getIpAddress()),
            'user_agent' => $entity->getUserAgent(),
            'is_read' => $entity->isRead(),
            'created_at' => $entity->getTimestamps()->getCreatedAt(),
            'updated_at' => $entity->getTimestamps()->getUpdatedAt(),
        ];
    }

    /**
     * Convert collection of models to domain entities.
     *
     * @param ContactMessageModel[] $models
     * @return ContactMessage[]
     */
    public function toDomainCollection(array $models): array
    {
        return array_map(
            fn(ContactMessageModel $model): ContactMessage => $this->toDomain($model),
            $models
        );
    }
}