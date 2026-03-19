<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Api;

use App\Domain\Article\Repositories\CategoryRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Infrastructure\Http\Resources\CategoryCollectionResource;
use Illuminate\Http\JsonResponse;

/**
 * Public Category API Controller.
 *
 * Handles read-only category operations.
 */
final class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
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
     *     @OA\Response(response=200, description="List of categories")
     * )
     */
    public function getCategoriesWithArticles(): JsonResponse
    {
        $categories = $this->categoryRepository->getWithPublishedArticles();

        return response()->json([
            'success' => true,
            'data' => new CategoryCollectionResource($categories),
        ]);
    }

    /**
     * Get a single category by slug with its articles.
     *
     * @OA\Get(
     *     path="/api/categories/{slug}",
     *     summary="Get category by slug",
     *     tags={"Categories"},
     *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Category details"),
     *     @OA\Response(response=404, description="Category not found")
     * )
     */
    public function getCategoryBySlug(string $slug): JsonResponse
    {
        $category = $this->categoryRepository->findBySlug($slug);

        if ($category === null) {
            return response()->json([
                'success' => false,
                'error' => 'category_not_found',
                'message' => "Category not found with slug: {$slug}",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $category->getId()->getValue(),
                'name' => $category->getName(),
                'slug' => $category->getSlug()->getValue(),
                'description' => $category->getDescription(),
            ],
        ]);
    }
}