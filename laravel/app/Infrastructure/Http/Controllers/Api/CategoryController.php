<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\Article\Services\CategoryService;
use App\Http\Controllers\Controller;
use App\Infrastructure\Http\Resources\CategoryCollectionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public Category API Controller.
 *
 * Handles read-only category operations.
 */
final class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService,
    ) {}

    /**
     * Get all categories with published articles.
     *
     * Returns only categories that have at least one published article.
     *
     * @OA\Get(
     *     path="/api/categories",
     *     summary="Get categories with published articles",
     *     tags={"Categories"},
     *
     *     @OA\Parameter(name="limit", in="query", @OA\Schema(type="integer", minimum=1, maximum=500)),
     *
     *     @OA\Response(response=200, description="List of categories")
     * )
     */
    public function getCategoriesWithArticles(Request $request): JsonResponse
    {
        $limit = $this->resolveListLimit($request);
        $categories = $this->categoryService->getCategoriesWithArticles($limit);

        return response()->json([
            'success' => true,
            'data' => new CategoryCollectionResource($categories),
        ]);
    }

    /**
     * Get a single category by slug.
     *
     * @OA\Get(
     *     path="/api/categories/{slug}",
     *     summary="Get category by slug",
     *     tags={"Categories"},
     *
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="Category details"),
     *     @OA\Response(response=404, description="Category not found")
     * )
     */
    public function getCategoryBySlug(string $slug): JsonResponse
    {
        $category = $this->categoryService->getCategoryBySlug($slug);

        if ($category === null) {
            return response()->json([
                'success' => false,
                'error' => 'category_not_found',
                'message' => "Category not found with slug: {$slug}",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $category->toArray(),
        ]);
    }

    /**
     * Resolve a capped list-limit query parameter (?limit, default 100, max 500).
     */
    private function resolveListLimit(Request $request): int
    {
        return min(max((int) $request->input('limit', 100), 1), 500);
    }
}
