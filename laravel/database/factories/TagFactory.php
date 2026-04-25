<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\TagModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<TagModel>
 */
final class TagFactory extends Factory
{
    protected $model = TagModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();
        $slug = strtolower($name) . '-' . fake()->unique()->randomNumber(4);

        return [
            'uuid' => fake()->uuid(),
            'name' => $name,
            'slug' => $slug,
        ];
    }
}