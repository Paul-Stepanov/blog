<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\CategoryModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<CategoryModel>
 */
final class CategoryFactory extends Factory
{
    protected $model = CategoryModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);
        $slug = strtolower(str_replace(' ', '-', $name));

        return [
            'uuid' => fake()->uuid(),
            'name' => $name,
            'slug' => $slug,
            'description' => fake()->sentence(),
        ];
    }
}