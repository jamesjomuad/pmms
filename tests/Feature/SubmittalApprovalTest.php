<?php

use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use App\Models\Project;
use App\Models\Submittal;
use App\Models\User;

test('submit creates approval request', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->teamMembers()->attach($user, ['project_role' => 'pm']);

    $submittal = Submittal::factory()->pending()->create([
        'project_id' => $project->id,
        'assigned_to' => $user->id,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('projects.submittals.submit', [$project, $submittal]));

    $response->assertRedirect();
    $this->assertDatabaseHas('approval_requests', [
        'approvable_id' => $submittal->id,
        'approvable_type' => Submittal::class,
        'status' => 'pending',
    ]);
});

test('approve resolves pending step for user', function () {
    $approver = User::factory()->create();
    $project = Project::factory()->create();
    $project->teamMembers()->attach($approver, ['project_role' => 'pm']);

    $submittal = Submittal::factory()->create([
        'project_id' => $project->id,
        'assigned_to' => $approver->id,
        'status' => 'pending',
    ]);

    $approvalRequest = ApprovalRequest::factory()->create([
        'approvable_id' => $submittal->id,
        'approvable_type' => Submittal::class,
        'status' => 'pending',
    ]);

    $step = ApprovalStep::factory()->create([
        'approval_request_id' => $approvalRequest->id,
        'approver_id' => $approver->id,
        'status' => 'pending',
    ]);

    $response = $this
        ->actingAs($approver)
        ->post(route('projects.submittals.approve', [$project, $submittal]));

    $response->assertRedirect();
    $this->assertDatabaseHas('approval_steps', [
        'id' => $step->id,
        'status' => 'approved',
    ]);
});

test('requestRevision resolves pending step for user', function () {
    $approver = User::factory()->create();
    $project = Project::factory()->create();
    $project->teamMembers()->attach($approver, ['project_role' => 'pm']);

    $submittal = Submittal::factory()->create([
        'project_id' => $project->id,
        'assigned_to' => $approver->id,
        'status' => 'pending',
    ]);

    $approvalRequest = ApprovalRequest::factory()->create([
        'approvable_id' => $submittal->id,
        'approvable_type' => Submittal::class,
        'status' => 'pending',
    ]);

    $step = ApprovalStep::factory()->create([
        'approval_request_id' => $approvalRequest->id,
        'approver_id' => $approver->id,
        'status' => 'pending',
    ]);

    $response = $this
        ->actingAs($approver)
        ->post(route('projects.submittals.request-revision', [$project, $submittal]), [
            'revision_notes' => 'Please update the spec section',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('approval_steps', [
        'id' => $step->id,
        'status' => 'rejected',
    ]);
    $this->assertDatabaseHas('submittals', [
        'id' => $submittal->id,
        'status' => 'revision',
    ]);
});

test('newRevision cancels pending approval requests', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->teamMembers()->attach($user, ['project_role' => 'pm']);

    $submittal = Submittal::factory()->create([
        'project_id' => $project->id,
        'assigned_to' => $user->id,
        'status' => 'revision',
    ]);

    $approvalRequest = ApprovalRequest::factory()->create([
        'approvable_id' => $submittal->id,
        'approvable_type' => Submittal::class,
        'status' => 'pending',
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('projects.submittals.new-revision', [$project, $submittal]));

    $response->assertRedirect();
    $this->assertDatabaseHas('approval_requests', [
        'id' => $approvalRequest->id,
        'status' => 'cancelled',
    ]);
    $this->assertDatabaseHas('submittals', [
        'id' => $submittal->id,
        'revision_number' => 2,
        'status' => 'draft',
    ]);
});

test('approve returns error if no pending step', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->teamMembers()->attach($user, ['project_role' => 'pm']);

    $submittal = Submittal::factory()->create([
        'project_id' => $project->id,
        'assigned_to' => $user->id,
        'status' => 'pending',
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('projects.submittals.approve', [$project, $submittal]));

    $response->assertRedirect();
    $this->assertDatabaseHas('submittals', [
        'id' => $submittal->id,
        'status' => 'pending',
    ]);
});
