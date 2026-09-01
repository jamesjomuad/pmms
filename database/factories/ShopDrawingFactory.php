<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ShopDrawing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShopDrawing>
 */
class ShopDrawingFactory extends Factory
{
    protected $model = ShopDrawing::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'assigned_to' => null,
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'drawing_number' => fake()->numerify('SD-####'),
            'revision_number' => 1,
            'status' => 'draft',
            'due_date' => fake()->dateTimeBetween('+1 week', '+1 month'),
        ];
    }
}
