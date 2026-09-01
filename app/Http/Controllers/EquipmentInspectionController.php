<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEquipmentInspectionRequest;
use App\Http\Requests\UpdateEquipmentInspectionRequest;
use App\Models\EquipmentInspection;
use App\Models\EquipmentItem;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EquipmentInspectionController extends Controller
{
    /**
     * Display a listing of inspections for an equipment item.
     */
    public function index(Request $request, Project $project, EquipmentItem $equipment): Response
    {
        Gate::authorize('view', $project);

        $inspections = $equipment->inspections()
            ->with('inspector')
            ->latest()
            ->get()
            ->map(fn (EquipmentInspection $inspection) => $this->present($inspection));

        return Inertia::render('projects/equipment/inspections/Index', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'equipment' => $this->presentEquipment($equipment),
            'inspections' => $inspections,
        ]);
    }

    /**
     * Show the form for creating a new inspection.
     */
    public function create(Request $request, Project $project, EquipmentItem $equipment): Response
    {
        Gate::authorize('update', $project);

        return Inertia::render('projects/equipment/inspections/Create', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'equipment' => $this->presentEquipment($equipment),
            'users' => $project->teamMembers->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
            ]),
        ]);
    }

    /**
     * Store a newly created inspection.
     */
    public function store(StoreEquipmentInspectionRequest $request, Project $project, EquipmentItem $equipment): RedirectResponse
    {
        Gate::authorize('update', $project);

        $inspection = $equipment->inspections()->create($request->validated());

        activity()
            ->performedOn($inspection)
            ->causedBy($request->user())
            ->event('created')
            ->log('Equipment inspection created');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Equipment inspection created.')]);

        return to_route('projects.equipment.inspections.show', [$project, $equipment, $inspection]);
    }

    /**
     * Display the specified inspection.
     */
    public function show(Project $project, EquipmentItem $equipment, EquipmentInspection $inspection): Response
    {
        Gate::authorize('view', $project);

        $inspection->load('inspector');

        return Inertia::render('projects/equipment/inspections/Show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'equipment' => $this->presentEquipment($equipment),
            'inspection' => $this->present($inspection),
        ]);
    }

    /**
     * Show the form for editing the specified inspection.
     */
    public function edit(Request $request, Project $project, EquipmentItem $equipment, EquipmentInspection $inspection): Response
    {
        Gate::authorize('update', $project);

        return Inertia::render('projects/equipment/inspections/Edit', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'equipment' => $this->presentEquipment($equipment),
            'users' => $project->teamMembers->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
            ]),
            'inspection' => [
                'id' => $inspection->id,
                'inspected_by' => $inspection->inspected_by,
                'inspected_date' => $inspection->inspected_date?->toDateString(),
                'result' => $inspection->result,
                'notes' => $inspection->notes,
            ],
        ]);
    }

    /**
     * Update the specified inspection.
     */
    public function update(UpdateEquipmentInspectionRequest $request, Project $project, EquipmentItem $equipment, EquipmentInspection $inspection): RedirectResponse
    {
        Gate::authorize('update', $project);

        $inspection->update($request->validated());

        activity()
            ->performedOn($inspection)
            ->causedBy($request->user())
            ->event('updated')
            ->log('Equipment inspection updated');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Equipment inspection updated.')]);

        return to_route('projects.equipment.inspections.show', [$project, $equipment, $inspection]);
    }

    /**
     * Remove the specified inspection.
     */
    public function destroy(Request $request, Project $project, EquipmentItem $equipment, EquipmentInspection $inspection): RedirectResponse
    {
        Gate::authorize('update', $project);

        $inspection->delete();

        activity()
            ->performedOn($inspection)
            ->causedBy($request->user())
            ->event('deleted')
            ->log('Equipment inspection deleted');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Equipment inspection deleted.')]);

        return to_route('projects.equipment.inspections.index', [$project, $equipment]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(EquipmentInspection $inspection): array
    {
        return [
            'id' => $inspection->id,
            'inspected_by' => $inspection->inspected_by,
            'inspected_date' => $inspection->inspected_date?->toDateString(),
            'result' => $inspection->result,
            'notes' => $inspection->notes,
            'created_at' => $inspection->created_at->toISOString(),
            'inspector' => $inspection->inspector ? [
                'id' => $inspection->inspector->id,
                'name' => $inspection->inspector->name,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentEquipment(EquipmentItem $equipment): array
    {
        return [
            'id' => $equipment->id,
            'title' => $equipment->title,
            'manufacturer' => $equipment->manufacturer,
            'model_number' => $equipment->model_number,
        ];
    }
}
