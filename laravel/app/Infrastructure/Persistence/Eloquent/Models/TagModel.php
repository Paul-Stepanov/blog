<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Infrastructure\Persistence\Casts\SlugCast;
use Database\Factories\TagFactory;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Eloquent Model for Tag persistence.
 *
 * Represents tags for labeling and grouping articles.
 */
final class TagModel extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'tags';

    /**
     * @var class-string<Factory>
     */
    protected static $factory = TagFactory::class;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'slug',
    ];

    /**
     * @var array<string, class-string<CastsAttributes>|string>
     */
    protected $casts = [
        'slug' => SlugCast::class,
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
        );
    }

    /**
     * Scope for ordering by name.
     */
    public function scopeOrderedByName(Builder $query): Builder
    {
        return $query->orderBy('name', 'asc');
    }

    /**
     * Scope for popular tags (by article count).
     */
    public function scopePopular(Builder $query, int $limit = 10): Builder
    {
        return $query->withCount('articles')
            ->orderBy('articles_count', 'desc')
            ->limit($limit);
    }

    /**
     * Find by UUID.
     */
    public function scopeByUuid(Builder $query, string $uuid): Builder
    {
        return $query->where('uuid', $uuid);
    }

    /**
     * Find by slug.
     */
    public function scopeBySlug(Builder $query, string $slug): Builder
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
