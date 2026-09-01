<?php

namespace App\Models;

use Database\Factories\PurchaseOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $equipment_item_id
 * @property int|null $supplier_quotation_id
 * @property string $po_number
 * @property Carbon|null $issued_date
 * @property float $cost
 * @property Carbon|null $expected_delivery_date
 * @property Carbon|null $actual_delivery_date
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read EquipmentItem $equipmentItem
 * @property-read SupplierQuotation|null $supplierQuotation
 */
#[Fillable([
    'equipment_item_id',
    'supplier_quotation_id',
    'po_number',
    'issued_date',
    'cost',
    'expected_delivery_date',
    'actual_delivery_date',
    'status',
])]
class PurchaseOrder extends Model
{
    /** @use HasFactory<PurchaseOrderFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Get the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['supplier_quotation_id', 'po_number', 'issued_date', 'cost', 'expected_delivery_date', 'actual_delivery_date', 'status'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cost' => 'decimal:2',
            'issued_date' => 'date',
            'expected_delivery_date' => 'date',
            'actual_delivery_date' => 'date',
        ];
    }

    /**
     * Get the equipment item that owns the purchase order.
     *
     * @return BelongsTo<EquipmentItem, $this>
     */
    public function equipmentItem(): BelongsTo
    {
        return $this->belongsTo(EquipmentItem::class);
    }

    /**
     * Get the supplier quotation the purchase order was issued against.
     *
     * @return BelongsTo<SupplierQuotation, $this>
     */
    public function supplierQuotation(): BelongsTo
    {
        return $this->belongsTo(SupplierQuotation::class);
    }

    /**
     * Scope to purchase orders for a specific equipment item.
     *
     * @param  Builder<PurchaseOrder>  $query
     * @return Builder<PurchaseOrder>
     */
    public function scopeForEquipmentItem($query, int $equipmentItemId): Builder
    {
        return $query->where('equipment_item_id', $equipmentItemId);
    }

    /**
     * Mark the purchase order as delivered.
     */
    public function markDelivered(?Carbon $deliveredAt = null): void
    {
        $this->update([
            'status' => 'delivered',
            'actual_delivery_date' => $deliveredAt ?? now(),
        ]);
    }
}
