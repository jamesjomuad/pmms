<?php

namespace App\Models;

use App\Concerns\HasAttachments;
use App\Concerns\HasComments;
use App\Concerns\HasWorkflowStatus;
use App\Enums\WorkflowStatus;
use Database\Factories\RfiFactory;
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
 * @property int|null $assigned_to
 * @property int $created_by
 * @property string $title
 * @property string $question
 * @property string|null $response
 * @property string $status
 * @property Carbon|null $due_date
 * @property Carbon|null $responded_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Project $project
 * @property-read User|null $assignee
 * @property-read User $creator
 */
#[Fillable([
    'project_id',
    'assigned_to',
    'created_by',
    'title',
    'question',
    'response',
    'status',
    'due_date',
    'responded_at',
])]
class Rfi extends Model
{
    /** @use HasFactory<RfiFactory> */
    use HasAttachments, HasComments, HasFactory, HasWorkflowStatus, LogsActivity, SoftDeletes;

    /**
     * Get the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'question', 'response', 'status', 'due_date', 'responded_at'])
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
            'due_date' => 'date',
            'responded_at' => 'date',
        ];
    }

    /**
     * Get the project that owns the RFI.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user assigned to the RFI.
     *
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who created the RFI.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to RFIs for a specific project.
     *
     * @param  Builder<Rfi>  $query
     * @return Builder<Rfi>
     */
    public function scopeForProject($query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Mark as responded.
     */
    public function markResponded(string $response): void
    {
        $this->update([
            'response' => $response,
            'status' => WorkflowStatus::Approved->value,
            'responded_at' => now(),
        ]);
    }
}
