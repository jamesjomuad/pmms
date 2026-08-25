<?php

namespace App\Models;

use Database\Factories\ProjectStageHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $project_id
 * @property int|null $from_stage_id
 * @property int $to_stage_id
 * @property int $changed_by
 * @property Carbon $changed_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Project $project
 * @property-read Stage|null $fromStage
 * @property-read Stage $toStage
 * @property-read User $changedByUser
 */
#[Fillable(['project_id', 'from_stage_id', 'to_stage_id', 'changed_by', 'changed_at', 'notes'])]
class ProjectStageHistory extends Model
{
    /** @use HasFactory<ProjectStageHistoryFactory> */
    use HasFactory;

    protected $table = 'project_stage_history';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    /**
     * Get the project this history entry belongs to.
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the stage this project came from.
     *
     * @return BelongsTo<Stage, $this>
     */
    public function fromStage(): BelongsTo
    {
        return $this->belongsTo(Stage::class, 'from_stage_id');
    }

    /**
     * Get the stage this project moved to.
     *
     * @return BelongsTo<Stage, $this>
     */
    public function toStage(): BelongsTo
    {
        return $this->belongsTo(Stage::class, 'to_stage_id');
    }

    /**
     * Get the user who made the change.
     *
     * @return BelongsTo<User, $this>
     */
    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
