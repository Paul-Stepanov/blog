<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\SiteSettingModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<SiteSettingModel>
 */
final class SiteSettingFactory extends Factory
{
    protected $model = SiteSettingModel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $key = fake()->slug(2) . '.' . fake()->slug();

        return [
            'uuid' => fake()->uuid(),
            'key' => $key,
            'value' => fake()->sentence(),
            'type' => fake()->randomElement(['string', 'boolean', 'integer']),
        ];
    }
}