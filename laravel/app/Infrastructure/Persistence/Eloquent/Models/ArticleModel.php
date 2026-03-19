<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Eloquent Model for Article persistence.
 *
 * This model represents the database table structure and handles
 * the relationship mapping between Domain entities and persistence.
 */
final class ArticleModel extends Model
{
    /**
     * @var string
     */
    protected $table = 'articles';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'title',
        'slug',
        'content',
        'excerpt',
        'status',
        'category_id',
        'category_uuid',
        'author_id',
        'author_uuid',
        'cover_image_id',
        'cover_image_uuid',
        'published_at',
    ];

    /**
     * @var array<string, class-string<\Illuminate\Contracts\Database\Eloquent\CastsAttributes>|string>
     */
    protected $casts = [
        'uuid' => \App\Infrastructure\Persistence\Casts\UuidCast::class,
        'slug' => \App\Infrastructure\Persistence\Casts\SlugCast::class,
        'status' => \App\Domain\Article\ValueObjects\ArticleStatus::class,
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the category that owns the article.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CategoryModel::class, 'category_id', 'id');
    }

    /**
     * Get the author (user) that owns the article.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'author_id', 'id');
    }

    /**
     * Get the cover image for the article.
     */
    public function coverImage(): BelongsTo
    {
        return $this->belongsTo(MediaFileModel::class, 'cover_image_id', 'id');
    }

    /**
     * The tags that belong to the article.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            TagModel::class,
            'article_tag',
            'article_id',
            'tag_id'
        )->withTimestamps();
    }

    /**
     * Scope for published articles.
     */
    public function scopePublished(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope for draft articles.
     */
    public function scopeDraft(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope for archived articles.
     */
    public function scopeArchived(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('status', 'archived');
    }

    /**
     * Scope for searching articles.
     */
    public function scopeSearch(\Illuminate\Database\Eloquent\Builder $query, string $term): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'LIKE', "%{$term}%")
                ->orWhere('content', 'LIKE', "%{$term}%")
                ->orWhere('excerpt', 'LIKE', "%{$term}%");
        });
    }

    /**
     * Scope for ordering by published date.
     */
    public function scopeLatestPublished(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->orderBy('published_at', 'desc');
    }

    /**
     * Scope for featured articles.
     */
    public function scopeFeatured(\Illuminate\Database\Eloquent\Builder $query, int $limit = 3): \Illuminate\Database\Eloquent\Builder
    {
        return $query->published()
            ->latestPublished()
            ->limit($limit);
    }

    /**
     * Find by UUID.
     */
    public function scopeByUuid(\Illuminate\Database\Eloquent\Builder $query, string $uuid): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('uuid', $uuid);
    }

    /**
     * Find by slug.
     */
    public function scopeBySlug(\Illuminate\Database\Eloquent\Builder $query, string $slug): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('slug', $slug);
    }

    /**
     * Get route key name for route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}