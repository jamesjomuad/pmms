<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePunchListItemRequest;
use App\Http\Requests\UpdatePunchListItemRequest;
use App\Models\Project;
use App\Models\PunchListItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PunchListItemController extends Controller
{
    /**
     * Display a listing of punch list items for a project.
     */
    public function index(Request $request, Project $project): Response
    {
        Gate::authorize('view', $project);

        $punchListItems = PunchListItem::forProject($project->id)
            ->with('assignee')
            ->latest()
            ->get()
            ->map(fn (PunchListItem $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'location' => $item->location,
                'trade' => $item->trade,
                'priority' => $item->priority,
                'status' => $item->status,
                'due_date' => $item->due_date?->toDateString(),
                'assigned_to' => $item->assignee?->name,
                'created_at' => $item->created_at->toISOString(),
            ]);

        return Inertia::render('projects/punch-list/Index', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'punchListItems' => $punchListItems,
        ]);
    }

    /**
     * Show the form for creating a new punch list item.
     */
    public function create(Project $project): Response
    {
        Gate::authorize('update', $project);

        return Inertia::render('projects/punch-list/Create', [
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
     * Store a newly created punch list item.
     */
    public function store(StorePunchListItemRequest $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $punchListItem = PunchListItem::create([
            ...$request->validated(),
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        activity()
            ->performedOn($punchListItem)
            ->causedBy($request->user())
            ->event('created')
            ->log('Punch list item created');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Punch list item created.')]);

        return to_route('projects.punch-list.show', [$project, $punchListItem]);
    }

    /**
     * Display the specified punch list item.
     */
    public function show(Project $project, PunchListItem $punchListItem): Response
    {
        Gate::authorize('view', $project);

        $punchListItem->load(['assignee', 'comments.user']);

        return Inertia::render('projects/punch-list/Show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'punchListItem' => [
                'id' => $punchListItem->id,
                'title' => $punchListItem->title,
                'description' => $punchListItem->description,
                'location' => $punchListItem->location,
                'trade' => $punchListItem->trade,
                'priority' => $punchListItem->priority,
                'status' => $punchListItem->status,
                'due_date' => $punchListItem->due_date?->toDateString(),
                'resolved_at' => $punchListItem->resolved_at?->toISOString(),
                'resolution_notes' => $punchListItem->resolution_notes,
                'created_at' => $punchListItem->created_at->toISOString(),
                'assignee' => $punchListItem->assignee ? [
                    'id' => $punchListItem->assignee->id,
                    'name' => $punchListItem->assignee->name,
                ] : null,
                'comments' => $punchListItem->comments->map(fn ($comment) => [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'user' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                    ],
                    'created_at' => $comment->created_at->toISOString(),
                ]),
                'attachments' => $punchListItem->getAttachments()->map(fn ($media) => [
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
     * Show the form for editing the specified punch list item.
     */
    public function edit(Project $project, PunchListItem $punchListItem): Response
    {
        Gate::authorize('update', $project);

        return Inertia::render('projects/punch-list/Edit', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'punchListItem' => [
                'id' => $punchListItem->id,
                'title' => $punchListItem->title,
                'description' => $punchListItem->description,
                'location' => $punchListItem->location,
                'trade' => $punchListItem->trade,
                'priority' => $punchListItem->priority,
                'status' => $punchListItem->status,
                'due_date' => $punchListItem->due_date?->toDateString(),
                'assigned_to' => $punchListItem->assigned_to,
            ],
            'users' => $project->teamMembers->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
            ]),
        ]);
    }

    /**
     * Update the specified punch list item.
     */
    public function update(UpdatePunchListItemRequest $request, Project $project, PunchListItem $punchListItem): RedirectResponse
    {
        Gate::authorize('update', $project);

        $punchListItem->update($request->validated());

        activity()
            ->performedOn($punchListItem)
            ->causedBy($request->user())
            ->event('updated')
            ->log('Punch list item updated');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Punch list item updated.')]);

        return to_route('projects.punch-list.show', [$project, $punchListItem]);
    }

    /**
     * Remove the specified punch list item.
     */
    public function destroy(Request $request, Project $project, PunchListItem $punchListItem): RedirectResponse
    {
        Gate::authorize('update', $project);

        $punchListItem->delete();

        activity()
            ->performedOn($punchListItem)
            ->causedBy($request->user())
            ->event('deleted')
            ->log('Punch list item deleted');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Punch list item deleted.')]);

        return to_route('projects.punch-list.index', $project);
    }

    /**
     * Mark the punch list item as resolved.
     */
    public function resolve(Request $request, Project $project, PunchListItem $punchListItem): RedirectResponse
    {
        Gate::authorize('update', $project);

        $data = $request->validate([
            'resolution_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $punchListItem->markResolved($data['resolution_notes'] ?? null);

        activity()
            ->performedOn($punchListItem)
            ->causedBy($request->user())
            ->event('resolved')
            ->log('Punch list item resolved');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Punch list item resolved.')]);

        return to_route('projects.punch-list.show', [$project, $punchListItem]);
    }
}
