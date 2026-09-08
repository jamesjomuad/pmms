<?php

use App\Models\Project;
use App\Models\ShopDrawing;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to login when visiting shop drawings page', function () {
    $this->get(route('shop-drawings.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view shop drawings from their projects', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Project Alpha']);
    $project->teamMembers()->attach($user, ['project_role' => 'pm']);

    $drawing = ShopDrawing::factory()->create([
        'project_id' => $project->id,
        'title' => 'HVAC Duct Layout',
        'drawing_number' => 'M-101',
    ]);

    $this->actingAs($user)
        ->get(route('shop-drawings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('shop-drawings/Index')
            ->has('shopDrawings', 1)
            ->where('shopDrawings.0.title', 'HVAC Duct Layout')
            ->where('shopDrawings.0.drawing_number', 'M-101')
            ->where('shopDrawings.0.project.name', 'Project Alpha')
            ->has('projects', 1)
        );
});

test('drawings from projects the user is not a member of are not shown', function () {
    $user = User::factory()->create();
    $myProject = Project::factory()->create(['name' => 'My Project']);
    $myProject->teamMembers()->attach($user, ['project_role' => 'pm']);

    $otherProject = Project::factory()->create(['name' => 'Secret Project']);

    ShopDrawing::factory()->create([
        'project_id' => $myProject->id,
        'title' => 'Visible Drawing',
    ]);

    ShopDrawing::factory()->create([
        'project_id' => $otherProject->id,
        'title' => 'Hidden Drawing',
    ]);

    $this->actingAs($user)
        ->get(route('shop-drawings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('shop-drawings/Index')
            ->has('shopDrawings', 1)
            ->where('shopDrawings.0.title', 'Visible Drawing')
            ->has('projects', 1)
            ->where('projects.0.name', 'My Project')
        );
});
