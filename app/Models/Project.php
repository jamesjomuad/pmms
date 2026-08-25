<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $current_stage_id
 * @property string $name
 * @property string $client_name
 * @property string $project_number
 * @property Carbon $awarded_date
 * @property Carbon|null $estimated_completion_date
 * @property string $status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Stage $currentStage
 * @property-read Collection<int, User> $teamMembers
 * @property-read Collection<int, ProjectStageHistory> $stageHistory
 */
#[Fillable([
    'current_stage_id',
    'name',
    'client_name',
    'project_number',
    'awarded_date',
    'estimated_completion_date',
    'status',
    'notes',
])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'awarded_date' => 'date',
            'estimated_completion_date' => 'date',
        ];
    }

    /**
     * Get the current stage for this project.
     *
     * @return BelongsTo<Stage, $this>
     */
    public function currentStage(): BelongsTo
    {
        return $this->belongsTo(Stage::class, 'current_stage_id');
    }

    /**
     * Get the team members assigned to this project.
     *
     * @return BelongsToMany<User, $this>
     */
    public function teamMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id', 'user_id')
            ->withPivot(['project_role'])
            ->withTimestamps();
    }

    /**
     * Get the stage history for this project.
     *
     * @return HasMany<ProjectStageHistory, $this>
     */
    public function stageHistory(): HasMany
    {
        return $this->hasMany(ProjectStageHistory::class)->orderByDesc('changed_at');
    }

    /**
     * Check if the project is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Scope to active projects.
     *
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopeActive($query): Builder
    {
        return $query->where('status', 'active');
    }
}
