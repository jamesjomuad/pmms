<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectStageHistory;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectStageHistory>
 */
class ProjectStageHistoryFactory extends Factory
{
    protected $model = ProjectStageHistory::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'from_stage_id' => Stage::factory(),
            'to_stage_id' => Stage::factory(),
            'changed_by' => User::factory(),
            'changed_at' => fake()->dateTime(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
