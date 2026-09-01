<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierQuotationRequest;
use App\Http\Requests\UpdateSupplierQuotationRequest;
use App\Models\EquipmentItem;
use App\Models\Project;
use App\Models\SupplierQuotation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SupplierQuotationController extends Controller
{
    /**
     * Display a listing of supplier quotations for an equipment item.
     */
    public function index(Request $request, Project $project, EquipmentItem $equipment): Response
    {
        Gate::authorize('view', $project);

        $quotations = $equipment->supplierQuotations()
            ->latest()
            ->get()
            ->map(fn (SupplierQuotation $quotation) => $this->present($quotation));

        return Inertia::render('projects/equipment/quotations/Index', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'equipment' => $this->presentEquipment($equipment),
            'quotations' => $quotations,
        ]);
    }

    /**
     * Show the form for creating a new supplier quotation.
     */
    public function create(Project $project, EquipmentItem $equipment): Response
    {
        Gate::authorize('update', $project);

        return Inertia::render('projects/equipment/quotations/Create', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'equipment' => $this->presentEquipment($equipment),
        ]);
    }

    /**
     * Store a newly created supplier quotation.
     */
    public function store(StoreSupplierQuotationRequest $request, Project $project, EquipmentItem $equipment): RedirectResponse
    {
        Gate::authorize('update', $project);

        $quotation = $equipment->supplierQuotations()->create($request->validated());

        activity()
            ->performedOn($quotation)
            ->causedBy($request->user())
            ->event('created')
            ->log('Supplier quotation created');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supplier quotation created.')]);

        return to_route('projects.equipment.quotations.show', [$project, $equipment, $quotation]);
    }

    /**
     * Display the specified supplier quotation.
     */
    public function show(Project $project, EquipmentItem $equipment, SupplierQuotation $quotation): Response
    {
        Gate::authorize('view', $project);

        return Inertia::render('projects/equipment/quotations/Show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'equipment' => $this->presentEquipment($equipment),
            'quotation' => $this->present($quotation),
        ]);
    }

    /**
     * Show the form for editing the specified supplier quotation.
     */
    public function edit(Project $project, EquipmentItem $equipment, SupplierQuotation $quotation): Response
    {
        Gate::authorize('update', $project);

        return Inertia::render('projects/equipment/quotations/Edit', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'equipment' => $this->presentEquipment($equipment),
            'quotation' => [
                'id' => $quotation->id,
                'supplier_name' => $quotation->supplier_name,
                'quoted_cost' => $quotation->quoted_cost,
                'quoted_lead_time_days' => $quotation->quoted_lead_time_days,
                'quote_date' => $quotation->quote_date?->toDateString(),
                'status' => $quotation->status,
            ],
        ]);
    }

    /**
     * Update the specified supplier quotation.
     */
    public function update(UpdateSupplierQuotationRequest $request, Project $project, EquipmentItem $equipment, SupplierQuotation $quotation): RedirectResponse
    {
        Gate::authorize('update', $project);

        $quotation->update($request->validated());

        activity()
            ->performedOn($quotation)
            ->causedBy($request->user())
            ->event('updated')
            ->log('Supplier quotation updated');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supplier quotation updated.')]);

        return to_route('projects.equipment.quotations.show', [$project, $equipment, $quotation]);
    }

    /**
     * Remove the specified supplier quotation.
     */
    public function destroy(Request $request, Project $project, EquipmentItem $equipment, SupplierQuotation $quotation): RedirectResponse
    {
        Gate::authorize('update', $project);

        $quotation->delete();

        activity()
            ->performedOn($quotation)
            ->causedBy($request->user())
            ->event('deleted')
            ->log('Supplier quotation deleted');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supplier quotation deleted.')]);

        return to_route('projects.equipment.quotations.index', [$project, $equipment]);
    }

    /**
     * Mark the supplier quotation as selected.
     */
    public function select(Request $request, Project $project, EquipmentItem $equipment, SupplierQuotation $quotation): RedirectResponse
    {
        Gate::authorize('update', $project);

        $equipment->supplierQuotations()
            ->where('status', 'selected')
            ->update(['status' => 'declined']);

        $quotation->markSelected();

        activity()
            ->performedOn($quotation)
            ->causedBy($request->user())
            ->event('selected')
            ->log('Supplier quotation selected');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supplier quotation selected.')]);

        return to_route('projects.equipment.quotations.show', [$project, $equipment, $quotation]);
    }

    /**
     * Mark the supplier quotation as declined.
     */
    public function decline(Request $request, Project $project, EquipmentItem $equipment, SupplierQuotation $quotation): RedirectResponse
    {
        Gate::authorize('update', $project);

        $quotation->markDeclined();

        activity()
            ->performedOn($quotation)
            ->causedBy($request->user())
            ->event('declined')
            ->log('Supplier quotation declined');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Supplier quotation declined.')]);

        return to_route('projects.equipment.quotations.show', [$project, $equipment, $quotation]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(SupplierQuotation $quotation): array
    {
        return [
            'id' => $quotation->id,
            'supplier_name' => $quotation->supplier_name,
            'quoted_cost' => $quotation->quoted_cost,
            'quoted_lead_time_days' => $quotation->quoted_lead_time_days,
            'quote_date' => $quotation->quote_date?->toDateString(),
            'status' => $quotation->status,
            'created_at' => $quotation->created_at->toISOString(),
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
