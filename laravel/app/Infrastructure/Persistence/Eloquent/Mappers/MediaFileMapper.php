<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Mappers;

use App\Domain\Media\Entities\MediaFile;
use App\Domain\Media\ValueObjects\ImageDimensions;
use App\Infrastructure\Persistence\Eloquent\Models\MediaFileModel;

/**
 * Mapper for MediaFile Entity <-> MediaFileModel.
 */
final class MediaFileMapper
{
    use BaseMapper;

    /**
     * Convert Eloquent Model to Domain Entity.
     */
    public function toDomain(MediaFileModel $model): MediaFile
    {
        return MediaFile::reconstitute(
            id: $this->mapUuid($model->uuid),
            filename: $model->filename,
            path: $this->mapFilePath($model->path),
            mimeType: $this->mapMimeType($model->mime_type),
            sizeBytes: $model->size_bytes,
            dimensions: $this->mapDimensions($model->width, $model->height),
            altText: $model->alt_text ?? '',
            timestamps: $this->mapTimestamps($model),
        );
    }

    /**
     * Convert Domain Entity to Eloquent data array.
     *
     * @return array<string, mixed>
     */
    public function toEloquent(MediaFile $entity): array
    {
        return [
            'uuid' => $entity->getId()->getValue(),
            'filename' => $entity->getFilename(),
            'path' => $this->getFilePathValue($entity->getPath()),
            'mime_type' => $this->getMimeTypeValue($entity->getMimeType()),
            'size_bytes' => $entity->getSizeBytes(),
            'width' => $entity->getDimensions()?->getWidth(),
            'height' => $entity->getDimensions()?->getHeight(),
            'alt_text' => $entity->getAltText(),
            'created_at' => $entity->getTimestamps()->getCreatedAt(),
            'updated_at' => $entity->getTimestamps()->getUpdatedAt(),
        ];
    }

    /**
     * Convert collection of models to domain entities.
     *
     * @param MediaFileModel[] $models
     * @return MediaFile[]
     */
    public function toDomainCollection(array $models): array
    {
        return array_map(
            fn(MediaFileModel $model): MediaFile => $this->toDomain($model),
            $models
        );
    }

    /**
     * Map width and height to ImageDimensions VO.
     */
    private function mapDimensions(?int $width, ?int $height): ?ImageDimensions
    {
        if ($width === null || $height === null) {
            return null;
        }

        return ImageDimensions::fromIntegers($width, $height);
    }
}