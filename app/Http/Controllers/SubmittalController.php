<?php

namespace App\Http\Controllers;

use App\Enums\WorkflowStatus;
use App\Http\Requests\StoreSubmittalRequest;
use App\Http\Requests\UpdateSubmittalRequest;
use App\Models\Project;
use App\Models\Submittal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SubmittalController extends Controller
{
    /**
     * Display a listing of all submittals across accessible projects.
     */
    public function all(Request $request): Response
    {
        $user = $request->user();

        $submittals = Submittal::query()
            ->whereHas('project.teamMembers', fn ($q) => $q->where('users.id', $user->id))
            ->with(['project', 'assignee'])
            ->latest()
            ->get()
            ->map(fn (Submittal $submittal) => [
                'id' => $submittal->id,
                'title' => $submittal->title,
                'spec_section' => $submittal->spec_section,
                'revision_number' => $submittal->revision_number,
                'status' => $submittal->status,
                'due_date' => $submittal->due_date?->toDateString(),
                'assigned_to' => $submittal->assignee?->name,
                'created_at' => $submittal->created_at->toISOString(),
                'project' => [
                    'id' => $submittal->project->id,
                    'name' => $submittal->project->name,
                    'project_number' => $submittal->project->project_number,
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

        return Inertia::render('submittals/Index', [
            'submittals' => $submittals,
            'projects' => $projects,
        ]);
    }

    /**
     * Display a listing of submittals for a project.
     */
    public function index(Request $request, Project $project): Response
    {
        Gate::authorize('view', $project);

        $submittals = Submittal::forProject($project->id)
            ->with('assignee')
            ->latest()
            ->get()
            ->map(fn (Submittal $submittal) => [
                'id' => $submittal->id,
                'title' => $submittal->title,
                'spec_section' => $submittal->spec_section,
                'revision_number' => $submittal->revision_number,
                'status' => $submittal->status,
                'due_date' => $submittal->due_date?->toDateString(),
                'assigned_to' => $submittal->assignee?->name,
                'created_at' => $submittal->created_at->toISOString(),
            ]);

        return Inertia::render('projects/submittals/Index', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'submittals' => $submittals,
        ]);
    }

    /**
     * Show the form for creating a new submittal.
     */
    public function create(Project $project): Response
    {
        Gate::authorize('update', $project);

        return Inertia::render('projects/submittals/Create', [
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
     * Store a newly created submittal.
     */
    public function store(StoreSubmittalRequest $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $submittal = Submittal::create([
            ...$request->validated(),
            'project_id' => $project->id,
            'status' => WorkflowStatus::Draft->value,
        ]);

        activity()
            ->performedOn($submittal)
            ->causedBy($request->user())
            ->event('created')
            ->log('Submittal created');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Submittal created.')]);

        return to_route('projects.submittals.show', [$project, $submittal]);
    }

    /**
     * Display the specified submittal.
     */
    public function show(Project $project, Submittal $submittal): Response
    {
        Gate::authorize('view', $project);

        $submittal->load(['assignee', 'comments.user', 'approvalRequests.steps.approver']);

        return Inertia::render('projects/submittals/Show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'submittal' => [
                'id' => $submittal->id,
                'title' => $submittal->title,
                'description' => $submittal->description,
                'spec_section' => $submittal->spec_section,
                'revision_number' => $submittal->revision_number,
                'status' => $submittal->status,
                'due_date' => $submittal->due_date?->toDateString(),
                'submitted_at' => $submittal->submitted_at?->toISOString(),
                'approved_at' => $submittal->approved_at?->toISOString(),
                'rejection_reason' => $submittal->rejection_reason,
                'created_at' => $submittal->created_at->toISOString(),
                'assignee' => $submittal->assignee ? [
                    'id' => $submittal->assignee->id,
                    'name' => $submittal->assignee->name,
                ] : null,
                'comments' => $submittal->comments->map(fn ($comment) => [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'user' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                    ],
                    'created_at' => $comment->created_at->toISOString(),
                ]),
                'attachments' => $submittal->getAttachments()->map(fn ($media) => [
                    'id' => $media->id,
                    'name' => $media->name ?? $media->file_name,
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'human_size' => $media->human_readable_size,
                    'created_at' => $media->created_at->toISOString(),
                    'url' => $media->getUrl(),
                ]),
                'approval_requests' => $submittal->approvalRequests->map(fn ($request) => [
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
     * Show the form for editing the specified submittal.
     */
    public function edit(Project $project, Submittal $submittal): Response
    {
        Gate::authorize('update', $project);

        return Inertia::render('projects/submittals/Edit', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'submittal' => [
                'id' => $submittal->id,
                'title' => $submittal->title,
                'description' => $submittal->description,
                'spec_section' => $submittal->spec_section,
                'revision_number' => $submittal->revision_number,
                'status' => $submittal->status,
                'due_date' => $submittal->due_date?->toDateString(),
                'assigned_to' => $submittal->assigned_to,
            ],
            'users' => $project->teamMembers->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
            ]),
        ]);
    }

    /**
     * Update the specified submittal.
     */
    public function update(UpdateSubmittalRequest $request, Project $project, Submittal $submittal): RedirectResponse
    {
        Gate::authorize('update', $project);

        $oldStatus = $submittal->status;

        $submittal->update($request->validated());

        if ($request->has('status') && $request->validated('status') !== $oldStatus) {
            activity()
                ->performedOn($submittal)
                ->causedBy($request->user())
                ->event('status_changed')
                ->withProperties([
                    'old_status' => $oldStatus,
                    'new_status' => $request->validated('status'),
                ])
                ->log('Submittal status changed');
        } else {
            activity()
                ->performedOn($submittal)
                ->causedBy($request->user())
                ->event('updated')
                ->log('Submittal updated');
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Submittal updated.')]);

        return to_route('projects.submittals.show', [$project, $submittal]);
    }

    /**
     * Remove the specified submittal.
     */
    public function destroy(Request $request, Project $project, Submittal $submittal): RedirectResponse
    {
        Gate::authorize('update', $project);

        $submittal->delete();

        activity()
            ->performedOn($submittal)
            ->causedBy($request->user())
            ->event('deleted')
            ->log('Submittal deleted');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Submittal deleted.')]);

        return to_route('projects.submittals.index', $project);
    }

    /**
     * Submit the submittal for review.
     */
    public function submit(Request $request, Project $project, Submittal $submittal): RedirectResponse
    {
        Gate::authorize('update', $project);

        $submittal->markSubmitted();

        $approverId = $submittal->assigned_to ?? $project->teamMembers->first()?->id;
        if ($approverId) {
            $submittal->requestApproval(
                $request->user(),
                [['approver_id' => $approverId]],
                'Submitted for review',
            );
        }

        activity()
            ->performedOn($submittal)
            ->causedBy($request->user())
            ->event('submitted')
            ->log('Submittal submitted for review');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Submittal submitted for review.')]);

        return to_route('projects.submittals.show', [$project, $submittal]);
    }

    /**
     * Approve the submittal.
     */
    public function approve(Request $request, Project $project, Submittal $submittal): RedirectResponse
    {
        Gate::authorize('update', $project);

        $pendingRequest = $submittal->approvalRequests()->where('status', 'pending')->first();
        $step = $pendingRequest?->steps()
            ->where('approver_id', $request->user()->id)
            ->where('status', 'pending')
            ->first();

        if (! $step) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('No pending approval step found for you.')]);

            return to_route('projects.submittals.show', [$project, $submittal]);
        }

        $step->approve();

        if ($submittal->fresh()->status === WorkflowStatus::Approved->value) {
            $submittal->update(['approved_at' => now()]);
        }

        activity()
            ->performedOn($submittal)
            ->causedBy($request->user())
            ->event('approved')
            ->log('Submittal approved');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Submittal approved.')]);

        return to_route('projects.submittals.show', [$project, $submittal]);
    }

    /**
     * Reject the submittal.
     */
    public function reject(Request $request, Project $project, Submittal $submittal): RedirectResponse
    {
        Gate::authorize('update', $project);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ]);

        $pendingRequest = $submittal->approvalRequests()->where('status', 'pending')->first();
        $step = $pendingRequest?->steps()
            ->where('approver_id', $request->user()->id)
            ->where('status', 'pending')
            ->first();

        if (! $step) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('No pending approval step found for you.')]);

            return to_route('projects.submittals.show', [$project, $submittal]);
        }

        $step->reject($data['rejection_reason']);

        $submittal->update([
            'rejection_reason' => $data['rejection_reason'],
        ]);

        activity()
            ->performedOn($submittal)
            ->causedBy($request->user())
            ->event('rejected')
            ->withProperties(['reason' => $data['rejection_reason']])
            ->log('Submittal rejected');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Submittal rejected.')]);

        return to_route('projects.submittals.show', [$project, $submittal]);
    }

    /**
     * Request revision on the submittal.
     */
    public function requestRevision(Request $request, Project $project, Submittal $submittal): RedirectResponse
    {
        Gate::authorize('update', $project);

        $pendingRequest = $submittal->approvalRequests()->where('status', 'pending')->first();
        $step = $pendingRequest?->steps()
            ->where('approver_id', $request->user()->id)
            ->where('status', 'pending')
            ->first();

        if (! $step) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('No pending approval step found for you.')]);

            return to_route('projects.submittals.show', [$project, $submittal]);
        }

        $step->reject('Revision requested');

        $submittal->update([
            'status' => WorkflowStatus::Revision->value,
        ]);

        activity()
            ->performedOn($submittal)
            ->causedBy($request->user())
            ->event('revision_requested')
            ->log('Revision requested on submittal');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Revision requested.')]);

        return to_route('projects.submittals.show', [$project, $submittal]);
    }

    /**
     * Create a new revision of the submittal.
     */
    public function newRevision(Request $request, Project $project, Submittal $submittal): RedirectResponse
    {
        Gate::authorize('update', $project);

        $submittal->approvalRequests()
            ->where('status', 'pending')
            ->each(fn ($request) => $request->cancel());

        $submittal->newRevision();

        activity()
            ->performedOn($submittal)
            ->causedBy($request->user())
            ->event('revision_created')
            ->withProperties(['revision_number' => $submittal->revision_number])
            ->log('New revision created');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('New revision created.')]);

        return to_route('projects.submittals.show', [$project, $submittal]);
    }
}
