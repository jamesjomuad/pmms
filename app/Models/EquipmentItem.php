<?php

namespace App\Models;

use App\Concerns\HasAttachments;
use App\Concerns\HasComments;
use App\Concerns\HasWorkflowStatus;
use Database\Factories\EquipmentItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $project_id
 * @property int|null $assigned_to
 * @property string $title
 * @property string|null $description
 * @property string|null $manufacturer
 * @property string|null $model_number
 * @property string|null $serial_number
 * @property float|null $cost
 * @property int $quantity
 * @property string $status
 * @property string $po_status
 * @property Carbon|null $lead_time
 * @property Carbon|null $expected_delivery
 * @property Carbon|null $received_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Project $project
 * @property-read User|null $assignee
 */
#[Fillable([
    'project_id',
    'assigned_to',
    'title',
    'description',
    'manufacturer',
    'model_number',
    'serial_number',
    'cost',
    'quantity',
    'status',
    'po_status',
    'lead_time',
    'expected_delivery',
    'received_at',
])]
class EquipmentItem extends Model
{
    /** @use HasFactory<EquipmentItemFactory> */
    use HasAttachments, HasComments, HasFactory, HasWorkflowStatus, LogsActivity, SoftDeletes;

    /**
     * Get the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'description', 'manufacturer', 'model_number', 'serial_number', 'cost', 'quantity', 'status', 'po_status', 'lead_time', 'expected_delivery', 'received_at'])
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
            'quantity' => 'integer',
            'lead_time' => 'date',
            'expected_delivery' => 'date',
            'received_at' => 'date',
        ];
    }

    /**
     * Get the project that owns the equipment item.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user assigned to the equipment item.
     *
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the supplier quotations for the equipment item.
     *
     * @return HasMany<SupplierQuotation, $this>
     */
    public function supplierQuotations(): HasMany
    {
        return $this->hasMany(SupplierQuotation::class);
    }

    /**
     * Get the purchase orders for the equipment item.
     *
     * @return HasMany<PurchaseOrder, $this>
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Get the inspections for the equipment item.
     *
     * @return HasMany<EquipmentInspection, $this>
     */
    public function inspections(): HasMany
    {
        return $this->hasMany(EquipmentInspection::class);
    }

    /**
     * Scope to equipment items for a specific project.
     *
     * @param  Builder<EquipmentItem>  $query
     * @return Builder<EquipmentItem>
     */
    public function scopeForProject($query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Mark as received.
     */
    public function markReceived(): void
    {
        $this->update([
            'status' => 'approved',
            'received_at' => now(),
        ]);
    }

    /**
     * Get the total cost.
     */
    public function getTotalCostAttribute(): ?float
    {
        return $this->cost !== null ? $this->cost * $this->quantity : null;
    }
}
