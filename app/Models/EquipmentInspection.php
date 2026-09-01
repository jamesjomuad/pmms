<?php

namespace App\Models;

use Database\Factories\EquipmentInspectionFactory;
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
 * @property int|null $inspected_by
 * @property Carbon|null $inspected_date
 * @property string $result
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read EquipmentItem $equipmentItem
 * @property-read User|null $inspector
 */
#[Fillable([
    'equipment_item_id',
    'inspected_by',
    'inspected_date',
    'result',
    'notes',
])]
class EquipmentInspection extends Model
{
    /** @use HasFactory<EquipmentInspectionFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * Get the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['inspected_by', 'inspected_date', 'result', 'notes'])
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
            'inspected_date' => 'date',
        ];
    }

    /**
     * Get the equipment item that owns the inspection.
     *
     * @return BelongsTo<EquipmentItem, $this>
     */
    public function equipmentItem(): BelongsTo
    {
        return $this->belongsTo(EquipmentItem::class);
    }

    /**
     * Get the user who inspected the equipment.
     *
     * @return BelongsTo<User, $this>
     */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    /**
     * Scope to inspections for a specific equipment item.
     *
     * @param  Builder<EquipmentInspection>  $query
     * @return Builder<EquipmentInspection>
     */
    public function scopeForEquipmentItem($query, int $equipmentItemId): Builder
    {
        return $query->where('equipment_item_id', $equipmentItemId);
    }
}
