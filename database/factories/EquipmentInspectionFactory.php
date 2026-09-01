<?php

namespace Database\Factories;

use App\Models\EquipmentInspection;
use App\Models\EquipmentItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipmentInspection>
 */
class EquipmentInspectionFactory extends Factory
{
    protected $model = EquipmentInspection::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'equipment_item_id' => EquipmentItem::factory(),
            'inspected_by' => User::factory(),
            'inspected_date' => fake()->date(),
            'result' => fake()->randomElement(['passed', 'failed', 'damaged', 'wrong_item']),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Set the inspection result to passed.
     */
    public function passed(): static
    {
        return $this->state(fn (array $attributes) => [
            'result' => 'passed',
        ]);
    }
}
