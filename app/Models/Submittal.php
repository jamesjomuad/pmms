<?php

namespace App\Models;

use App\Concerns\HasApprovals;
use App\Concerns\HasAttachments;
use App\Concerns\HasComments;
use App\Concerns\HasWorkflowStatus;
use App\Enums\WorkflowStatus;
use Database\Factories\SubmittalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;

/**
 * @property int $id
 * @property int $project_id
 * @property int|null $assigned_to
 * @property string $title
 * @property string|null $description
 * @property string|null $spec_section
 * @property int $revision_number
 * @property string $status
 * @property Carbon|null $due_date
 * @property Carbon|null $submitted_at
 * @property Carbon|null $approved_at
 * @property string|null $rejection_reason
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
    'spec_section',
    'revision_number',
    'status',
    'due_date',
    'submitted_at',
    'approved_at',
    'rejection_reason',
])]
class Submittal extends Model implements HasMedia
{
    /** @use HasFactory<SubmittalFactory> */
    use HasApprovals, HasAttachments, HasComments, HasFactory, HasWorkflowStatus, LogsActivity, SoftDeletes;

    /**
     * Get the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'description', 'spec_section', 'revision_number', 'status', 'due_date', 'submitted_at', 'approved_at', 'rejection_reason'])
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
            'submitted_at' => 'date',
            'approved_at' => 'date',
            'revision_number' => 'integer',
        ];
    }

    /**
     * Get the project that owns the submittal.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user assigned to the submittal.
     *
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Scope to submittals for a specific project.
     *
     * @param  Builder<Submittal>  $query
     * @return Builder<Submittal>
     */
    public function scopeForProject($query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Increment the revision number and reset status to draft.
     */
    public function newRevision(): void
    {
        $this->update([
            'revision_number' => $this->revision_number + 1,
            'status' => WorkflowStatus::Draft->value,
            'submitted_at' => null,
            'approved_at' => null,
            'rejection_reason' => null,
        ]);
    }

    /**
     * Mark as submitted.
     */
    public function markSubmitted(): void
    {
        $this->update([
            'status' => WorkflowStatus::Pending->value,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Mark as approved.
     */
    public function markApproved(): void
    {
        $this->update([
            'status' => WorkflowStatus::Approved->value,
            'approved_at' => now(),
        ]);
    }
}
