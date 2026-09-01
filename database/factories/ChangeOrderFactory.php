<?php

namespace Database\Factories;

use App\Models\ChangeOrder;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChangeOrder>
 */
class ChangeOrderFactory extends Factory
{
    protected $model = ChangeOrder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'requested_by' => User::factory(),
            'approved_by' => null,
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'cost_impact' => fake()->randomFloat(2, 100, 50000),
            'schedule_impact_days' => fake()->numberBetween(0, 30),
            'status' => 'draft',
            'requested_at' => now(),
        ];
    }
}
