<?php

namespace App\Http\Controllers;

use App\Enums\WorkflowStatus;
use App\Http\Requests\StoreShopDrawingRequest;
use App\Http\Requests\UpdateShopDrawingRequest;
use App\Models\Project;
use App\Models\ShopDrawing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ShopDrawingController extends Controller
{
    /**
     * Display a listing of all shop drawings across accessible projects.
     */
    public function all(Request $request): Response
    {
        $user = $request->user();

        $shopDrawings = ShopDrawing::query()
            ->whereHas('project.teamMembers', fn ($q) => $q->where('users.id', $user->id))
            ->with(['project', 'assignee'])
            ->latest()
            ->get()
            ->map(fn (ShopDrawing $drawing) => [
                'id' => $drawing->id,
                'title' => $drawing->title,
                'drawing_number' => $drawing->drawing_number,
                'revision_number' => $drawing->revision_number,
                'status' => $drawing->status,
                'due_date' => $drawing->due_date?->toDateString(),
                'assigned_to' => $drawing->assignee?->name,
                'created_at' => $drawing->created_at->toISOString(),
                'project' => [
                    'id' => $drawing->project->id,
                    'name' => $drawing->project->name,
                    'project_number' => $drawing->project->project_number,
                ],
            ]);

        $projects = Project::query()
            ->whereHas('teamMembers', fn ($q) => $q->where('users.id', $user->id))
            ->orderBy('name')
            ->get()
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'project_number' => $project->project_number,
            ]);

        return Inertia::render('shop-drawings/Index', [
            'shopDrawings' => $shopDrawings,
            'projects' => $projects,
        ]);
    }

    /**
     * Display a listing of shop drawings for a project.
     */
    public function index(Request $request, Project $project): Response
    {
        Gate::authorize('view', $project);

        $shopDrawings = ShopDrawing::forProject($project->id)
            ->with('assignee')
            ->latest()
            ->get()
            ->map(fn (ShopDrawing $drawing) => [
                'id' => $drawing->id,
                'title' => $drawing->title,
                'drawing_number' => $drawing->drawing_number,
                'revision_number' => $drawing->revision_number,
                'status' => $drawing->status,
                'due_date' => $drawing->due_date?->toDateString(),
                'assigned_to' => $drawing->assignee?->name,
                'created_at' => $drawing->created_at->toISOString(),
            ]);

        return Inertia::render('projects/shop-drawings/Index', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'shopDrawings' => $shopDrawings,
        ]);
    }

    /**
     * Show the form for creating a new shop drawing.
     */
    public function create(Project $project): Response
    {
        Gate::authorize('update', $project);

        return Inertia::render('projects/shop-drawings/Create', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'users' => $project->teamMembers->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
            ]),
        ]);
    }

    /**
     * Store a newly created shop drawing.
     */
    public function store(StoreShopDrawingRequest $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $shopDrawing = ShopDrawing::create([
            ...$request->validated(),
            'project_id' => $project->id,
            'status' => WorkflowStatus::Draft->value,
        ]);

        activity()
            ->performedOn($shopDrawing)
            ->causedBy($request->user())
            ->event('created')
            ->log('Shop drawing created');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Shop drawing created.')]);

        return to_route('projects.shop-drawings.show', [$project, $shopDrawing]);
    }

    /**
     * Display the specified shop drawing.
     */
    public function show(Project $project, ShopDrawing $shopDrawing): Response
    {
        Gate::authorize('view', $project);

        $shopDrawing->load(['assignee', 'comments.user', 'approvalRequests.steps.approver']);

        return Inertia::render('projects/shop-drawings/Show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'shopDrawing' => [
                'id' => $shopDrawing->id,
                'title' => $shopDrawing->title,
                'description' => $shopDrawing->description,
                'drawing_number' => $shopDrawing->drawing_number,
                'revision_number' => $shopDrawing->revision_number,
                'status' => $shopDrawing->status,
                'due_date' => $shopDrawing->due_date?->toDateString(),
                'submitted_at' => $shopDrawing->submitted_at?->toISOString(),
                'approved_at' => $shopDrawing->approved_at?->toISOString(),
                'rejection_reason' => $shopDrawing->rejection_reason,
                'created_at' => $shopDrawing->created_at->toISOString(),
                'assignee' => $shopDrawing->assignee ? [
                    'id' => $shopDrawing->assignee->id,
                    'name' => $shopDrawing->assignee->name,
                ] : null,
                'comments' => $shopDrawing->comments->map(fn ($comment) => [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'user' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                    ],
                    'created_at' => $comment->created_at->toISOString(),
                ]),
                'attachments' => $shopDrawing->getAttachments()->map(fn ($media) => [
                    'id' => $media->id,
                    'name' => $media->name ?? $media->file_name,
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'human_size' => $media->human_readable_size,
                    'created_at' => $media->created_at->toISOString(),
                    'url' => $media->getUrl(),
                ]),
                'approval_requests' => $shopDrawing->approvalRequests->map(fn ($request) => [
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
     * Show the form for editing the specified shop drawing.
     */
    public function edit(Project $project, ShopDrawing $shopDrawing): Response
    {
        Gate::authorize('update', $project);

        return Inertia::render('projects/shop-drawings/Edit', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'shopDrawing' => [
                'id' => $shopDrawing->id,
                'title' => $shopDrawing->title,
                'description' => $shopDrawing->description,
                'drawing_number' => $shopDrawing->drawing_number,
                'revision_number' => $shopDrawing->revision_number,
                'status' => $shopDrawing->status,
                'due_date' => $shopDrawing->due_date?->toDateString(),
                'assigned_to' => $shopDrawing->assigned_to,
            ],
            'users' => $project->teamMembers->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
            ]),
        ]);
    }

    /**
     * Update the specified shop drawing.
     */
    public function update(UpdateShopDrawingRequest $request, Project $project, ShopDrawing $shopDrawing): RedirectResponse
    {
        Gate::authorize('update', $project);

        $shopDrawing->update($request->validated());

        activity()
            ->performedOn($shopDrawing)
            ->causedBy($request->user())
            ->event('updated')
            ->log('Shop drawing updated');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Shop drawing updated.')]);

        return to_route('projects.shop-drawings.show', [$project, $shopDrawing]);
    }

    /**
     * Remove the specified shop drawing.
     */
    public function destroy(Request $request, Project $project, ShopDrawing $shopDrawing): RedirectResponse
    {
        Gate::authorize('update', $project);

        $shopDrawing->delete();

        activity()
            ->performedOn($shopDrawing)
            ->causedBy($request->user())
            ->event('deleted')
            ->log('Shop drawing deleted');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Shop drawing deleted.')]);

        return to_route('projects.shop-drawings.index', $project);
    }

    /**
     * Submit the shop drawing for review.
     */
    public function submit(Request $request, Project $project, ShopDrawing $shopDrawing): RedirectResponse
    {
        Gate::authorize('update', $project);

        $shopDrawing->markSubmitted();

        $approverId = $shopDrawing->assigned_to ?? $project->teamMembers->first()?->id;
        if ($approverId) {
            $shopDrawing->requestApproval(
                $request->user(),
                [['approver_id' => $approverId]],
                'Submitted for review',
            );
        }

        activity()
            ->performedOn($shopDrawing)
            ->causedBy($request->user())
            ->event('submitted')
            ->log('Shop drawing submitted for review');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Shop drawing submitted for review.')]);

        return to_route('projects.shop-drawings.show', [$project, $shopDrawing]);
    }

    /**
     * Approve the shop drawing.
     */
    public function approve(Request $request, Project $project, ShopDrawing $shopDrawing): RedirectResponse
    {
        Gate::authorize('update', $project);

        $pendingRequest = $shopDrawing->approvalRequests()->where('status', 'pending')->first();
        $step = $pendingRequest?->steps()
            ->where('approver_id', $request->user()->id)
            ->where('status', 'pending')
            ->first();

        if (! $step) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('No pending approval step found for you.')]);

            return to_route('projects.shop-drawings.show', [$project, $shopDrawing]);
        }

        $step->approve();

        if ($pendingRequest->fresh()->isApproved()) {
            $shopDrawing->markApproved();
        }

        activity()
            ->performedOn($shopDrawing)
            ->causedBy($request->user())
            ->event('approved')
            ->log('Shop drawing approved');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Shop drawing approved.')]);

        return to_route('projects.shop-drawings.show', [$project, $shopDrawing]);
    }

    /**
     * Request revision on the shop drawing.
     */
    public function requestRevision(Request $request, Project $project, ShopDrawing $shopDrawing): RedirectResponse
    {
        Gate::authorize('update', $project);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ]);

        $pendingRequest = $shopDrawing->approvalRequests()->where('status', 'pending')->first();
        $step = $pendingRequest?->steps()
            ->where('approver_id', $request->user()->id)
            ->where('status', 'pending')
            ->first();

        if (! $step) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('No pending approval step found for you.')]);

            return to_route('projects.shop-drawings.show', [$project, $shopDrawing]);
        }

        $step->reject($data['rejection_reason']);

        $shopDrawing->update([
            'status' => WorkflowStatus::Revision->value,
            'rejection_reason' => $data['rejection_reason'],
        ]);

        activity()
            ->performedOn($shopDrawing)
            ->causedBy($request->user())
            ->event('revision_requested')
            ->withProperties(['reason' => $data['rejection_reason']])
            ->log('Revision requested on shop drawing');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Revision requested.')]);

        return to_route('projects.shop-drawings.show', [$project, $shopDrawing]);
    }

    /**
     * Create a new revision of the shop drawing.
     */
    public function newRevision(Request $request, Project $project, ShopDrawing $shopDrawing): RedirectResponse
    {
        Gate::authorize('update', $project);

        $shopDrawing->approvalRequests()
            ->where('status', 'pending')
            ->each(fn ($request) => $request->cancel());

        $shopDrawing->newRevision();

        activity()
            ->performedOn($shopDrawing)
            ->causedBy($request->user())
            ->event('revision_created')
            ->withProperties(['revision_number' => $shopDrawing->revision_number])
            ->log('New revision created');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('New revision created.')]);

        return to_route('projects.shop-drawings.show', [$project, $shopDrawing]);
    }
}
