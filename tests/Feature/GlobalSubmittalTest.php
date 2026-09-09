<?php

use App\Models\Project;
use App\Models\Submittal;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to login when visiting submittals page', function () {
    $this->get(route('submittals.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view submittals from their projects', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Project Gamma']);
    $project->teamMembers()->attach($user, ['project_role' => 'pm']);

    Submittal::factory()->create([
        'project_id' => $project->id,
        'title' => 'Chilled Water Piping Submittal',
        'spec_section' => '23 21 13',
    ]);

    $this->actingAs($user)
        ->get(route('submittals.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('submittals/Index')
            ->has('submittals', 1)
            ->where('submittals.0.title', 'Chilled Water Piping Submittal')
            ->where('submittals.0.spec_section', '23 21 13')
            ->where('submittals.0.project.name', 'Project Gamma')
            ->has('projects', 1)
        );
});

test('submittals from projects the user is not a member of are not shown', function () {
    $user = User::factory()->create();
    $myProject = Project::factory()->create(['name' => 'My HVAC Project']);
    $myProject->teamMembers()->attach($user, ['project_role' => 'pm']);

    $otherProject = Project::factory()->create(['name' => 'Confidential Project']);

    Submittal::factory()->create([
        'project_id' => $myProject->id,
        'title' => 'Air Handler Unit Submittal',
    ]);

    Submittal::factory()->create([
        'project_id' => $otherProject->id,
        'title' => 'Other Submittal',
    ]);

    $this->actingAs($user)
        ->get(route('submittals.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('submittals/Index')
            ->has('submittals', 1)
            ->where('submittals.0.title', 'Air Handler Unit Submittal')
            ->has('projects', 1)
            ->where('projects.0.name', 'My HVAC Project')
        );
});
