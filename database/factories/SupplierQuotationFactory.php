<?php

namespace Database\Factories;

use App\Models\EquipmentItem;
use App\Models\SupplierQuotation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupplierQuotation>
 */
class SupplierQuotationFactory extends Factory
{
    protected $model = SupplierQuotation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'equipment_item_id' => EquipmentItem::factory(),
            'supplier_name' => fake()->company(),
            'quoted_cost' => fake()->randomFloat(2, 100, 50000),
            'quoted_lead_time_days' => fake()->numberBetween(5, 120),
            'quote_date' => fake()->date(),
            'status' => 'received',
        ];
    }
}
