<?php

namespace Database\Factories;

use App\Models\Stage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stage>
 */
class StageFactory extends Factory
{
    protected $model = Stage::class;

    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(),
            'label' => fake()->words(2, true),
            'sort_order' => fake()->numberBetween(1, 100),
            'description' => fake()->sentence(),
        ];
    }
}
