<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Submittal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submittal>
 */
class SubmittalFactory extends Factory
{
    protected $model = Submittal::class;

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
            'spec_section' => fake()->numerify('##.##'),
            'revision_number' => 1,
            'status' => 'draft',
            'due_date' => fake()->dateTimeBetween('+1 week', '+1 month'),
        ];
    }

    /**
     * Set the submittal as pending.
     */
    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
    }

    /**
     * Set the submittal as approved.
     */
    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'submitted_at' => now()->subWeek(),
            'approved_at' => now(),
        ]);
    }

    /**
     * Set the submittal as rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => 'rejected',
            'submitted_at' => now()->subWeek(),
            'rejection_reason' => fake()->sentence(),
        ]);
    }
}
