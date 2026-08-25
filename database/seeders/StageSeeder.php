<?php

namespace Database\Seeders;

use App\Models\Stage;
use Illuminate\Database\Seeder;

class StageSeeder extends Seeder
{
    /**
     * Seed the default project lifecycle stages.
     */
    public function run(): void
    {
        $stages = [
            ['key' => 'awarded', 'label' => 'Awarded', 'sort_order' => 1, 'description' => 'Project has been awarded. Initial setup and team assignment.'],
            ['key' => 'submittals', 'label' => 'Submittals', 'sort_order' => 2, 'description' => 'Submittal log, revision cycles, approval routing.'],
            ['key' => 'shop_drawings', 'label' => 'Shop Drawings', 'sort_order' => 3, 'description' => 'Drawing log, revision tracking, approval routing.'],
            ['key' => 'equipment_procurement', 'label' => 'Equipment Procurement', 'sort_order' => 4, 'description' => 'Equipment list, vendor/cost/lead-time tracking, PO status.'],
            ['key' => 'mobilization', 'label' => 'Mobilization', 'sort_order' => 5, 'description' => 'Task checklist, resource/crew assignment.'],
            ['key' => 'installation', 'label' => 'Installation', 'sort_order' => 6, 'description' => 'Field task tracking, progress %, field reporting.'],
            ['key' => 'startup', 'label' => 'Startup', 'sort_order' => 7, 'description' => 'Startup checklist/forms per major equipment item.'],
            ['key' => 'tab', 'label' => 'TAB', 'sort_order' => 8, 'description' => 'Testing, Adjusting, and Balancing tracking and results.'],
            ['key' => 'punch_list', 'label' => 'Punch List', 'sort_order' => 9, 'description' => 'Punch items, assignment, resolution tracking.'],
            ['key' => 'closeout', 'label' => 'Closeout', 'sort_order' => 10, 'description' => 'Document collection, final sign-off, project archive.'],
        ];

        foreach ($stages as $stage) {
            Stage::updateOrCreate(
                ['key' => $stage['key']],
                $stage,
            );
        }
    }
}
