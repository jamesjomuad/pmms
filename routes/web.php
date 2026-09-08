<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ChangelogController;
use App\Http\Controllers\ChangeOrderController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliverableController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\EquipmentInspectionController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PunchListItemController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\RfiController;
use App\Http\Controllers\ShopDrawingController;
use App\Http\Controllers\SubmittalController;
use App\Http\Controllers\SupplierQuotationController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::patch('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

    Route::post('projects/{project}/deliverables', [DeliverableController::class, 'store'])->name('projects.deliverables.store');
    Route::delete('projects/{project}/deliverables/{mediaId}', [DeliverableController::class, 'destroy'])->name('projects.deliverables.destroy');

    Route::get('projects/{project}/submittals', [SubmittalController::class, 'index'])->name('projects.submittals.index');
    Route::get('projects/{project}/submittals/create', [SubmittalController::class, 'create'])->name('projects.submittals.create');
    Route::post('projects/{project}/submittals', [SubmittalController::class, 'store'])->name('projects.submittals.store');
    Route::get('projects/{project}/submittals/{submittal}', [SubmittalController::class, 'show'])->name('projects.submittals.show');
    Route::get('projects/{project}/submittals/{submittal}/edit', [SubmittalController::class, 'edit'])->name('projects.submittals.edit');
    Route::patch('projects/{project}/submittals/{submittal}', [SubmittalController::class, 'update'])->name('projects.submittals.update');
    Route::delete('projects/{project}/submittals/{submittal}', [SubmittalController::class, 'destroy'])->name('projects.submittals.destroy');
    Route::post('projects/{project}/submittals/{submittal}/submit', [SubmittalController::class, 'submit'])->name('projects.submittals.submit');
    Route::post('projects/{project}/submittals/{submittal}/approve', [SubmittalController::class, 'approve'])->name('projects.submittals.approve');
    Route::post('projects/{project}/submittals/{submittal}/reject', [SubmittalController::class, 'reject'])->name('projects.submittals.reject');
    Route::post('projects/{project}/submittals/{submittal}/request-revision', [SubmittalController::class, 'requestRevision'])->name('projects.submittals.request-revision');
    Route::post('projects/{project}/submittals/{submittal}/new-revision', [SubmittalController::class, 'newRevision'])->name('projects.submittals.new-revision');

    Route::get('projects/{project}/rfis', [RfiController::class, 'index'])->name('projects.rfis.index');
    Route::get('projects/{project}/rfis/create', [RfiController::class, 'create'])->name('projects.rfis.create');
    Route::post('projects/{project}/rfis', [RfiController::class, 'store'])->name('projects.rfis.store');
    Route::get('projects/{project}/rfis/{rfi}', [RfiController::class, 'show'])->name('projects.rfis.show');
    Route::get('projects/{project}/rfis/{rfi}/edit', [RfiController::class, 'edit'])->name('projects.rfis.edit');
    Route::patch('projects/{project}/rfis/{rfi}', [RfiController::class, 'update'])->name('projects.rfis.update');
    Route::delete('projects/{project}/rfis/{rfi}', [RfiController::class, 'destroy'])->name('projects.rfis.destroy');
    Route::post('projects/{project}/rfis/{rfi}/submit', [RfiController::class, 'submit'])->name('projects.rfis.submit');
    Route::post('projects/{project}/rfis/{rfi}/respond', [RfiController::class, 'respond'])->name('projects.rfis.respond');

    Route::get('shop-drawings', [ShopDrawingController::class, 'all'])->name('shop-drawings.index');
    Route::get('projects/{project}/shop-drawings', [ShopDrawingController::class, 'index'])->name('projects.shop-drawings.index');
    Route::get('projects/{project}/shop-drawings/create', [ShopDrawingController::class, 'create'])->name('projects.shop-drawings.create');
    Route::post('projects/{project}/shop-drawings', [ShopDrawingController::class, 'store'])->name('projects.shop-drawings.store');
    Route::get('projects/{project}/shop-drawings/{shopDrawing}', [ShopDrawingController::class, 'show'])->name('projects.shop-drawings.show');
    Route::get('projects/{project}/shop-drawings/{shopDrawing}/edit', [ShopDrawingController::class, 'edit'])->name('projects.shop-drawings.edit');
    Route::patch('projects/{project}/shop-drawings/{shopDrawing}', [ShopDrawingController::class, 'update'])->name('projects.shop-drawings.update');
    Route::delete('projects/{project}/shop-drawings/{shopDrawing}', [ShopDrawingController::class, 'destroy'])->name('projects.shop-drawings.destroy');
    Route::post('projects/{project}/shop-drawings/{shopDrawing}/submit', [ShopDrawingController::class, 'submit'])->name('projects.shop-drawings.submit');
    Route::post('projects/{project}/shop-drawings/{shopDrawing}/approve', [ShopDrawingController::class, 'approve'])->name('projects.shop-drawings.approve');
    Route::post('projects/{project}/shop-drawings/{shopDrawing}/request-revision', [ShopDrawingController::class, 'requestRevision'])->name('projects.shop-drawings.request-revision');
    Route::post('projects/{project}/shop-drawings/{shopDrawing}/new-revision', [ShopDrawingController::class, 'newRevision'])->name('projects.shop-drawings.new-revision');

    Route::get('projects/{project}/equipment', [EquipmentController::class, 'index'])->name('projects.equipment.index');
    Route::get('projects/{project}/equipment/create', [EquipmentController::class, 'create'])->name('projects.equipment.create');
    Route::post('projects/{project}/equipment', [EquipmentController::class, 'store'])->name('projects.equipment.store');
    Route::get('projects/{project}/equipment/{equipment}', [EquipmentController::class, 'show'])->name('projects.equipment.show');
    Route::get('projects/{project}/equipment/{equipment}/edit', [EquipmentController::class, 'edit'])->name('projects.equipment.edit');
    Route::patch('projects/{project}/equipment/{equipment}', [EquipmentController::class, 'update'])->name('projects.equipment.update');
    Route::delete('projects/{project}/equipment/{equipment}', [EquipmentController::class, 'destroy'])->name('projects.equipment.destroy');
    Route::post('projects/{project}/equipment/{equipment}/receive', [EquipmentController::class, 'receive'])->name('projects.equipment.receive');

    Route::get('projects/{project}/equipment/{equipment}/quotations', [SupplierQuotationController::class, 'index'])->name('projects.equipment.quotations.index');
    Route::get('projects/{project}/equipment/{equipment}/quotations/create', [SupplierQuotationController::class, 'create'])->name('projects.equipment.quotations.create');
    Route::post('projects/{project}/equipment/{equipment}/quotations', [SupplierQuotationController::class, 'store'])->name('projects.equipment.quotations.store');
    Route::get('projects/{project}/equipment/{equipment}/quotations/{quotation}', [SupplierQuotationController::class, 'show'])->name('projects.equipment.quotations.show');
    Route::get('projects/{project}/equipment/{equipment}/quotations/{quotation}/edit', [SupplierQuotationController::class, 'edit'])->name('projects.equipment.quotations.edit');
    Route::patch('projects/{project}/equipment/{equipment}/quotations/{quotation}', [SupplierQuotationController::class, 'update'])->name('projects.equipment.quotations.update');
    Route::delete('projects/{project}/equipment/{equipment}/quotations/{quotation}', [SupplierQuotationController::class, 'destroy'])->name('projects.equipment.quotations.destroy');
    Route::post('projects/{project}/equipment/{equipment}/quotations/{quotation}/select', [SupplierQuotationController::class, 'select'])->name('projects.equipment.quotations.select');
    Route::post('projects/{project}/equipment/{equipment}/quotations/{quotation}/decline', [SupplierQuotationController::class, 'decline'])->name('projects.equipment.quotations.decline');

    Route::get('projects/{project}/equipment/{equipment}/purchase-orders', [PurchaseOrderController::class, 'index'])->name('projects.equipment.purchase-orders.index');
    Route::get('projects/{project}/equipment/{equipment}/purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('projects.equipment.purchase-orders.create');
    Route::post('projects/{project}/equipment/{equipment}/purchase-orders', [PurchaseOrderController::class, 'store'])->name('projects.equipment.purchase-orders.store');
    Route::get('projects/{project}/equipment/{equipment}/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('projects.equipment.purchase-orders.show');
    Route::get('projects/{project}/equipment/{equipment}/purchase-orders/{purchaseOrder}/edit', [PurchaseOrderController::class, 'edit'])->name('projects.equipment.purchase-orders.edit');
    Route::patch('projects/{project}/equipment/{equipment}/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('projects.equipment.purchase-orders.update');
    Route::delete('projects/{project}/equipment/{equipment}/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->name('projects.equipment.purchase-orders.destroy');
    Route::post('projects/{project}/equipment/{equipment}/purchase-orders/{purchaseOrder}/deliver', [PurchaseOrderController::class, 'markDelivered'])->name('projects.equipment.purchase-orders.deliver');

    Route::get('projects/{project}/equipment/{equipment}/inspections', [EquipmentInspectionController::class, 'index'])->name('projects.equipment.inspections.index');
    Route::get('projects/{project}/equipment/{equipment}/inspections/create', [EquipmentInspectionController::class, 'create'])->name('projects.equipment.inspections.create');
    Route::post('projects/{project}/equipment/{equipment}/inspections', [EquipmentInspectionController::class, 'store'])->name('projects.equipment.inspections.store');
    Route::get('projects/{project}/equipment/{equipment}/inspections/{inspection}', [EquipmentInspectionController::class, 'show'])->name('projects.equipment.inspections.show');
    Route::get('projects/{project}/equipment/{equipment}/inspections/{inspection}/edit', [EquipmentInspectionController::class, 'edit'])->name('projects.equipment.inspections.edit');
    Route::patch('projects/{project}/equipment/{equipment}/inspections/{inspection}', [EquipmentInspectionController::class, 'update'])->name('projects.equipment.inspections.update');
    Route::delete('projects/{project}/equipment/{equipment}/inspections/{inspection}', [EquipmentInspectionController::class, 'destroy'])->name('projects.equipment.inspections.destroy');

    Route::get('projects/{project}/punch-list', [PunchListItemController::class, 'index'])->name('projects.punch-list.index');
    Route::get('projects/{project}/punch-list/create', [PunchListItemController::class, 'create'])->name('projects.punch-list.create');
    Route::post('projects/{project}/punch-list', [PunchListItemController::class, 'store'])->name('projects.punch-list.store');
    Route::get('projects/{project}/punch-list/{punchListItem}', [PunchListItemController::class, 'show'])->name('projects.punch-list.show');
    Route::get('projects/{project}/punch-list/{punchListItem}/edit', [PunchListItemController::class, 'edit'])->name('projects.punch-list.edit');
    Route::patch('projects/{project}/punch-list/{punchListItem}', [PunchListItemController::class, 'update'])->name('projects.punch-list.update');
    Route::delete('projects/{project}/punch-list/{punchListItem}', [PunchListItemController::class, 'destroy'])->name('projects.punch-list.destroy');
    Route::post('projects/{project}/punch-list/{punchListItem}/resolve', [PunchListItemController::class, 'resolve'])->name('projects.punch-list.resolve');

    Route::get('projects/{project}/change-orders', [ChangeOrderController::class, 'index'])->name('projects.change-orders.index');
    Route::get('projects/{project}/change-orders/create', [ChangeOrderController::class, 'create'])->name('projects.change-orders.create');
    Route::post('projects/{project}/change-orders', [ChangeOrderController::class, 'store'])->name('projects.change-orders.store');
    Route::get('projects/{project}/change-orders/{changeOrder}', [ChangeOrderController::class, 'show'])->name('projects.change-orders.show');
    Route::get('projects/{project}/change-orders/{changeOrder}/edit', [ChangeOrderController::class, 'edit'])->name('projects.change-orders.edit');
    Route::patch('projects/{project}/change-orders/{changeOrder}', [ChangeOrderController::class, 'update'])->name('projects.change-orders.update');
    Route::delete('projects/{project}/change-orders/{changeOrder}', [ChangeOrderController::class, 'destroy'])->name('projects.change-orders.destroy');
    Route::post('projects/{project}/change-orders/{changeOrder}/submit', [ChangeOrderController::class, 'submit'])->name('projects.change-orders.submit');
    Route::post('projects/{project}/change-orders/{changeOrder}/approve', [ChangeOrderController::class, 'approve'])->name('projects.change-orders.approve');
    Route::post('projects/{project}/change-orders/{changeOrder}/reject', [ChangeOrderController::class, 'reject'])->name('projects.change-orders.reject');

    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');
    Route::get('changelog', [ChangelogController::class, 'index'])->name('changelog.index');
});

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
