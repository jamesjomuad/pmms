<?php

namespace App\Http\Controllers;

use App\Enums\WorkflowStatus;
use App\Http\Requests\StoreChangeOrderRequest;
use App\Http\Requests\UpdateChangeOrderRequest;
use App\Models\ChangeOrder;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ChangeOrderController extends Controller
{
    /**
     * Display a listing of change orders for a project.
     */
    public function index(Request $request, Project $project): Response
    {
        Gate::authorize('view', $project);

        $changeOrders = ChangeOrder::forProject($project->id)
            ->with('requester', 'approver')
            ->latest()
            ->get()
            ->map(fn (ChangeOrder $co) => [
                'id' => $co->id,
                'title' => $co->title,
                'cost_impact' => $co->cost_impact,
                'schedule_impact_days' => $co->schedule_impact_days,
                'status' => $co->status,
                'requested_by' => $co->requester->name,
                'approved_by' => $co->approver?->name,
                'requested_at' => $co->requested_at?->toDateString(),
                'created_at' => $co->created_at->toISOString(),
            ]);

        return Inertia::render('projects/change-orders/Index', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'changeOrders' => $changeOrders,
        ]);
    }

    /**
     * Show the form for creating a new change order.
     */
    public function create(Project $project): Response
    {
        Gate::authorize('update', $project);

        return Inertia::render('projects/change-orders/Create', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
        ]);
    }

    /**
     * Store a newly created change order.
     */
    public function store(StoreChangeOrderRequest $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $changeOrder = ChangeOrder::create([
            ...$request->validated(),
            'project_id' => $project->id,
            'requested_by' => $request->user()->id,
            'status' => WorkflowStatus::Draft->value,
            'requested_at' => now(),
        ]);

        activity()
            ->performedOn($changeOrder)
            ->causedBy($request->user())
            ->event('created')
            ->log('Change order created');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Change order created.')]);

        return to_route('projects.change-orders.show', [$project, $changeOrder]);
    }

    /**
     * Display the specified change order.
     */
    public function show(Project $project, ChangeOrder $changeOrder): Response
    {
        Gate::authorize('view', $project);

        $changeOrder->load(['requester', 'approver', 'comments.user', 'approvalRequests.steps.approver']);

        return Inertia::render('projects/change-orders/Show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'changeOrder' => [
                'id' => $changeOrder->id,
                'title' => $changeOrder->title,
                'description' => $changeOrder->description,
                'cost_impact' => $changeOrder->cost_impact,
                'schedule_impact_days' => $changeOrder->schedule_impact_days,
                'status' => $changeOrder->status,
                'requested_at' => $changeOrder->requested_at?->toDateString(),
                'approved_at' => $changeOrder->approved_at?->toISOString(),
                'rejected_at' => $changeOrder->rejected_at?->toISOString(),
                'rejection_reason' => $changeOrder->rejection_reason,
                'created_at' => $changeOrder->created_at->toISOString(),
                'requester' => [
                    'id' => $changeOrder->requester->id,
                    'name' => $changeOrder->requester->name,
                ],
                'approver' => $changeOrder->approver ? [
                    'id' => $changeOrder->approver->id,
                    'name' => $changeOrder->approver->name,
                ] : null,
                'comments' => $changeOrder->comments->map(fn ($comment) => [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'user' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                    ],
                    'created_at' => $comment->created_at->toISOString(),
                ]),
                'attachments' => $changeOrder->getAttachments()->map(fn ($media) => [
                    'id' => $media->id,
                    'name' => $media->name ?? $media->file_name,
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'human_size' => $media->human_readable_size,
                    'created_at' => $media->created_at->toISOString(),
                    'url' => $media->getUrl(),
                ]),
                'approval_requests' => $changeOrder->approvalRequests->map(fn ($request) => [
                    'id' => $request->id,
                    'status' => $request->status,
                    'requested_by' => $request->requester->name,
                    'created_at' => $request->created_at->toISOString(),
                    'steps' => $request->steps->map(fn ($step) => [
                        'id' => $step->id,
                        'approver' => $step->approver?->name ?? $step->approver_role, // @phpstan-ignore nullsafe.neverNull
                        'status' => $step->status,
                        'decided_at' => $step->decided_at?->toISOString(),
                        'comments' => $step->comments,
                    ]),
                ]),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified change order.
     */
    public function edit(Project $project, ChangeOrder $changeOrder): Response
    {
        Gate::authorize('update', $project);

        return Inertia::render('projects/change-orders/Edit', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'changeOrder' => [
                'id' => $changeOrder->id,
                'title' => $changeOrder->title,
                'description' => $changeOrder->description,
                'cost_impact' => $changeOrder->cost_impact,
                'schedule_impact_days' => $changeOrder->schedule_impact_days,
                'status' => $changeOrder->status,
            ],
        ]);
    }

    /**
     * Update the specified change order.
     */
    public function update(UpdateChangeOrderRequest $request, Project $project, ChangeOrder $changeOrder): RedirectResponse
    {
        Gate::authorize('update', $project);

        $changeOrder->update($request->validated());

        activity()
            ->performedOn($changeOrder)
            ->causedBy($request->user())
            ->event('updated')
            ->log('Change order updated');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Change order updated.')]);

        return to_route('projects.change-orders.show', [$project, $changeOrder]);
    }

    /**
     * Remove the specified change order.
     */
    public function destroy(Request $request, Project $project, ChangeOrder $changeOrder): RedirectResponse
    {
        Gate::authorize('update', $project);

        $changeOrder->delete();

        activity()
            ->performedOn($changeOrder)
            ->causedBy($request->user())
            ->event('deleted')
            ->log('Change order deleted');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Change order deleted.')]);

        return to_route('projects.change-orders.index', $project);
    }

    /**
     * Submit the change order for approval.
     */
    public function submit(Request $request, Project $project, ChangeOrder $changeOrder): RedirectResponse
    {
        Gate::authorize('update', $project);

        $changeOrder->update([
            'status' => WorkflowStatus::Pending->value,
        ]);

        $approverId = $project->teamMembers->first()?->id;
        if ($approverId) {
            $changeOrder->requestApproval(
                $request->user(),
                [['approver_id' => $approverId]],
                'Submitted for approval',
            );
        }

        activity()
            ->performedOn($changeOrder)
            ->causedBy($request->user())
            ->event('submitted')
            ->log('Change order submitted for approval');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Change order submitted for approval.')]);

        return to_route('projects.change-orders.show', [$project, $changeOrder]);
    }

    /**
     * Approve the change order.
     */
    public function approve(Request $request, Project $project, ChangeOrder $changeOrder): RedirectResponse
    {
        Gate::authorize('update', $project);

        $pendingRequest = $changeOrder->approvalRequests()->where('status', 'pending')->first();
        $step = $pendingRequest?->steps()
            ->where('approver_id', $request->user()->id)
            ->where('status', 'pending')
            ->first();

        if (! $step) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('No pending approval step found for you.')]);

            return to_route('projects.change-orders.show', [$project, $changeOrder]);
        }

        $step->approve();

        if ($changeOrder->fresh()->status === WorkflowStatus::Approved->value) {
            $changeOrder->update([
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);
        }

        activity()
            ->performedOn($changeOrder)
            ->causedBy($request->user())
            ->event('approved')
            ->log('Change order approved');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Change order approved.')]);

        return to_route('projects.change-orders.show', [$project, $changeOrder]);
    }

    /**
     * Reject the change order.
     */
    public function reject(Request $request, Project $project, ChangeOrder $changeOrder): RedirectResponse
    {
        Gate::authorize('update', $project);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ]);

        $pendingRequest = $changeOrder->approvalRequests()->where('status', 'pending')->first();
        $step = $pendingRequest?->steps()
            ->where('approver_id', $request->user()->id)
            ->where('status', 'pending')
            ->first();

        if (! $step) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('No pending approval step found for you.')]);

            return to_route('projects.change-orders.show', [$project, $changeOrder]);
        }

        $step->reject($data['rejection_reason']);

        $changeOrder->update([
            'status' => WorkflowStatus::Rejected->value,
            'rejected_at' => now(),
            'rejection_reason' => $data['rejection_reason'],
        ]);

        activity()
            ->performedOn($changeOrder)
            ->causedBy($request->user())
            ->event('rejected')
            ->withProperties(['reason' => $data['rejection_reason']])
            ->log('Change order rejected');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Change order rejected.')]);

        return to_route('projects.change-orders.show', [$project, $changeOrder]);
    }
}
