<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Mappers;

use App\Domain\Article\ValueObjects\Slug;
use App\Domain\Contact\ValueObjects\{Email, IPAddress};
use App\Domain\Media\ValueObjects\{FilePath, MimeType};
use App\Domain\Settings\ValueObjects\SettingKey;
use App\Domain\Shared\{Timestamps, Uuid};
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Base Mapper Trait.
 *
 * Provides common mapping methods for transforming between
 * Domain Value Objects and database primitives.
 */
trait BaseMapper
{
    /**
     * Map a string or Uuid object to Domain Uuid VO.
     */
    protected function mapUuid(Uuid|string $value): Uuid
    {
        if ($value instanceof Uuid) {
            return $value;
        }

        return Uuid::fromString($value);
    }

    /**
     * Map a nullable string or Uuid object to Domain Uuid VO.
     */
    protected function mapNullableUuid(Uuid|string|null $value): ?Uuid
    {
        if ($value === null) {
            return null;
        }

        return $this->mapUuid($value);
    }

    /**
     * Get string value from Uuid for database storage.
     */
    protected function getUuidValue(?Uuid $uuid): ?string
    {
        return $uuid?->getValue();
    }

    /**
     * Extract Timestamps from Eloquent model.
     */
    protected function mapTimestamps(Model $model): Timestamps
    {
        $createdAt = $model->created_at?->toDateTimeString() ?? 'now';
        $updatedAt = $model->updated_at?->toDateTimeString() ?? 'now';

        return Timestamps::fromStrings(
            createdAt: $createdAt,
            updatedAt: $updatedAt
        );
    }

    /**
     * Map a string or Slug object to Domain Slug VO.
     */
    protected function mapSlug(Slug|string $value): Slug
    {
        if ($value instanceof Slug) {
            return $value;
        }

        return Slug::fromString($value);
    }

    /**
     * Get string value from Slug for database storage.
     */
    protected function getSlugValue(Slug $slug): string
    {
        return $slug->getValue();
    }

    /**
     * Map a string or Email object to Domain Email VO.
     */
    protected function mapEmail(Email|string $value): Email
    {
        if ($value instanceof Email) {
            return $value;
        }

        return Email::fromString($value);
    }

    /**
     * Get string value from Email for database storage.
     */
    protected function getEmailValue(Email $email): string
    {
        return $email->getValue();
    }

    /**
     * Map a string or IPAddress object to Domain IPAddress VO.
     */
    protected function mapIPAddress(IPAddress|string $value): IPAddress
    {
        if ($value instanceof IPAddress) {
            return $value;
        }

        return IPAddress::fromString($value);
    }

    /**
     * Get string value from IPAddress for database storage.
     */
    protected function getIPAddressValue(IPAddress $ipAddress): string
    {
        return $ipAddress->getValue();
    }

    /**
     * Map a string or MimeType object to Domain MimeType VO.
     */
    protected function mapMimeType(MimeType|string $value): MimeType
    {
        if ($value instanceof MimeType) {
            return $value;
        }

        return MimeType::fromString($value);
    }

    /**
     * Get string value from MimeType for database storage.
     */
    protected function getMimeTypeValue(MimeType $mimeType): string
    {
        return $mimeType->getValue();
    }

    /**
     * Map a string or FilePath object to Domain FilePath VO.
     */
    protected function mapFilePath(FilePath|string $value): FilePath
    {
        if ($value instanceof FilePath) {
            return $value;
        }

        return FilePath::fromString($value);
    }

    /**
     * Get string value from FilePath for database storage.
     */
    protected function getFilePathValue(FilePath $filePath): string
    {
        return $filePath->getValue();
    }

    /**
     * Map a string or SettingKey object to Domain SettingKey VO.
     */
    protected function mapSettingKey(SettingKey|string $value): SettingKey
    {
        if ($value instanceof SettingKey) {
            return $value;
        }

        return SettingKey::fromString($value);
    }

    /**
     * Get string value from SettingKey for database storage.
     */
    protected function getSettingKeyValue(SettingKey $settingKey): string
    {
        return $settingKey->getValue();
    }

    /**
     * Format DateTimeImmutable for database storage.
     */
    protected function formatDateTime(?DateTimeImmutable $dateTime): ?string
    {
        return $dateTime?->format('Y-m-d H:i:s');
    }

    /**
     * Parse DateTimeImmutable from database string.
     */
    protected function parseDateTime(?string $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (\Exception) {
            return null;
        }
    }
}