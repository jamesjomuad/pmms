<?php

namespace App\Http\Controllers;

use App\Enums\WorkflowStatus;
use App\Http\Requests\StoreRfiRequest;
use App\Http\Requests\UpdateRfiRequest;
use App\Models\Project;
use App\Models\Rfi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RfiController extends Controller
{
    /**
     * Display a listing of RFIs for a project.
     */
    public function index(Request $request, Project $project): Response
    {
        Gate::authorize('view', $project);

        $rfis = Rfi::forProject($project->id)
            ->with('assignee', 'creator')
            ->latest()
            ->get()
            ->map(fn (Rfi $rfi) => [
                'id' => $rfi->id,
                'title' => $rfi->title,
                'status' => $rfi->status,
                'due_date' => $rfi->due_date?->toDateString(),
                'assigned_to' => $rfi->assignee?->name,
                'created_by' => $rfi->creator->name,
                'created_at' => $rfi->created_at->toISOString(),
            ]);

        return Inertia::render('projects/rfis/Index', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'rfis' => $rfis,
        ]);
    }

    /**
     * Show the form for creating a new RFI.
     */
    public function create(Project $project): Response
    {
        Gate::authorize('update', $project);

        return Inertia::render('projects/rfis/Create', [
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
     * Store a newly created RFI.
     */
    public function store(StoreRfiRequest $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $rfi = Rfi::create([
            ...$request->validated(),
            'project_id' => $project->id,
            'created_by' => $request->user()->id,
            'status' => WorkflowStatus::Draft->value,
        ]);

        activity()
            ->performedOn($rfi)
            ->causedBy($request->user())
            ->event('created')
            ->log('RFI created');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('RFI created.')]);

        return to_route('projects.rfis.show', [$project, $rfi]);
    }

    /**
     * Display the specified RFI.
     */
    public function show(Project $project, Rfi $rfi): Response
    {
        Gate::authorize('view', $project);

        $rfi->load(['assignee', 'creator', 'comments.user']);

        return Inertia::render('projects/rfis/Show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'rfi' => [
                'id' => $rfi->id,
                'title' => $rfi->title,
                'question' => $rfi->question,
                'response' => $rfi->response,
                'status' => $rfi->status,
                'due_date' => $rfi->due_date?->toDateString(),
                'responded_at' => $rfi->responded_at?->toISOString(),
                'created_at' => $rfi->created_at->toISOString(),
                'assignee' => $rfi->assignee ? [
                    'id' => $rfi->assignee->id,
                    'name' => $rfi->assignee->name,
                ] : null,
                'creator' => [
                    'id' => $rfi->creator->id,
                    'name' => $rfi->creator->name,
                ],
                'comments' => $rfi->comments->map(fn ($comment) => [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'user' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                    ],
                    'created_at' => $comment->created_at->toISOString(),
                ]),
                'attachments' => $rfi->getAttachments()->map(fn ($media) => [
                    'id' => $media->id,
                    'name' => $media->name ?? $media->file_name,
                    'file_name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                    'human_size' => $media->human_readable_size,
                    'created_at' => $media->created_at->toISOString(),
                    'url' => $media->getUrl(),
                ]),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified RFI.
     */
    public function edit(Project $project, Rfi $rfi): Response
    {
        Gate::authorize('update', $project);

        return Inertia::render('projects/rfis/Edit', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'rfi' => [
                'id' => $rfi->id,
                'title' => $rfi->title,
                'question' => $rfi->question,
                'response' => $rfi->response,
                'status' => $rfi->status,
                'due_date' => $rfi->due_date?->toDateString(),
                'assigned_to' => $rfi->assigned_to,
            ],
            'users' => $project->teamMembers->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
            ]),
        ]);
    }

    /**
     * Update the specified RFI.
     */
    public function update(UpdateRfiRequest $request, Project $project, Rfi $rfi): RedirectResponse
    {
        Gate::authorize('update', $project);

        $rfi->update($request->validated());

        activity()
            ->performedOn($rfi)
            ->causedBy($request->user())
            ->event('updated')
            ->log('RFI updated');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('RFI updated.')]);

        return to_route('projects.rfis.show', [$project, $rfi]);
    }

    /**
     * Remove the specified RFI.
     */
    public function destroy(Request $request, Project $project, Rfi $rfi): RedirectResponse
    {
        Gate::authorize('update', $project);

        $rfi->delete();

        activity()
            ->performedOn($rfi)
            ->causedBy($request->user())
            ->event('deleted')
            ->log('RFI deleted');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('RFI deleted.')]);

        return to_route('projects.rfis.index', $project);
    }

    /**
     * Submit the RFI for response.
     */
    public function submit(Request $request, Project $project, Rfi $rfi): RedirectResponse
    {
        Gate::authorize('update', $project);

        $rfi->update([
            'status' => WorkflowStatus::Pending->value,
        ]);

        activity()
            ->performedOn($rfi)
            ->causedBy($request->user())
            ->event('submitted')
            ->log('RFI submitted for response');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('RFI submitted for response.')]);

        return to_route('projects.rfis.show', [$project, $rfi]);
    }

    /**
     * Respond to the RFI.
     */
    public function respond(Request $request, Project $project, Rfi $rfi): RedirectResponse
    {
        Gate::authorize('update', $project);

        $data = $request->validate([
            'response' => ['required', 'string', 'max:10000'],
        ]);

        $rfi->markResponded($data['response']);

        activity()
            ->performedOn($rfi)
            ->causedBy($request->user())
            ->event('responded')
            ->log('RFI responded');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('RFI responded.')]);

        return to_route('projects.rfis.show', [$project, $rfi]);
    }
}
