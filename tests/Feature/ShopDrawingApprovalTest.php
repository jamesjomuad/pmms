<?php

use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use App\Models\Project;
use App\Models\ShopDrawing;
use App\Models\User;

test('submit creates approval request for shop drawing', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->teamMembers()->attach($user, ['project_role' => 'pm']);

    $shopDrawing = ShopDrawing::factory()->create([
        'project_id' => $project->id,
        'assigned_to' => $user->id,
        'status' => 'pending',
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('projects.shop-drawings.submit', [$project, $shopDrawing]));

    $response->assertRedirect();
    $this->assertDatabaseHas('approval_requests', [
        'approvable_id' => $shopDrawing->id,
        'approvable_type' => ShopDrawing::class,
        'status' => 'pending',
    ]);
});

test('approve resolves pending step for shop drawing', function () {
    $approver = User::factory()->create();
    $project = Project::factory()->create();
    $project->teamMembers()->attach($approver, ['project_role' => 'pm']);

    $shopDrawing = ShopDrawing::factory()->create([
        'project_id' => $project->id,
        'assigned_to' => $approver->id,
        'status' => 'pending',
    ]);

    $approvalRequest = ApprovalRequest::factory()->create([
        'approvable_id' => $shopDrawing->id,
        'approvable_type' => ShopDrawing::class,
        'status' => 'pending',
    ]);

    $step = ApprovalStep::factory()->create([
        'approval_request_id' => $approvalRequest->id,
        'approver_id' => $approver->id,
        'status' => 'pending',
    ]);

    $response = $this
        ->actingAs($approver)
        ->post(route('projects.shop-drawings.approve', [$project, $shopDrawing]));

    $response->assertRedirect();
    $this->assertDatabaseHas('approval_steps', [
        'id' => $step->id,
        'status' => 'approved',
    ]);
    $this->assertDatabaseHas('shop_drawings', [
        'id' => $shopDrawing->id,
        'status' => 'approved',
    ]);
    $this->assertNotNull($shopDrawing->fresh()->approved_at);
});

test('requestRevision resolves pending step for shop drawing', function () {
    $approver = User::factory()->create();
    $project = Project::factory()->create();
    $project->teamMembers()->attach($approver, ['project_role' => 'pm']);

    $shopDrawing = ShopDrawing::factory()->create([
        'project_id' => $project->id,
        'assigned_to' => $approver->id,
        'status' => 'pending',
    ]);

    $approvalRequest = ApprovalRequest::factory()->create([
        'approvable_id' => $shopDrawing->id,
        'approvable_type' => ShopDrawing::class,
        'status' => 'pending',
    ]);

    $step = ApprovalStep::factory()->create([
        'approval_request_id' => $approvalRequest->id,
        'approver_id' => $approver->id,
        'status' => 'pending',
    ]);

    $response = $this
        ->actingAs($approver)
        ->post(route('projects.shop-drawings.request-revision', [$project, $shopDrawing]), [
            'rejection_reason' => 'Please update the dimensions',
        ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('approval_steps', [
        'id' => $step->id,
        'status' => 'rejected',
    ]);
    $this->assertDatabaseHas('shop_drawings', [
        'id' => $shopDrawing->id,
        'status' => 'revision',
    ]);
});

test('newRevision cancels pending approval requests for shop drawing', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->teamMembers()->attach($user, ['project_role' => 'pm']);

    $shopDrawing = ShopDrawing::factory()->create([
        'project_id' => $project->id,
        'assigned_to' => $user->id,
        'status' => 'revision',
    ]);

    $approvalRequest = ApprovalRequest::factory()->create([
        'approvable_id' => $shopDrawing->id,
        'approvable_type' => ShopDrawing::class,
        'status' => 'pending',
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('projects.shop-drawings.new-revision', [$project, $shopDrawing]));

    $response->assertRedirect();
    $this->assertDatabaseHas('approval_requests', [
        'id' => $approvalRequest->id,
        'status' => 'cancelled',
    ]);
    $this->assertDatabaseHas('shop_drawings', [
        'id' => $shopDrawing->id,
        'revision_number' => 2,
        'status' => 'draft',
    ]);
});
