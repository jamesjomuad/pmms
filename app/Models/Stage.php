<?php

namespace App\Models;

use Database\Factories\StageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $key
 * @property string $label
 * @property int $sort_order
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Project> $projects
 */
#[Fillable(['key', 'label', 'sort_order', 'description'])]
class Stage extends Model
{
    /** @use HasFactory<StageFactory> */
    use HasFactory;

    /**
     * Get the projects currently at this stage.
     *
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'current_stage_id');
    }

    /**
     * Get the stage history entries where this was the destination stage.
     *
     * @return HasMany<ProjectStageHistory, $this>
     */
    public function historyEntries(): HasMany
    {
        return $this->hasMany(ProjectStageHistory::class, 'to_stage_id');
    }
}
