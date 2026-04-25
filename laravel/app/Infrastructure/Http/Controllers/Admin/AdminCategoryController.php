<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controllers\Admin;

use App\Application\Article\Commands\CreateCategoryCommand;
use App\Application\Article\Commands\UpdateCategoryCommand;
use App\Application\Article\Services\CategoryService;
use App\Http\Controllers\Controller;
use App\Infrastructure\Http\Requests\Admin\CategoryRequest;
use App\Infrastructure\Http\Resources\CategoryResource;
use Illuminate\Http\JsonResponse;

/**
 * Admin Category Management Controller.
 *
 * Handles CRUD operations for categories in the admin panel.
 */
final class AdminCategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService,
    ) {}

    /**
     * Get all categories.
     *
     * @OA\Get(
     *     path="/api/admin/categories",
     *     summary="Get all categories (admin)",
     *     tags={"Admin Categories"},
     *     @OA\Response(response=200, description="List of categories")
     * )
     */
    public function getAllCategories(): JsonResponse
    {
        $categories = $this->categoryService->getAllCategories();

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories),
        ]);
    }

    /**
     * Get a single category by ID.
     *
     * @OA\Get(
     *     path="/api/admin/categories/{id}",
     *     summary="Get category by ID (admin)",
     *     tags={"Admin Categories"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string", format="uuid")),
     *     @OA\Response(response=200, description="Category details"),
     *     @OA\Response(response=404, description="Category not found")
     * )
     */
    public function getCategoryById(string $id): JsonResponse
    {
        $category = $this->categoryService->getCategoryById($id);

        if ($category === null) {
            return response()->json([
                'success' => false,
                'error' => 'category_not_found',
                'message' => "Category not found with ID: {$id}",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
        ]);
    }

    /**
     * Create a new category.
     *
     * @OA\Post(
     *     path="/api/admin/categories",
     *     summary="Create category",
     *     tags={"Admin Categories"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"name"},
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="slug", type="string"),
     *         @OA\Property(property="description", type="string")
     *     )),
     *     @OA\Response(response=201, description="Category created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function createCategory(CategoryRequest $request): JsonResponse
    {
        $command = CreateCategoryCommand::fromRequest($request);
        $category = $this->categoryService->createCategory($command);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'data' => new CategoryResource($category),
        ], 201);
    }

    /**
     * Update an existing category.
     *
     * @OA\Put(
     *     path="/api/admin/categories/{id}",
     *     summary="Update category",
     *     tags={"Admin Categories"},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="slug", type="string"),
     *         @OA\Property(property="description", type="string")
     *     )),
     *     @OA\Response(response=200, description="Category updated"),
     *     @OA\Response(response=404, description="Category not found")
     * )
     */
    public function updateCategory(CategoryRequest $request, string $id): JsonResponse
    {
        $command = UpdateCategoryCommand::fromRequest($request, $id);
        $category = $this->categoryService->updateCategory($command);

        if ($category === null) {
            return response()->json([
                'success' => false,
                'error' => 'category_not_found',
                'message' => "Category not found with ID: {$id}",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'data' => new CategoryResource($category),
        ]);
    }

    /**
     * Delete a category.
     *
     * @OA\Delete(
     *     path="/api/admin/categories/{id}",
     *     summary="Delete category",
     *     tags={"Admin Categories"},
     *     @OA\Response(response=200, description="Category deleted"),
     *     @OA\Response(response=404, description="Category not found")
     * )
     */
    public function deleteCategory(string $id): JsonResponse
    {
        $deleted = $this->categoryService->deleteCategory($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'error' => 'category_not_found',
                'message' => "Category not found with ID: {$id}",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ]);
    }
}