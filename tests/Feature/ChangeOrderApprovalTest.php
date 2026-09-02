<?php

use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use App\Models\ChangeOrder;
use App\Models\Project;
use App\Models\User;

test('submit creates approval request for change order', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->teamMembers()->attach($user, ['project_role' => 'pm']);

    $changeOrder = ChangeOrder::factory()->create([
        'project_id' => $project->id,
        'requested_by' => $user->id,
        'status' => 'pending',
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('projects.change-orders.submit', [$project, $changeOrder]));

    $response->assertRedirect();
    $this->assertDatabaseHas('approval_requests', [
        'approvable_id' => $changeOrder->id,
        'approvable_type' => ChangeOrder::class,
        'status' => 'pending',
    ]);
});

test('approve resolves pending step for change order', function () {
    $approver = User::factory()->create();
    $project = Project::factory()->create();
    $project->teamMembers()->attach($approver, ['project_role' => 'pm']);

    $changeOrder = ChangeOrder::factory()->create([
        'project_id' => $project->id,
        'requested_by' => $approver->id,
        'status' => 'pending',
    ]);

    $approvalRequest = ApprovalRequest::factory()->create([
        'approvable_id' => $changeOrder->id,
        'approvable_type' => ChangeOrder::class,
        'status' => 'pending',
    ]);

    $step = ApprovalStep::factory()->create([
        'approval_request_id' => $approvalRequest->id,
        'approver_id' => $approver->id,
        'status' => 'pending',
    ]);

    $response = $this
        ->actingAs($approver)
        ->post(route('projects.change-orders.approve', [$project, $changeOrder]));

    $response->assertRedirect();
    $this->assertDatabaseHas('approval_steps', [
        'id' => $step->id,
        'status' => 'approved',
    ]);
});

test('reject resolves pending step for change order', function () {
    $approver = User::factory()->create();
    $project = Project::factory()->create();
    $project->teamMembers()->attach($approver, ['project_role' => 'pm']);

    $changeOrder = ChangeOrder::factory()->create([
        'project_id' => $project->id,
        'requested_by' => $approver->id,
        'status' => 'pending',
    ]);

    $approvalRequest = ApprovalRequest::factory()->create([
        'approvable_id' => $changeOrder->id,
        'approvable_type' => ChangeOrder::class,
        'status' => 'pending',
    ]);

    $step = ApprovalStep::factory()->create([
        'approval_request_id' => $approvalRequest->id,
        'approver_id' => $approver->id,
        'status' => 'pending',
    ]);

    $response = $this
        ->actingAs($approver)
        ->post(route('projects.change-orders.reject', [$project, $changeOrder]), [
            'rejection_reason' => 'Cost impact too high',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('approval_steps', [
        'id' => $step->id,
        'status' => 'rejected',
    ]);
    $this->assertDatabaseHas('change_orders', [
        'id' => $changeOrder->id,
        'status' => 'rejected',
    ]);
});

test('approve returns error if no pending step', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->teamMembers()->attach($user, ['project_role' => 'pm']);

    $changeOrder = ChangeOrder::factory()->create([
        'project_id' => $project->id,
        'requested_by' => $user->id,
        'status' => 'pending',
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('projects.change-orders.approve', [$project, $changeOrder]));

    $response->assertRedirect();
    $this->assertDatabaseHas('change_orders', [
        'id' => $changeOrder->id,
        'status' => 'pending',
    ]);
});
