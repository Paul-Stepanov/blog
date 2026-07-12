<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Models\SiteSettingModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteSettingModel>
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
        $group = fake()->randomElement(['site', 'blog', 'seo', 'social', 'mail']);
        $key = $group.'.'.fake()->word();

        return [
            'uuid' => fake()->uuid(),
            'key' => $key,
            'value' => fake()->sentence(),
            'type' => 'string',
        ];
    }
}
