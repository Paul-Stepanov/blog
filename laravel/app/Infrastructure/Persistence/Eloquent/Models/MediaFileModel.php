<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Database\Factories\MediaFileFactory;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent Model for MediaFile persistence.
 *
 * Represents uploaded files (images, documents, videos).
 */
final class MediaFileModel extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'media_files';

    /**
     * @var class-string<Factory>
     */
    protected static $factory = MediaFileFactory::class;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'uploader_uuid',
        'filename',
        'path',
        'url',
        'mime_type',
        'size_bytes',
        'width',
        'height',
        'alt_text',
    ];

    /**
     * @var array<string, class-string<CastsAttributes>|string>
     */
    protected $casts = [
        'size_bytes' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who uploaded this file.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'uploader_uuid', 'uuid');
    }

    /**
     * Get the articles using this file as cover image.
     */
    public function articles(): HasMany
    {
        return $this->hasMany(ArticleModel::class, 'cover_image_uuid', 'uuid');
    }

    /**
     * Scope for images only.
     */
    public function scopeImages(Builder $query): Builder
    {
        return $query->where('mime_type', 'LIKE', 'image/%');
    }

    /**
     * Scope for documents only.
     */
    public function scopeDocuments(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('mime_type', 'LIKE', 'application/%')
                ->orWhere('mime_type', 'LIKE', 'text/%');
        });
    }

    /**
     * Scope for videos only.
     */
    public function scopeVideos(Builder $query): Builder
    {
        return $query->where('mime_type', 'LIKE', 'video/%');
    }

    /**
     * Scope for specific MIME type.
     */
    public function scopeByMimeType(Builder $query, string $mimeType): Builder
    {
        return $query->where('mime_type', $mimeType);
    }

    /**
     * Scope for recent uploads.
     */
    public function scopeRecent(Builder $query, int $limit = 10): Builder
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Scope for search by filename.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('filename', 'LIKE', "%{$term}%")
                ->orWhere('alt_text', 'LIKE', "%{$term}%");
        });
    }

    /**
     * Find by UUID.
     */
    public function scopeByUuid(Builder $query, string $uuid): Builder
    {
        return $query->where('uuid', $uuid);
    }

    /**
     * Find by path.
     */
    public function scopeByPath(Builder $query, string $path): Builder
    {
        return $query->where('path', $path);
    }

    /**
     * Check if this is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if this is a video.
     */
    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    /**
     * Check if this is a document.
     */
    public function isDocument(): bool
    {
        return str_starts_with($this->mime_type, 'application/')
            || str_starts_with($this->mime_type, 'text/');
    }

    /**
     * Get file size in human-readable format.
     */
    public function getSizeHumanAttribute(): string
    {
        $bytes = $this->size_bytes;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Get aspect ratio (width / height).
     */
    public function getAspectRatio(): ?float
    {
        if ($this->width === null || $this->height === null || $this->height === 0) {
            return null;
        }

        return $this->width / $this->height;
    }

    /**
     * Get route key name for route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
