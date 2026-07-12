<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Domain\Article\ValueObjects\ArticleStatus;
use App\Infrastructure\Persistence\Casts\SlugCast;
use Database\Factories\ArticleFactory;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'articles';

    /**
     * @var class-string<Factory>
     */
    protected static $factory = ArticleFactory::class;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'title',
        'slug',
        'content',
        'excerpt',
        'status',
        'category_uuid',
        'author_uuid',
        'cover_image_uuid',
        'published_at',
    ];

    /**
     * @var array<string, class-string<CastsAttributes>|string>
     */
    protected $casts = [
        'slug' => SlugCast::class,
        'status' => ArticleStatus::class,
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the category that owns the article.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(CategoryModel::class, 'category_uuid', 'uuid');
    }

    /**
     * Get the author (user) that owns the article.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'author_uuid', 'uuid');
    }

    /**
     * Get the cover image for the article.
     */
    public function coverImage(): BelongsTo
    {
        return $this->belongsTo(MediaFileModel::class, 'cover_image_uuid', 'uuid');
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
        );
    }

    /**
     * Scope for published articles.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope for draft articles.
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope for archived articles.
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', 'archived');
    }

    /**
     * Scope for searching articles.
     */
    public function scopeSearch(Builder $query, string $term): Builder
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
    public function scopeLatestPublished(Builder $query): Builder
    {
        return $query->orderBy('published_at', 'desc');
    }

    /**
     * Scope for featured articles.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeFeatured(Builder $query, int $limit = 3): Builder
    {
        return $query->published()
            ->latestPublished()
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
