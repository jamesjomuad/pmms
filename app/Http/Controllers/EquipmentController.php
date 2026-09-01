<?php

namespace App\Http\Controllers;

use App\Enums\WorkflowStatus;
use App\Http\Requests\StoreEquipmentRequest;
use App\Http\Requests\UpdateEquipmentRequest;
use App\Models\EquipmentItem;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EquipmentController extends Controller
{
    /**
     * Display a listing of equipment items for a project.
     */
    public function index(Request $request, Project $project): Response
    {
        Gate::authorize('view', $project);

        $equipment = EquipmentItem::forProject($project->id)
            ->with('assignee')
            ->latest()
            ->get()
            ->map(fn (EquipmentItem $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'manufacturer' => $item->manufacturer,
                'model_number' => $item->model_number,
                'cost' => $item->cost,
                'quantity' => $item->quantity,
                'status' => $item->status,
                'po_status' => $item->po_status,
                'expected_delivery' => $item->expected_delivery?->toDateString(),
                'assigned_to' => $item->assignee?->name,
                'created_at' => $item->created_at->toISOString(),
            ]);

        return Inertia::render('projects/equipment/Index', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'equipment' => $equipment,
        ]);
    }

    /**
     * Show the form for creating a new equipment item.
     */
    public function create(Project $project): Response
    {
        Gate::authorize('update', $project);

        return Inertia::render('projects/equipment/Create', [
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
     * Store a newly created equipment item.
     */
    public function store(StoreEquipmentRequest $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $equipment = EquipmentItem::create([
            ...$request->validated(),
            'project_id' => $project->id,
            'status' => WorkflowStatus::Draft->value,
        ]);

        activity()
            ->performedOn($equipment)
            ->causedBy($request->user())
            ->event('created')
            ->log('Equipment item created');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Equipment item created.')]);

        return to_route('projects.equipment.show', [$project, $equipment]);
    }

    /**
     * Display the specified equipment item.
     */
    public function show(Project $project, EquipmentItem $equipment): Response
    {
        Gate::authorize('view', $project);

        $equipment->load(['assignee', 'comments.user']);

        return Inertia::render('projects/equipment/Show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'equipment' => [
                'id' => $equipment->id,
                'title' => $equipment->title,
                'description' => $equipment->description,
                'manufacturer' => $equipment->manufacturer,
                'model_number' => $equipment->model_number,
                'serial_number' => $equipment->serial_number,
                'cost' => $equipment->cost,
                'quantity' => $equipment->quantity,
                'total_cost' => $equipment->total_cost,
                'status' => $equipment->status,
                'po_status' => $equipment->po_status,
                'lead_time' => $equipment->lead_time?->toDateString(),
                'expected_delivery' => $equipment->expected_delivery?->toDateString(),
                'received_at' => $equipment->received_at?->toISOString(),
                'created_at' => $equipment->created_at->toISOString(),
                'assignee' => $equipment->assignee ? [
                    'id' => $equipment->assignee->id,
                    'name' => $equipment->assignee->name,
                ] : null,
                'procurement' => [
                    'quotation_count' => $equipment->supplierQuotations()->count(),
                    'purchase_order_count' => $equipment->purchaseOrders()->count(),
                    'inspection_count' => $equipment->inspections()->count(),
                ],
                'comments' => $equipment->comments->map(fn ($comment) => [
                    'id' => $comment->id,
                    'body' => $comment->body,
                    'user' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                    ],
                    'created_at' => $comment->created_at->toISOString(),
                ]),
                'attachments' => $equipment->getAttachments()->map(fn ($media) => [
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
     * Show the form for editing the specified equipment item.
     */
    public function edit(Project $project, EquipmentItem $equipment): Response
    {
        Gate::authorize('update', $project);

        return Inertia::render('projects/equipment/Edit', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'equipment' => [
                'id' => $equipment->id,
                'title' => $equipment->title,
                'description' => $equipment->description,
                'manufacturer' => $equipment->manufacturer,
                'model_number' => $equipment->model_number,
                'serial_number' => $equipment->serial_number,
                'cost' => $equipment->cost,
                'quantity' => $equipment->quantity,
                'status' => $equipment->status,
                'po_status' => $equipment->po_status,
                'lead_time' => $equipment->lead_time?->toDateString(),
                'expected_delivery' => $equipment->expected_delivery?->toDateString(),
                'assigned_to' => $equipment->assigned_to,
            ],
            'users' => $project->teamMembers->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
            ]),
        ]);
    }

    /**
     * Update the specified equipment item.
     */
    public function update(UpdateEquipmentRequest $request, Project $project, EquipmentItem $equipment): RedirectResponse
    {
        Gate::authorize('update', $project);

        $equipment->update($request->validated());

        activity()
            ->performedOn($equipment)
            ->causedBy($request->user())
            ->event('updated')
            ->log('Equipment item updated');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Equipment item updated.')]);

        return to_route('projects.equipment.show', [$project, $equipment]);
    }

    /**
     * Remove the specified equipment item.
     */
    public function destroy(Request $request, Project $project, EquipmentItem $equipment): RedirectResponse
    {
        Gate::authorize('update', $project);

        $equipment->delete();

        activity()
            ->performedOn($equipment)
            ->causedBy($request->user())
            ->event('deleted')
            ->log('Equipment item deleted');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Equipment item deleted.')]);

        return to_route('projects.equipment.index', $project);
    }

    /**
     * Mark the equipment item as received.
     */
    public function receive(Request $request, Project $project, EquipmentItem $equipment): RedirectResponse
    {
        Gate::authorize('update', $project);

        $equipment->markReceived();

        activity()
            ->performedOn($equipment)
            ->causedBy($request->user())
            ->event('received')
            ->log('Equipment item received');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Equipment item marked as received.')]);

        return to_route('projects.equipment.show', [$project, $equipment]);
    }
}
