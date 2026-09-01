<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\PunchListItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PunchListItem>
 */
class PunchListItemFactory extends Factory
{
    protected $model = PunchListItem::class;

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
            'location' => fake()->address(),
            'trade' => fake()->randomElement(['Electrical', 'Plumbing', 'HVAC', 'General', 'Painting']),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'status' => 'draft',
            'due_date' => fake()->dateTimeBetween('+1 week', '+1 month'),
        ];
    }
}
