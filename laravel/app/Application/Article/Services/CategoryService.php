<?php

declare(strict_types=1);

namespace App\Application\Article\Services;

use App\Application\Article\Commands\{CreateCategoryCommand, UpdateCategoryCommand};
use App\Application\Article\DTOs\{CategoryDTO, CategoryListDTO};
use App\Domain\Article\Entities\Category;
use App\Domain\Article\Repositories\CategoryRepositoryInterface;
use App\Domain\Article\ValueObjects\Slug;
use App\Domain\Shared\Uuid;

/**
 * Category Application Service.
 *
 * Orchestrates category-related use cases by coordinating
 * domain objects and repository operations.
 */
final readonly class CategoryService
{
    public function __construct(
        private CategoryRepositoryInterface $categoryRepository,
    ) {}

    /**
     * Get all categories for admin panel.
     *
     * @return array<CategoryListDTO>
     */
    public function getAllCategories(): array
    {
        $categoriesWithCount = $this->categoryRepository->findAllWithArticleCount();

        return array_map(
            fn(array $data) => CategoryListDTO::fromArrayData($data),
            $categoriesWithCount
        );
    }

    /**
     * Get category by ID.
     */
    public function getCategoryById(string $id): ?CategoryDTO
    {
        $category = $this->categoryRepository->findById(Uuid::fromString($id));

        if ($category === null) {
            return null;
        }

        return CategoryDTO::fromEntity($category);
    }

    /**
     * Get category by slug.
     */
    public function getCategoryBySlug(string $slug): ?CategoryDTO
    {
        $category = $this->categoryRepository->findBySlug($slug);

        if ($category === null) {
            return null;
        }

        return CategoryDTO::fromEntity($category);
    }

    /**
     * Create a new category.
     */
    public function createCategory(CreateCategoryCommand $command): CategoryDTO
    {
        $slug = $command->slug ?? Slug::fromTitle($command->name);

        $category = Category::create(
            id: Uuid::generate(),
            name: $command->name,
            slug: $slug,
            description: $command->description,
        );

        $this->categoryRepository->save($category);

        return CategoryDTO::fromEntity($category);
    }

    /**
     * Update an existing category.
     */
    public function updateCategory(UpdateCategoryCommand $command): ?CategoryDTO
    {
        $category = $this->categoryRepository->findById(
            Uuid::fromString($command->categoryId)
        );

        if ($category === null) {
            return null;
        }

        // Update name and optionally slug
        if ($command->name !== null) {
            $newSlug = $command->slug !== null
                ? Slug::fromString($command->slug)
                : null;

            $category->rename($command->name, $newSlug);
        }

        // Update description if provided
        if ($command->description !== null) {
            $category->updateDescription($command->description);
        }

        $this->categoryRepository->save($category);

        return CategoryDTO::fromEntity($category);
    }

    /**
     * Delete a category.
     */
    public function deleteCategory(string $id): bool
    {
        $categoryId = Uuid::fromString($id);
        $category = $this->categoryRepository->findById($categoryId);

        if ($category === null) {
            return false;
        }

        $this->categoryRepository->delete($categoryId);

        return true;
    }
}