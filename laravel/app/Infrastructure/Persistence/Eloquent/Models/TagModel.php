<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Eloquent Model for Tag persistence.
 *
 * Represents tags for labeling and grouping articles.
 */
final class TagModel extends Model
{
    /**
     * @var string
     */
    protected $table = 'tags';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'slug',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The articles that belong to the tag.
     */
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(
            ArticleModel::class,
            'article_tag',
            'tag_id',
            'article_id'
        )->withTimestamps();
    }

    /**
     * Scope for ordering by name.
     */
    public function scopeOrderedByName(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->orderBy('name', 'asc');
    }

    /**
     * Scope for popular tags (by article count).
     */
    public function scopePopular(\Illuminate\Database\Eloquent\Builder $query, int $limit = 10): \Illuminate\Database\Eloquent\Builder
    {
        return $query->withCount('articles')
            ->orderBy('articles_count', 'desc')
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