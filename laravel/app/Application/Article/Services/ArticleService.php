<?php

declare(strict_types=1);

namespace App\Application\Article\Services;

use App\Application\Article\Commands\{ArchiveArticleCommand, CreateArticleCommand, PublishArticleCommand};
use App\Application\Article\DTOs\{ArticleDTO, ArticleListDTO};
use App\Application\Article\Exceptions\ArticleNotFoundException;
use App\Application\Article\Queries\{GetArticleBySlugQuery, GetPublishedArticlesQuery};
use App\Domain\Article\Entities\Article;
use App\Domain\Article\Repositories\ArticleRepositoryInterface;
use App\Domain\Article\ValueObjects\{ArticleContent, ArticleFilters, ArticleStatus, Slug};
use App\Domain\Shared\PaginatedResult;
use App\Domain\Shared\Uuid;

/**
 * Article Application Service.
 *
 * Orchestrates article-related use cases by coordinating
 * domain objects and repository operations.
 */
final readonly class ArticleService
{
    public function __construct(
        private ArticleRepositoryInterface $articleRepository,
    ) {}

    /**
     * Create a new draft article.
     */
    public function createArticle(CreateArticleCommand $command): ArticleDTO
    {
        $slug = $command->slug ?? Slug::fromTitle($command->title);
        $content = ArticleContent::fromString($command->content);

        $article = Article::createDraft(
            id: Uuid::generate(),
            title: $command->title,
            slug: $slug,
            content: $content,
            categoryId: $command->categoryId,
            authorId: $command->authorId,
        );

        $this->articleRepository->save($article);

        return ArticleDTO::fromEntity($article);
    }

    /**
     * Publish a draft article.
     *
     * @throws ArticleNotFoundException
     */
    public function publishArticle(PublishArticleCommand $command): ArticleDTO
    {
        $article = $this->findOrFail($command->articleId);

        $article->publish();

        $this->articleRepository->save($article);

        return ArticleDTO::fromEntity($article);
    }

    /**
     * Archive an article.
     *
     * @throws ArticleNotFoundException
     */
    public function archiveArticle(ArchiveArticleCommand $command): ArticleDTO
    {
        $article = $this->findOrFail($command->articleId);

        $article->archive();

        $this->articleRepository->save($article);

        return ArticleDTO::fromEntity($article);
    }

    /**
     * Get a single article by slug.
     *
     * @throws ArticleNotFoundException
     */
    public function getArticleBySlug(GetArticleBySlugQuery $query): ArticleDTO
    {
        $article = $this->articleRepository->findBySlug($query->slug->getValue());

        if ($article === null) {
            throw ArticleNotFoundException::bySlug($query->slug->getValue());
        }

        return ArticleDTO::fromEntity($article);
    }

    /**
     * Get paginated list of published articles with optional filters.
     *
     * Uses Query Object Pattern via ArticleFilters.
     *
     * @return PaginatedResult<ArticleListDTO>
     */
    public function getPublishedArticles(GetPublishedArticlesQuery $query): PaginatedResult
    {
        $filters = ArticleFilters::create([
            'search' => $query->searchTerm,
            'category_id' => $query->categoryId,
            'status' => ArticleStatus::PUBLISHED->value,
        ]);

        $result = $this->articleRepository->findByFilters(
            filters: $filters,
            page: $query->page,
            perPage: $query->perPage,
        );

        return $result->map(
            fn(Article $article) => ArticleListDTO::fromEntity($article)
        );
    }

    /**
     * Get all articles for admin panel with optional filters.
     *
     * @return PaginatedResult<ArticleListDTO>
     */
    public function getArticlesForAdmin(
        ?string $search = null,
        ?string $status = null,
        ?string $categoryId = null,
        int $page = 1,
        int $perPage = 20
    ): PaginatedResult {
        $filters = ArticleFilters::create([
            'search' => $search,
            'status' => $status,
            'category_id' => $categoryId,
        ]);

        $result = $this->articleRepository->findByFilters(
            filters: $filters,
            page: $page,
            perPage: $perPage,
        );

        return $result->map(
            fn(Article $article) => ArticleListDTO::fromEntity($article)
        );
    }

    /**
     * Find article or throw exception.
     *
     * @throws ArticleNotFoundException
     */
    private function findOrFail(Uuid $articleId): Article
    {
        $article = $this->articleRepository->findById($articleId);

        if ($article === null) {
            throw ArticleNotFoundException::byId($articleId->getValue());
        }

        return $article;
    }
}