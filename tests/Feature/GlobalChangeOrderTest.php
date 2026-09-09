<?php

use App\Models\ChangeOrder;
use App\Models\Project;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to login when visiting change orders page', function () {
    $this->get(route('change-orders.index'))
        ->assertRedirect(route('login'));
});

test('authenticated users can view change orders from their projects', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['name' => 'Project Delta']);
    $project->teamMembers()->attach($user, ['project_role' => 'pm']);

    ChangeOrder::factory()->create([
        'project_id' => $project->id,
        'title' => 'Additional Ductwork',
        'cost_impact' => 25000,
        'schedule_impact_days' => 5,
    ]);

    $this->actingAs($user)
        ->get(route('change-orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('change-orders/Index')
            ->has('changeOrders', 1)
            ->where('changeOrders.0.title', 'Additional Ductwork')
            ->where('changeOrders.0.cost_impact', '25000.00')
            ->where('changeOrders.0.schedule_impact_days', 5)
            ->where('changeOrders.0.project.name', 'Project Delta')
            ->has('projects', 1)
        );
});

test('change orders from projects the user is not a member of are not shown', function () {
    $user = User::factory()->create();
    $myProject = Project::factory()->create(['name' => 'My HVAC Project']);
    $myProject->teamMembers()->attach($user, ['project_role' => 'pm']);

    $otherProject = Project::factory()->create(['name' => 'Confidential Project']);

    ChangeOrder::factory()->create([
        'project_id' => $myProject->id,
        'title' => 'Visible Change Order',
    ]);

    ChangeOrder::factory()->create([
        'project_id' => $otherProject->id,
        'title' => 'Hidden Change Order',
    ]);

    $this->actingAs($user)
        ->get(route('change-orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('change-orders/Index')
            ->has('changeOrders', 1)
            ->where('changeOrders.0.title', 'Visible Change Order')
            ->has('projects', 1)
            ->where('projects.0.name', 'My HVAC Project')
        );
});
