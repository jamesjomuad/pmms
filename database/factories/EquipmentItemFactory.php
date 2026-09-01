<?php

namespace Database\Factories;

use App\Models\EquipmentItem;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipmentItem>
 */
class EquipmentItemFactory extends Factory
{
    protected $model = EquipmentItem::class;

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
            'title' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'manufacturer' => fake()->company(),
            'model_number' => fake()->numerify('MDL-####'),
            'serial_number' => fake()->numerify('SN-########'),
            'cost' => fake()->randomFloat(2, 100, 10000),
            'quantity' => fake()->numberBetween(1, 10),
            'status' => 'draft',
            'po_status' => 'pending',
            'lead_time' => fake()->dateTimeBetween('+2 weeks', '+2 months'),
        ];
    }
}
