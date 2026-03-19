<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent Model for MediaFile persistence.
 *
 * Represents uploaded files (images, documents, videos).
 */
final class MediaFileModel extends Model
{
    /**
     * @var string
     */
    protected $table = 'media_files';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'filename',
        'path',
        'url',
        'mime_type',
        'size_bytes',
        'width',
        'height',
        'alt_text',
        'uploader_id',
    ];

    /**
     * @var array<string, string>
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
        return $this->belongsTo(UserModel::class, 'uploader_id', 'id');
    }

    /**
     * Scope for images only.
     */
    public function scopeImages(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('mime_type', 'LIKE', 'image/%');
    }

    /**
     * Scope for documents only.
     */
    public function scopeDocuments(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where(function ($q) {
            $q->where('mime_type', 'LIKE', 'application/%')
                ->orWhere('mime_type', 'LIKE', 'text/%');
        });
    }

    /**
     * Scope for videos only.
     */
    public function scopeVideos(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('mime_type', 'LIKE', 'video/%');
    }

    /**
     * Scope for specific MIME type.
     */
    public function scopeByMimeType(\Illuminate\Database\Eloquent\Builder $query, string $mimeType): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('mime_type', $mimeType);
    }

    /**
     * Scope for recent uploads.
     */
    public function scopeRecent(\Illuminate\Database\Eloquent\Builder $query, int $limit = 10): \Illuminate\Database\Eloquent\Builder
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    /**
     * Scope for search by filename.
     */
    public function scopeSearch(\Illuminate\Database\Eloquent\Builder $query, string $term): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('filename', 'LIKE', "%{$term}%")
                ->orWhere('alt_text', 'LIKE', "%{$term}%");
        });
    }

    /**
     * Find by UUID.
     */
    public function scopeByUuid(\Illuminate\Database\Eloquent\Builder $query, string $uuid): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('uuid', $uuid);
    }

    /**
     * Find by path.
     */
    public function scopeByPath(\Illuminate\Database\Eloquent\Builder $query, string $path): \Illuminate\Database\Eloquent\Builder
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

        return round($bytes, 2) . ' ' . $units[$i];
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