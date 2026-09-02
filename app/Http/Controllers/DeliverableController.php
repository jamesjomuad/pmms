<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeliverableRequest;
use App\Models\Project;
use App\Models\Stage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class DeliverableController extends Controller
{
    /**
     * Store a new deliverable for a project.
     */
    public function store(StoreDeliverableRequest $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $validated = $request->validated();

        /** @var Stage $stage */
        $stage = Stage::findOrFail($validated['stage_id']);

        $project->addMedia($request->file('file'))
            ->withCustomProperties([
                'stage_id' => $stage->id,
                'stage_key' => $stage->key,
                'description' => $validated['description'] ?? null,
                'uploaded_by' => $request->user()->id,
            ])
            ->toMediaCollection('deliverables');

        activity()
            ->performedOn($project)
            ->causedBy($request->user())
            ->event('deliverable_added')
            ->withProperties(['file_name' => $request->file('file')->getClientOriginalName()])
            ->log('Deliverable uploaded');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Deliverable uploaded.']);

        return to_route('projects.show', $project);
    }

    /**
     * Remove a deliverable from a project.
     */
    public function destroy(Project $project, int $mediaId): RedirectResponse
    {
        Gate::authorize('update', $project);

        $media = $project->getMedia('deliverables')->firstWhere('id', $mediaId);

        if (! $media) {
            throw ValidationException::withMessages([
                'media_id' => 'Deliverable not found.',
            ]);
        }

        $media->delete();

        activity()
            ->performedOn($project)
            ->causedBy(auth()->user())
            ->event('deliverable_removed')
            ->withProperties(['file_name' => $media->file_name])
            ->log('Deliverable deleted');

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Deliverable deleted.']);

        return to_route('projects.show', $project);
    }
}
