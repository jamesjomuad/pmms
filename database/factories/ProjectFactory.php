<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Stage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'current_stage_id' => Stage::factory(),
            'name' => fake()->company(),
            'client_name' => fake()->company(),
            'project_number' => fake()->unique()->bothify('PMMS-####'),
            'awarded_date' => fake()->date(),
            'estimated_completion_date' => fake()->optional()->date(),
            'status' => 'active',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
