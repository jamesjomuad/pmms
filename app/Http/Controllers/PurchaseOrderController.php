<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Models\EquipmentItem;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\SupplierQuotation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of purchase orders for an equipment item.
     */
    public function index(Request $request, Project $project, EquipmentItem $equipment): Response
    {
        Gate::authorize('view', $project);

        $purchaseOrders = $equipment->purchaseOrders()
            ->with('supplierQuotation')
            ->latest()
            ->get()
            ->map(fn (PurchaseOrder $po) => $this->present($po));

        return Inertia::render('projects/equipment/purchase-orders/Index', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'equipment' => $this->presentEquipment($equipment),
            'purchaseOrders' => $purchaseOrders,
        ]);
    }

    /**
     * Show the form for creating a new purchase order.
     */
    public function create(Request $request, Project $project, EquipmentItem $equipment): Response
    {
        Gate::authorize('update', $project);

        $selected = $equipment->supplierQuotations()
            ->where('status', 'selected')
            ->first();

        return Inertia::render('projects/equipment/purchase-orders/Create', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'equipment' => $this->presentEquipment($equipment),
            'quotations' => $this->presentQuotations($equipment),
            'selectedQuotation' => $selected ? $this->presentQuotation($selected) : null,
        ]);
    }

    /**
     * Store a newly created purchase order.
     */
    public function store(StorePurchaseOrderRequest $request, Project $project, EquipmentItem $equipment): RedirectResponse
    {
        Gate::authorize('update', $project);

        $po = $equipment->purchaseOrders()->create($request->validated());

        $po->update(['status' => 'issued']);
        $equipment->update(['po_status' => 'ordered']);

        activity()
            ->performedOn($po)
            ->causedBy($request->user())
            ->event('created')
            ->log('Purchase order created');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Purchase order created.')]);

        return to_route('projects.equipment.purchase-orders.show', [$project, $equipment, $po]);
    }

    /**
     * Display the specified purchase order.
     */
    public function show(Project $project, EquipmentItem $equipment, PurchaseOrder $purchaseOrder): Response
    {
        Gate::authorize('view', $project);

        $purchaseOrder->load('supplierQuotation');

        return Inertia::render('projects/equipment/purchase-orders/Show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'equipment' => $this->presentEquipment($equipment),
            'purchaseOrder' => $this->present($purchaseOrder),
        ]);
    }

    /**
     * Show the form for editing the specified purchase order.
     */
    public function edit(Project $project, EquipmentItem $equipment, PurchaseOrder $purchaseOrder): Response
    {
        Gate::authorize('update', $project);

        return Inertia::render('projects/equipment/purchase-orders/Edit', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'equipment' => $this->presentEquipment($equipment),
            'quotations' => $this->presentQuotations($equipment),
            'purchaseOrder' => [
                'id' => $purchaseOrder->id,
                'supplier_quotation_id' => $purchaseOrder->supplier_quotation_id,
                'po_number' => $purchaseOrder->po_number,
                'issued_date' => $purchaseOrder->issued_date?->toDateString(),
                'cost' => $purchaseOrder->cost,
                'expected_delivery_date' => $purchaseOrder->expected_delivery_date?->toDateString(),
                'actual_delivery_date' => $purchaseOrder->actual_delivery_date?->toDateString(),
                'status' => $purchaseOrder->status,
            ],
        ]);
    }

    /**
     * Update the specified purchase order.
     */
    public function update(UpdatePurchaseOrderRequest $request, Project $project, EquipmentItem $equipment, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        Gate::authorize('update', $project);

        $purchaseOrder->update($request->validated());

        activity()
            ->performedOn($purchaseOrder)
            ->causedBy($request->user())
            ->event('updated')
            ->log('Purchase order updated');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Purchase order updated.')]);

        return to_route('projects.equipment.purchase-orders.show', [$project, $equipment, $purchaseOrder]);
    }

    /**
     * Remove the specified purchase order.
     */
    public function destroy(Request $request, Project $project, EquipmentItem $equipment, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        Gate::authorize('update', $project);

        $purchaseOrder->delete();

        activity()
            ->performedOn($purchaseOrder)
            ->causedBy($request->user())
            ->event('deleted')
            ->log('Purchase order deleted');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Purchase order deleted.')]);

        return to_route('projects.equipment.purchase-orders.index', [$project, $equipment]);
    }

    /**
     * Mark the purchase order as delivered.
     */
    public function markDelivered(Request $request, Project $project, EquipmentItem $equipment, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        Gate::authorize('update', $project);

        $purchaseOrder->markDelivered();
        $equipment->markReceived();

        activity()
            ->performedOn($purchaseOrder)
            ->causedBy($request->user())
            ->event('delivered')
            ->log('Purchase order marked as delivered');

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Purchase order marked as delivered.')]);

        return to_route('projects.equipment.purchase-orders.show', [$project, $equipment, $purchaseOrder]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(PurchaseOrder $po): array
    {
        return [
            'id' => $po->id,
            'supplier_quotation_id' => $po->supplier_quotation_id,
            'po_number' => $po->po_number,
            'issued_date' => $po->issued_date?->toDateString(),
            'cost' => $po->cost,
            'expected_delivery_date' => $po->expected_delivery_date?->toDateString(),
            'actual_delivery_date' => $po->actual_delivery_date?->toDateString(),
            'status' => $po->status,
            'created_at' => $po->created_at->toISOString(),
            'supplier_quotation' => $po->relationLoaded('supplierQuotation') && $po->supplierQuotation
                ? $this->presentQuotation($po->supplierQuotation)
                : null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function presentQuotations(EquipmentItem $equipment): array
    {
        return $equipment->supplierQuotations()
            ->latest()
            ->get()
            ->map(fn (SupplierQuotation $q) => $this->presentQuotation($q))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function presentQuotation(SupplierQuotation $quotation): array
    {
        return [
            'id' => $quotation->id,
            'supplier_name' => $quotation->supplier_name,
            'quoted_cost' => $quotation->quoted_cost,
            'status' => $quotation->status,
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
