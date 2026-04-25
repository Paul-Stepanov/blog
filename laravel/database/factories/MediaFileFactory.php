<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\MediaFileModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<MediaFileModel>
 */
final class MediaFileFactory extends Factory
{
    protected $model = MediaFileModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileName = fake()->word() . '.jpg';
        $filePath = 'uploads/' . $fileName;

        return [
            'uuid' => fake()->uuid(),
            'filename' => $fileName,
            'path' => $filePath,
            'url' => '/storage/' . $filePath,
            'mime_type' => 'image/jpeg',
            'size_bytes' => fake()->numberBetween(10000, 5000000),
            'width' => fake()->numberBetween(800, 1920),
            'height' => fake()->numberBetween(600, 1080),
            'alt_text' => fake()->sentence(),
            'uploader_uuid' => null,
        ];
    }

    /**
     * Indicate that the media is an image.
     */
    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => 'image/jpeg',
            'width' => fake()->numberBetween(800, 1920),
            'height' => fake()->numberBetween(600, 1080),
        ]);
    }
}