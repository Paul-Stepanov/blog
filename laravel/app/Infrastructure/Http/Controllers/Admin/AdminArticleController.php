<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Admin;

use App\Application\Article\Commands\{
    ArchiveArticleCommand,
    CreateArticleCommand,
    PublishArticleCommand,
    UpdateArticleCommand
};
use App\Application\Article\Services\ArticleService;
use App\Http\Controllers\Controller;
use App\Infrastructure\Http\Requests\{Api\CreateArticleRequest, Api\UpdateArticleRequest, Admin\ArticleTagsRequest};
use App\Infrastructure\Http\Resources\{ArticleResource, ArticleListResource};
use Illuminate\Http\{JsonResponse, Request};

/**
 * Admin Article Management Controller.
 *
 * Handles CRUD operations for articles in the admin panel.
 */
final class AdminArticleController extends Controller
{
    public function __construct(
        private readonly ArticleService $articleService,
    ) {}

    /**
     * Get all articles (including drafts).
     *
     * @OA\Get(
     *     path="/api/admin/articles",
     *     summary="Get all articles (admin)",
     *     tags={"Admin Articles"},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", maximum=100)),
     *     @OA\Response(response=200, description="List of articles")
     * )
     */
    public function getAllArticles(Request $request): JsonResponse
    {
        $page = max((int) $request->input('page', 1), 1);
        $perPage = min((int) $request->input('per_page', 15), 100);

        $result = $this->articleService->getAllArticles($page, $perPage);

        return response()->json([
            'success' => true,
            'data' => ArticleListResource::collection($result->items),
            'meta' => [
                'current_page' => $result->page,
                'last_page' => $result->lastPage,
                'per_page' => $result->perPage,
                'total' => $result->total,
                'from' => ($result->page - 1) * $result->perPage + 1,
                'to' => min($result->page * $result->perPage, $result->total),
            ],
        ]);
    }

    /**
     * Get a single article by ID.
     *
     * @OA\Get(
     *     path="/api/admin/articles/{id}",
     *     summary="Get article by ID (admin)",
     *     tags={"Admin Articles"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Article details"),
     *     @OA\Response(response=404, description="Article not found")
     * )
     */
    public function getArticleById(string $id): JsonResponse
    {
        $article = $this->articleService->getArticleById($id);

        if ($article === null) {
            return response()->json([
                'success' => false,
                'error' => 'article_not_found',
                'message' => "Article not found with ID: {$id}",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new ArticleResource($article),
        ]);
    }

    /**
     * Create a new article.
     *
     * @OA\Post(
     *     path="/api/admin/articles",
     *     summary="Create article",
     *     tags={"Admin Articles"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"title", "content"},
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="content", type="string"),
     *         @OA\Property(property="slug", type="string"),
     *         @OA\Property(property="excerpt", type="string"),
     *         @OA\Property(property="category_id", type="string", format="uuid"),
     *         @OA\Property(property="cover_image_id", type="string", format="uuid"),
     *         @OA\Property(property="tags", type="array", @OA\Items(type="string", format="uuid"))
     *     )),
     *     @OA\Response(response=201, description="Article created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createArticle(CreateArticleRequest $request): JsonResponse
    {
        $command = CreateArticleCommand::fromRequest($request);
        $article = $this->articleService->createArticle($command);

        return response()->json([
            'success' => true,
            'message' => 'Article created successfully.',
            'data' => new ArticleResource($article),
        ], 201);
    }

    /**
     * Update an existing article.
     *
     * @OA\Put(
     *     path="/api/admin/articles/{id}",
     *     summary="Update article",
     *     tags={"Admin Articles"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="content", type="string"),
     *         @OA\Property(property="slug", type="string"),
     *         @OA\Property(property="category_id", type="string", format="uuid"),
     *         @OA\Property(property="cover_image_id", type="string", format="uuid")
     *     )),
     *     @OA\Response(response=200, description="Article updated"),
     *     @OA\Response(response=404, description="Article not found")
     * )
     */
    public function updateArticle(UpdateArticleRequest $request, string $id): JsonResponse
    {
        $command = UpdateArticleCommand::fromRequest($request, $id);
        $article = $this->articleService->updateArticle($command);

        if ($article === null) {
            return response()->json([
                'success' => false,
                'error' => 'article_not_found',
                'message' => "Article not found with ID: {$id}",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Article updated successfully.',
            'data' => new ArticleResource($article),
        ]);
    }

    /**
     * Delete an article.
     *
     * @OA\Delete(
     *     path="/api/admin/articles/{id}",
     *     summary="Delete article",
     *     tags={"Admin Articles"},
     *     @OA\Response(response=200, description="Article deleted"),
     *     @OA\Response(response=404, description="Article not found")
     * )
     */
    public function deleteArticle(string $id): JsonResponse
    {
        $deleted = $this->articleService->deleteArticle($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'error' => 'article_not_found',
                'message' => "Article not found with ID: {$id}",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Article deleted successfully.',
        ]);
    }

    /**
     * Publish an article.
     *
     * @OA\Post(
     *     path="/api/admin/articles/{id}/publish",
     *     summary="Publish article",
     *     tags={"Admin Articles"},
     *     @OA\Response(response=200, description="Article published"),
     *     @OA\Response(response=404, description="Article not found")
     * )
     */
    public function publishArticle(string $id): JsonResponse
    {
        $command = PublishArticleCommand::fromId($id);
        $article = $this->articleService->publishArticle($command);

        if ($article === null) {
            return response()->json([
                'success' => false,
                'error' => 'article_not_found',
                'message' => "Article not found with ID: {$id}",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Article published successfully.',
            'data' => new ArticleResource($article),
        ]);
    }

    /**
     * Archive an article.
     *
     * @OA\Post(
     *     path="/api/admin/articles/{id}/archive",
     *     summary="Archive article",
     *     tags={"Admin Articles"},
     *     @OA\Response(response=200, description="Article archived"),
     *     @OA\Response(response=404, description="Article not found")
     * )
     */
    public function archiveArticle(string $id): JsonResponse
    {
        $command = ArchiveArticleCommand::fromId($id);
        $article = $this->articleService->archiveArticle($command);

        if ($article === null) {
            return response()->json([
                'success' => false,
                'error' => 'article_not_found',
                'message' => "Article not found with ID: {$id}",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Article archived successfully.',
            'data' => new ArticleResource($article),
        ]);
    }

    /**
     * Sync tags for an article.
     *
     * @OA\Put(
     *     path="/api/admin/articles/{id}/tags",
     *     summary="Sync article tags",
     *     tags={"Admin Articles"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"tags"},
     *         @OA\Property(property="tags", type="array", @OA\Items(type="string", format="uuid"))
     *     )),
     *     @OA\Response(response=200, description="Tags synced"),
     *     @OA\Response(response=404, description="Article not found")
     * )
     */
    public function syncArticleTags(ArticleTagsRequest $request, string $id): JsonResponse
    {
        $tags = $request->validated('tags');

        $article = $this->articleService->syncArticleTags($id, $tags);

        if ($article === null) {
            return response()->json([
                'success' => false,
                'error' => 'article_not_found',
                'message' => "Article not found with ID: {$id}",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tags synced successfully.',
            'data' => new ArticleResource($article),
        ]);
    }
}