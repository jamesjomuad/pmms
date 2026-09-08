<?php

use App\Models\Project;
use App\Models\Stage;
use App\Models\User;

test('authenticated users can create a project with site address and geo-location', function () {
    $user = User::factory()->create();
    Stage::factory()->create(['key' => 'awarded', 'sort_order' => 1]);

    $this->actingAs($user)
        ->post(route('projects.store'), [
            'name' => 'Riverside Clinic',
            'client_name' => 'Acme Health',
            'project_number' => 'PMMS-TEST-001',
            'awarded_date' => '2026-01-15',
            'address' => '1200 Market Street',
            'city' => 'San Francisco',
            'state' => 'CA',
            'postal_code' => '94103',
            'latitude' => '37.7863347',
            'longitude' => '-122.4039055',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('projects', [
        'name' => 'Riverside Clinic',
        'address' => '1200 Market Street',
        'city' => 'San Francisco',
        'state' => 'CA',
        'postal_code' => '94103',
        'latitude' => '37.7863347',
        'longitude' => '-122.4039055',
    ]);
});

test('project managers can update site address and geo-location', function () {
    $pm = User::factory()->create();
    $project = Project::factory()->create();
    $project->teamMembers()->attach($pm, ['project_role' => 'pm']);

    $this->actingAs($pm)
        ->patch(route('projects.update', $project), [
            'address' => '500 Oak Avenue',
            'city' => 'Austin',
            'state' => 'TX',
            'postal_code' => '78701',
            'latitude' => '30.267153',
            'longitude' => '-97.743057',
        ])
        ->assertRedirect();

    $project->refresh();

    expect($project->address)->toBe('500 Oak Avenue')
        ->and($project->city)->toBe('Austin')
        ->and($project->state)->toBe('TX')
        ->and($project->postal_code)->toBe('78701')
        ->and((float) $project->latitude)->toBe(30.267153)
        ->and((float) $project->longitude)->toBe(-97.743057);
});

test('project store rejects out-of-range coordinates', function () {
    $user = User::factory()->create();
    Stage::factory()->create(['key' => 'awarded', 'sort_order' => 1]);

    $this->actingAs($user)
        ->from(route('projects.create'))
        ->post(route('projects.store'), [
            'name' => 'Bad Location',
            'client_name' => 'Acme',
            'awarded_date' => '2026-01-15',
            'latitude' => '95',
            'longitude' => '200',
        ])
        ->assertSessionHasErrors(['latitude', 'longitude']);

    $this->assertDatabaseCount('projects', 0);
});

test('maps url falls back to the site address when coordinates are absent', function () {
    $project = Project::factory()->create([
        'latitude' => null,
        'longitude' => null,
    ]);

    expect($project->maps_url)->toContain('query=')
        ->and($project->maps_url)->not->toContain(',');
});

test('maps url is null when the project has no address or coordinates', function () {
    $project = Project::factory()->create([
        'address' => null,
        'city' => null,
        'state' => null,
        'postal_code' => null,
        'latitude' => null,
        'longitude' => null,
    ]);

    expect($project->maps_url)->toBeNull()
        ->and($project->full_address)->toBe('');
});
