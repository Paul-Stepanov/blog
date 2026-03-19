<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\Article\Exceptions\ArticleNotFoundException;
use App\Application\Article\Queries\{GetArticleBySlugQuery, GetPublishedArticlesQuery};
use App\Application\Article\Services\ArticleService;
use App\Domain\Article\ValueObjects\Slug;
use App\Http\Controllers\Controller;
use App\Infrastructure\Http\Resources\{ArticleListResource, PaginatedResource};
use Illuminate\Http\{JsonResponse, Request};

/**
 * Public Article API Controller.
 *
 * Handles public read-only article operations.
 * All endpoints are accessible without authentication.
 */
final class ArticleController extends Controller
{
    public function __construct(
        private readonly ArticleService $articleService,
    ) {}

    /**
     * Get paginated list of published articles.
     *
     * Supports optional filtering by search term and category.
     *
     * @OA\Get(
     *     path="/api/articles",
     *     summary="Get published articles with optional filters",
     *     tags={"Articles"},
     *
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", minimum=1)),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", minimum=1, maximum=50)),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string", maxLength=255)),
     *     @OA\Parameter(name="category_id", in="query", @OA\Schema(type="string", format="uuid")),
     *
     *     @OA\Response(response=200, description="Paginated list of articles"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function getPublishedArticles(Request $request): JsonResponse
    {
        $query = new GetPublishedArticlesQuery(
            page: (int) $request->input('page', 1),
            perPage: min((int) $request->input('per_page', 12), 50),
            categoryId: $request->input('category_id'),
            searchTerm: $request->input('search'),
        );

        $result = $this->articleService->getPublishedArticles($query);

        return response()->json(
            new PaginatedResource($result, ArticleListResource::class)
        );
    }

    /**
     * Get a single published article by its slug.
     *
     * @OA\Get(
     *     path="/api/articles/{slug}",
     *     summary="Get article by slug",
     *     tags={"Articles"},
     *
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Article details"),
     *     @OA\Response(response=404, description="Article not found")
     * )
     */
    public function getArticleBySlug(string $slug): JsonResponse
    {
        try {
            $query = new GetArticleBySlugQuery(
                slug: Slug::fromString($slug)
            );

            $article = $this->articleService->getArticleBySlug($query);

            return response()->json([
                'success' => true,
                'data' => $article->toArray(),
            ]);
        } catch (ArticleNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'article_not_found',
                'message' => $e->getMessage(),
            ], 404);
        }
    }
}