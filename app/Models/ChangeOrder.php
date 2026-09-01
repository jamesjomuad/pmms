<?php

namespace App\Models;

use App\Concerns\HasApprovals;
use App\Concerns\HasAttachments;
use App\Concerns\HasComments;
use App\Concerns\HasWorkflowStatus;
use Database\Factories\ChangeOrderFactory;
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
 * @property int $project_id
 * @property int $requested_by
 * @property int|null $approved_by
 * @property string $title
 * @property string $description
 * @property float $cost_impact
 * @property int $schedule_impact_days
 * @property string $status
 * @property Carbon|null $requested_at
 * @property Carbon|null $approved_at
 * @property Carbon|null $rejected_at
 * @property string|null $rejection_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Project $project
 * @property-read User $requester
 * @property-read User|null $approver
 */
#[Fillable([
    'project_id',
    'requested_by',
    'approved_by',
    'title',
    'description',
    'cost_impact',
    'schedule_impact_days',
    'status',
    'requested_at',
    'approved_at',
    'rejected_at',
    'rejection_reason',
])]
class ChangeOrder extends Model
{
    /** @use HasFactory<ChangeOrderFactory> */
    use HasApprovals, HasAttachments, HasComments, HasFactory, HasWorkflowStatus, LogsActivity, SoftDeletes;

    /**
     * Get the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'description', 'cost_impact', 'schedule_impact_days', 'status', 'requested_at', 'approved_at', 'rejected_at', 'rejection_reason'])
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
            'cost_impact' => 'decimal:2',
            'schedule_impact_days' => 'integer',
            'requested_at' => 'date',
            'approved_at' => 'date',
            'rejected_at' => 'date',
        ];
    }

    /**
     * Get the project that owns the change order.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who requested the change order.
     *
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * Get the user who approved the change order.
     *
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope to change orders for a specific project.
     *
     * @param  Builder<ChangeOrder>  $query
     * @return Builder<ChangeOrder>
     */
    public function scopeForProject($query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Mark as approved.
     */
    public function markApproved(int $approvedBy): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);
    }

    /**
     * Mark as rejected.
     */
    public function markRejected(?string $reason = null): void
    {
        $this->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }
}
