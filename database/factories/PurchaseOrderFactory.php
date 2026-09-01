<?php

namespace Database\Factories;

use App\Models\EquipmentItem;
use App\Models\PurchaseOrder;
use App\Models\SupplierQuotation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'equipment_item_id' => EquipmentItem::factory(),
            'supplier_quotation_id' => null,
            'po_number' => 'PO-'.fake()->unique()->numerify('####'),
            'issued_date' => fake()->date(),
            'cost' => fake()->randomFloat(2, 100, 50000),
            'expected_delivery_date' => fake()->dateTimeBetween('+1 week', '+3 months')->format('Y-m-d'),
            'actual_delivery_date' => null,
            'status' => 'issued',
        ];
    }

    /**
     * Attach the purchase order to a specific supplier quotation.
     */
    public function fromQuotation(SupplierQuotation $quotation): static
    {
        return $this->state(fn (array $attributes) => [
            'supplier_quotation_id' => $quotation->id,
            'cost' => $quotation->quoted_cost,
            'equipment_item_id' => $quotation->equipment_item_id,
        ]);
    }
}
