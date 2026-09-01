<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Rfi;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rfi>
 */
class RfiFactory extends Factory
{
    protected $model = Rfi::class;

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
            'created_by' => User::factory(),
            'title' => fake()->sentence(3),
            'question' => fake()->paragraph(),
            'status' => 'draft',
            'due_date' => fake()->dateTimeBetween('+1 week', '+1 month'),
        ];
    }
}
