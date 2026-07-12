<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Infrastructure\Persistence\Casts\SlugCast;
use Database\Factories\CategoryFactory;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent Model for Category persistence.
 *
 * Represents article categories for organization.
 */
final class CategoryModel extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'categories';

    /**
     * @var class-string<Factory>
     */
    protected static $factory = CategoryFactory::class;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
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
     * Get the articles for the category.
     *
     * @return HasMany<ArticleModel, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(ArticleModel::class, 'category_uuid', 'uuid');
    }

    /**
     * Get published articles count for this category.
     */
    public function getPublishedArticlesCountAttribute(): int
    {
        return $this->articles()->published()->count();
    }

    /**
     * Scope for ordering by name.
     */
    public function scopeOrderedByName(Builder $query): Builder
    {
        return $query->orderBy('name', 'asc');
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
     * Scope for categories with published articles only.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithPublishedArticles(Builder $query): Builder
    {
        return $query->whereHas('articles', $this->filterPublished(...));
    }

    /**
     * Apply the ArticleModel "published" scope inside a relation subquery.
     *
     * @param  Builder<ArticleModel>  $query
     */
    private function filterPublished(Builder $query): void
    {
        $query->published();
    }

    /**
     * Get route key name for route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
