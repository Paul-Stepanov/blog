<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Article\ValueObjects\ArticleStatus;
use App\Infrastructure\Persistence\Eloquent\Models\ArticleModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<ArticleModel>
 */
final class ArticleFactory extends Factory
{
    protected $model = ArticleModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(4);
        $slug = strtolower(str_replace(' ', '-', trim(preg_replace('/[^\w\s-]/', '', $title))));

        return [
            'uuid' => fake()->uuid(),
            'title' => $title,
            'slug' => $slug,
            'content' => fake()->paragraphs(3, true),
            'excerpt' => fake()->sentence(),
            'status' => ArticleStatus::DRAFT->value,
            'category_uuid' => null,
            'cover_image_uuid' => null,
            'author_uuid' => null,
            'published_at' => null,
        ];
    }

    /**
     * Indicate that the article is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::PUBLISHED->value,
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the article is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArticleStatus::ARCHIVED->value,
        ]);
    }
}