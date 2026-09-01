<?php

namespace App\Models;

use App\Concerns\HasAttachments;
use App\Concerns\HasComments;
use App\Concerns\HasWorkflowStatus;
use Database\Factories\PunchListItemFactory;
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
 * @property string $title
 * @property string|null $description
 * @property string|null $location
 * @property string|null $trade
 * @property string $priority
 * @property string $status
 * @property Carbon|null $due_date
 * @property Carbon|null $resolved_at
 * @property string|null $resolution_notes
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
    'location',
    'trade',
    'priority',
    'status',
    'due_date',
    'resolved_at',
    'resolution_notes',
])]
class PunchListItem extends Model
{
    /** @use HasFactory<PunchListItemFactory> */
    use HasAttachments, HasComments, HasFactory, HasWorkflowStatus, LogsActivity, SoftDeletes;

    /**
     * Get the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'description', 'location', 'trade', 'priority', 'status', 'due_date', 'resolved_at', 'resolution_notes'])
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
            'resolved_at' => 'date',
        ];
    }

    /**
     * Get the project that owns the punch list item.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user assigned to the punch list item.
     *
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Scope to punch list items for a specific project.
     *
     * @param  Builder<PunchListItem>  $query
     * @return Builder<PunchListItem>
     */
    public function scopeForProject($query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Mark as resolved.
     */
    public function markResolved(?string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }
}
