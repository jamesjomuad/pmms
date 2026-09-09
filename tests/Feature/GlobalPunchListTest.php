<?php

use App\Models\Project;
use App\Models\PunchListItem;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to login when visiting punch list page', function () {
    $this->get(route('punch-list.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view punch list items from their projects', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Project Echo']);
    $project->teamMembers()->attach($user, ['project_role' => 'pm']);

    PunchListItem::factory()->create([
        'project_id' => $project->id,
        'title' => 'Fix leaking VAV box connection',
        'location' => 'Level 3 Priority Room',
        'trade' => 'HVAC',
        'priority' => 'high',
    ]);

    $this->actingAs($user)
        ->get(route('punch-list.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('punch-list/Index')
            ->has('punchListItems', 1)
            ->where('punchListItems.0.title', 'Fix leaking VAV box connection')
            ->where('punchListItems.0.location', 'Level 3 Priority Room')
            ->where('punchListItems.0.project.name', 'Project Echo')
            ->has('projects', 1)
        );
});

test('punch list items from projects the user is not a member of are not shown', function () {
    $user = User::factory()->create();
    $myProject = Project::factory()->create(['name' => 'My HVAC Project']);
    $myProject->teamMembers()->attach($user, ['project_role' => 'pm']);

    $otherProject = Project::factory()->create(['name' => 'Confidential Project']);

    PunchListItem::factory()->create([
        'project_id' => $myProject->id,
        'title' => 'Visible Item',
    ]);

    PunchListItem::factory()->create([
        'project_id' => $otherProject->id,
        'title' => 'Hidden Item',
    ]);

    $this->actingAs($user)
        ->get(route('punch-list.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('punch-list/Index')
            ->has('punchListItems', 1)
            ->where('punchListItems.0.title', 'Visible Item')
            ->has('projects', 1)
            ->where('projects.0.name', 'My HVAC Project')
        );
});
