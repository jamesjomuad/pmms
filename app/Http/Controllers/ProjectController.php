<?php

namespace App\Http\Controllers;

use App\Enums\ProjectRole;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Models\ProjectStageHistory;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $projects = Project::query()
            ->with(['currentStage', 'teamMembers'])
            ->whereHas('teamMembers', fn ($q) => $q->where('users.id', $user->id))
            ->latest()
            ->get()
            ->map(function (Project $project) {
                $teamCount = $project->teamMembers->count();

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'client_name' => $project->client_name,
                    'project_number' => $project->project_number,
                    'awarded_date' => $project->awarded_date->toDateString(),
                    'estimated_completion_date' => $project->estimated_completion_date?->toDateString(),
                    'status' => $project->status,
                    'address' => $project->address,
                    'city' => $project->city,
                    'state' => $project->state,
                    'postal_code' => $project->postal_code,
                    'latitude' => $project->latitude !== null ? (float) $project->latitude : null,
                    'longitude' => $project->longitude !== null ? (float) $project->longitude : null,
                    'full_address' => $project->full_address,
                    'maps_url' => $project->maps_url,
                    'current_stage' => [
                        'id' => $project->currentStage->id,
                        'key' => $project->currentStage->key,
                        'label' => $project->currentStage->label,
                    ],
                    'team_count' => $teamCount,
                    'updated_at' => $project->updated_at->toISOString(),
                ];
            });

        return Inertia::render('projects/Index', [
            'projects' => $projects,
            'stages' => Stage::orderBy('sort_order')->get()->map(fn (Stage $stage) => [
                'id' => $stage->id,
                'key' => $stage->key,
                'label' => $stage->label,
            ]),
        ]);
    }

    /**
     * Show the form for creating a new project.
     */
    public function create(): Response
    {
        return Inertia::render('projects/Create', [
            'stages' => Stage::orderBy('sort_order')->get()->map(fn (Stage $stage) => [
                'id' => $stage->id,
                'key' => $stage->key,
                'label' => $stage->label,
            ]),
            'projectRoles' => ProjectRole::options(),
            'users' => User::orderBy('name')->get()->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]),
            'nextProjectNumber' => self::generateProjectNumber(),
        ]);
    }

    /**
     * Generate the next available project number.
     */
    private static function generateProjectNumber(): string
    {
        $lastProject = Project::withTrashed()
            ->where('project_number', 'like', 'PMMS-%')
            ->orderByRaw('substring(project_number from 6)::int desc')
            ->first();

        if ($lastProject) {
            $lastNumber = (int) substr($lastProject->project_number, 5);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return 'PMMS-'.str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Store a newly created project.
     */
    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = DB::transaction(function () use ($request) {
            $awardedStage = Stage::where('key', 'awarded')->firstOrFail();

            $projectNumber = $request->validated('project_number') ?? self::generateProjectNumber();

            $project = Project::create([
                'current_stage_id' => $awardedStage->id,
                'name' => $request->validated('name'),
                'client_name' => $request->validated('client_name'),
                'project_number' => $projectNumber,
                'awarded_date' => $request->validated('awarded_date'),
                'estimated_completion_date' => $request->validated('estimated_completion_date'),
                'notes' => $request->validated('notes'),
                'address' => $request->validated('address'),
                'city' => $request->validated('city'),
                'state' => $request->validated('state'),
                'postal_code' => $request->validated('postal_code'),
                'latitude' => $request->validated('latitude'),
                'longitude' => $request->validated('longitude'),
            ]);

            if ($team = $request->validated('team')) {
                foreach ($team as $member) {
                    $project->teamMembers()->attach($member['user_id'], [
                        'project_role' => $member['project_role'],
                    ]);
                }
            }

            ProjectStageHistory::create([
                'project_id' => $project->id,
                'from_stage_id' => null,
                'to_stage_id' => $awardedStage->id,
                'changed_by' => $request->user()->id,
                'changed_at' => now(),
                'notes' => 'Project awarded.',
            ]);

            return $project;
        });

        activity()
            ->performedOn($project)
            ->causedBy($request->user())
            ->event('created')
            ->log('Project created');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project created.')]);

        return to_route('projects.show', $project);
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project): Response
    {
        Gate::authorize('view', $project);

        $project->load(['currentStage', 'teamMembers', 'stageHistory.fromStage', 'stageHistory.toStage', 'stageHistory.changedByUser']);

        /** @var Collection<int, Media> $mediaItems */
        $mediaItems = $project->getMedia('deliverables');
        $deliverables = $mediaItems->map(function (Media $media) {
            return [
                'id' => $media->id,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'human_size' => $media->human_readable_size,
                'description' => $media->getCustomProperty('description'),
                'stage_id' => $media->getCustomProperty('stage_id'),
                'stage_key' => $media->getCustomProperty('stage_key'),
                'uploaded_by' => $media->getCustomProperty('uploaded_by'),
                'created_at' => $media->created_at->toISOString(),
                'url' => $media->getUrl(),
                'preview_url' => null,
            ];
        })->values();

        return Inertia::render('projects/Show', [
            'stages' => Stage::orderBy('sort_order')->get()->map(fn (Stage $stage) => [
                'id' => $stage->id,
                'key' => $stage->key,
                'label' => $stage->label,
            ]),
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'client_name' => $project->client_name,
                'project_number' => $project->project_number,
                'awarded_date' => $project->awarded_date->toDateString(),
                'estimated_completion_date' => $project->estimated_completion_date?->toDateString(),
                'status' => $project->status,
                'notes' => $project->notes,
                'address' => $project->address,
                'city' => $project->city,
                'state' => $project->state,
                'postal_code' => $project->postal_code,
                'latitude' => $project->latitude !== null ? (float) $project->latitude : null,
                'longitude' => $project->longitude !== null ? (float) $project->longitude : null,
                'full_address' => $project->full_address,
                'maps_url' => $project->maps_url,
                'created_at' => $project->created_at->toISOString(),
                'current_stage' => [
                    'id' => $project->currentStage->id,
                    'key' => $project->currentStage->key,
                    'label' => $project->currentStage->label,
                ],
                'team' => $project->teamMembers->map(function (User $member) {
                    $projectRole = $member->pivot->project_role; // @phpstan-ignore property.notFound

                    return [
                        'id' => $member->id,
                        'name' => $member->name,
                        'email' => $member->email,
                        'project_role' => $projectRole,
                        'project_role_label' => ProjectRole::from($projectRole)->label(),
                    ];
                }),
                'stage_history' => $project->stageHistory->map(fn (ProjectStageHistory $entry) => [
                    'id' => $entry->id,
                    'from_stage' => $entry->fromStage?->label,
                    'to_stage' => $entry->toStage->label,
                    'changed_by' => $entry->changedByUser->name,
                    'changed_at' => $entry->changed_at->toISOString(),
                    'notes' => $entry->notes,
                ]),
                'deliverables' => $deliverables,
                'activity_log' => $project->activitiesAsSubject()
                    ->latest()
                    ->with('causer')
                    ->limit(50)
                    ->get()
                    ->map(fn ($activity) => [
                        'id' => $activity->id,
                        'description' => $activity->description,
                        'event' => $activity->event,
                        'causer' => $activity->causer ? [
                            'id' => $activity->causer->id,
                            'name' => $activity->causer->name,
                        ] : null,
                        'properties' => $activity->properties->toArray(),
                        'created_at' => $activity->created_at->toISOString(),
                    ]),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified project.
     */
    public function edit(Project $project): Response
    {
        Gate::authorize('update', $project);

        $project->load('teamMembers');

        return Inertia::render('projects/Edit', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'client_name' => $project->client_name,
                'project_number' => $project->project_number,
                'awarded_date' => $project->awarded_date->toDateString(),
                'estimated_completion_date' => $project->estimated_completion_date?->toDateString(),
                'status' => $project->status,
                'notes' => $project->notes,
                'address' => $project->address,
                'city' => $project->city,
                'state' => $project->state,
                'postal_code' => $project->postal_code,
                'latitude' => $project->latitude !== null ? (float) $project->latitude : null,
                'longitude' => $project->longitude !== null ? (float) $project->longitude : null,
                'full_address' => $project->full_address,
                'maps_url' => $project->maps_url,
                'team' => $project->teamMembers->map(function (User $member) {
                    $projectRole = $member->pivot->project_role; // @phpstan-ignore property.notFound

                    return [
                        'user_id' => $member->id,
                        'name' => $member->name,
                        'email' => $member->email,
                        'project_role' => $projectRole,
                    ];
                }),
            ],
            'stages' => Stage::orderBy('sort_order')->get()->map(fn (Stage $stage) => [
                'id' => $stage->id,
                'key' => $stage->key,
                'label' => $stage->label,
            ]),
            'projectRoles' => ProjectRole::options(),
            'users' => User::orderBy('name')->get()->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]),
        ]);
    }

    /**
     * Update the specified project.
     */
    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        DB::transaction(function () use ($request, $project) {
            $project->update($request->only([
                'name',
                'client_name',
                'project_number',
                'awarded_date',
                'estimated_completion_date',
                'status',
                'notes',
                'address',
                'city',
                'state',
                'postal_code',
                'latitude',
                'longitude',
            ]));

            if ($request->has('team')) {
                $project->teamMembers()->detach();

                foreach ($request->validated('team') as $member) {
                    $project->teamMembers()->attach($member['user_id'], [
                        'project_role' => $member['project_role'],
                    ]);
                }
            }
        });

        activity()
            ->performedOn($project)
            ->causedBy($request->user())
            ->event('updated')
            ->log('Project updated');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project updated.')]);

        return to_route('projects.show', $project);
    }

    /**
     * Remove the specified project.
     */
    public function destroy(Project $project): RedirectResponse
    {
        Gate::authorize('delete', $project);

        $project->delete();

        activity()
            ->performedOn($project)
            ->causedBy(auth()->user())
            ->event('deleted')
            ->log('Project deleted');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project deleted.')]);

        return to_route('projects.index');
    }
}
