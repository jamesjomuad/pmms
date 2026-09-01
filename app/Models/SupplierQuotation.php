<?php

namespace App\Models;

use Database\Factories\SupplierQuotationFactory;
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
 * @property string $supplier_name
 * @property float $quoted_cost
 * @property int|null $quoted_lead_time_days
 * @property Carbon|null $quote_date
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read EquipmentItem $equipmentItem
 */
#[Fillable([
    'equipment_item_id',
    'supplier_name',
    'quoted_cost',
    'quoted_lead_time_days',
    'quote_date',
    'status',
])]
class SupplierQuotation extends Model
{
    /** @use HasFactory<SupplierQuotationFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Get the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['supplier_name', 'quoted_cost', 'quoted_lead_time_days', 'quote_date', 'status'])
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
            'quoted_cost' => 'decimal:2',
            'quoted_lead_time_days' => 'integer',
            'quote_date' => 'date',
        ];
    }

    /**
     * Get the equipment item that owns the quotation.
     *
     * @return BelongsTo<EquipmentItem, $this>
     */
    public function equipmentItem(): BelongsTo
    {
        return $this->belongsTo(EquipmentItem::class);
    }

    /**
     * Scope to quotations for a specific equipment item.
     *
     * @param  Builder<SupplierQuotation>  $query
     * @return Builder<SupplierQuotation>
     */
    public function scopeForEquipmentItem($query, int $equipmentItemId): Builder
    {
        return $query->where('equipment_item_id', $equipmentItemId);
    }

    /**
     * Mark the quotation as selected.
     */
    public function markSelected(): void
    {
        $this->update(['status' => 'selected']);
    }

    /**
     * Mark the quotation as declined.
     */
    public function markDeclined(): void
    {
        $this->update(['status' => 'declined']);
    }
}
