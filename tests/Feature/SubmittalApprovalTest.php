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
    $this->assertDatabaseHas('submittals', [
        'id' => $submittal->id,
        'status' => 'approved',
    ]);
    $this->assertNotNull($submittal->fresh()->approved_at);
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
            'comments' => 'Please update the spec section',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('approval_steps', [
        'id' => $step->id,
        'status' => 'rejected',
        'comments' => 'Please update the spec section',
    ]);
    $this->assertDatabaseHas('submittals', [
        'id' => $submittal->id,
        'status' => 'revision',
    ]);
});

test('approve stores reviewer comments on the step', function () {
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
        ->post(route('projects.submittals.approve', [$project, $submittal]), [
            'comments' => 'Approved with a note',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('approval_steps', [
        'id' => $step->id,
        'status' => 'approved',
        'comments' => 'Approved with a note',
    ]);
});

test('approveAsNoted marks step approved as noted', function () {
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
        ->post(route('projects.submittals.approve-as-noted', [$project, $submittal]), [
            'comments' => 'Owner requires 4-inch clearance to be noted on the drawing.',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('approval_steps', [
        'id' => $step->id,
        'status' => 'approved',
        'approved_as_noted' => true,
        'comments' => 'Owner requires 4-inch clearance to be noted on the drawing.',
    ]);
    $this->assertDatabaseHas('approval_requests', [
        'id' => $approvalRequest->id,
        'status' => 'approved',
    ]);
    $this->assertDatabaseHas('submittals', [
        'id' => $submittal->id,
        'status' => 'approved',
    ]);
    $this->assertNotNull($submittal->fresh()->approved_at);
});

test('approveAsNoted requires comments', function () {
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
        ->post(route('projects.submittals.approve-as-noted', [$project, $submittal]));

    $response->assertSessionHasErrors('comments');
    $this->assertDatabaseHas('approval_steps', [
        'id' => $step->id,
        'status' => 'pending',
        'approved_as_noted' => false,
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
