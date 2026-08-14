<?php

use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MM\GoodsIssueController;
use App\Http\Controllers\MM\LabelCheckController;
use App\Http\Controllers\Vendor\DashboardController as VendorDashboardController;
use App\Http\Controllers\Vendor\PurchaseOrderController as VendorPoController;
use App\Http\Controllers\Vendor\DeliveryNoteController as VendorDnController;
use App\Http\Controllers\Vendor\ProductionOrderController as VendorProductionOrderController;
use App\Http\Controllers\MM\GoodsReceiptController;
use App\Http\Controllers\MM\MaterialController;
use App\Http\Controllers\MM\PurchaseOrderController;
use App\Http\Controllers\MM\StockController;
use App\Http\Controllers\MM\StorageLocationController;
use App\Http\Controllers\MM\SkmController;
use App\Http\Controllers\MM\CustomerController;
use App\Http\Controllers\MM\VendorController;
use App\Http\Controllers\MM\VendorMaterialDeliveryController;
use App\Http\Controllers\MM\BusinessEventLogController;
use App\Http\Controllers\Vendor\MaterialReceiptController;
use App\Http\Controllers\Vendor\StockController as VendorStockController;
use App\Http\Controllers\PP\BomController;
use App\Http\Controllers\PP\MrpController;
use App\Http\Controllers\PP\ProductionOrderController;
use App\Http\Controllers\PP\RoutingController;
use App\Http\Controllers\PP\WorkCenterController;
use App\Http\Controllers\ProfileController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    /** @var User|null $user */
    $user = Auth::user();
    if ($user && $user->loadRoleWithPermissions()->isVendor()) {
        return redirect()->route('vendor.dashboard');
    }
    return redirect()->route('dashboard');
});

// ===================== Vendor Portal =====================
Route::middleware(['auth', 'verified', 'vendor.portal', 'vendor.scope'])
    ->prefix('vendor')
    ->name('vendor.')
    ->group(function () {
        Route::get('/dashboard', [VendorDashboardController::class, 'index'])->name('dashboard');
        Route::get('purchase-orders', [VendorPoController::class, 'index'])->name('purchase-orders.index');
        Route::get('purchase-orders/export-excel', [VendorPoController::class, 'exportExcel'])->name('purchase-orders.export-excel');
        Route::get('purchase-orders/export-pdf', [VendorPoController::class, 'exportPdf'])->name('purchase-orders.export-pdf');
        Route::get('purchase-orders/{purchaseOrder}', [VendorPoController::class, 'show'])->name('purchase-orders.show');
        Route::get('purchase-orders/{purchaseOrder}/print-pdf', [VendorPoController::class, 'printPdf'])->name('purchase-orders.print-pdf');
        Route::get('delivery-notes', [VendorDnController::class, 'index'])->name('delivery-notes.index');
        Route::get('delivery-notes/export-excel', [VendorDnController::class, 'exportListExcel'])->name('delivery-notes.export-excel');
        Route::get('delivery-notes/export-pdf', [VendorDnController::class, 'exportListPdf'])->name('delivery-notes.export-pdf');
        Route::get('delivery-notes/create', [VendorDnController::class, 'create'])->name('delivery-notes.create');
        Route::post('delivery-notes', [VendorDnController::class, 'store'])->name('delivery-notes.store');
        Route::get('delivery-notes/{deliveryNote}', [VendorDnController::class, 'show'])->name('delivery-notes.show');
        Route::get('delivery-notes/{deliveryNote}/print-pdf', [VendorDnController::class, 'printPdf'])->name('delivery-notes.print-pdf');
        Route::get('delivery-notes/{deliveryNote}/export-excel', [VendorDnController::class, 'exportExcel'])->name('delivery-notes.export-excel-single');
        Route::patch('delivery-notes/{deliveryNote}/cancel', [VendorDnController::class, 'cancel'])->name('delivery-notes.cancel');

        // Material Receipts (Kiriman Bahan dari IPPI)
        Route::get('material-receipts', [MaterialReceiptController::class, 'index'])->name('material-receipts.index');
        Route::get('material-receipts/export-excel', [MaterialReceiptController::class, 'exportExcel'])->name('material-receipts.export-excel');
        Route::get('material-receipts/export-pdf', [MaterialReceiptController::class, 'exportPdf'])->name('material-receipts.export-pdf');
        Route::get('material-receipts/{materialReceipt}', [MaterialReceiptController::class, 'show'])->name('material-receipts.show');
        Route::get('material-receipts/{materialReceipt}/print-pdf', [MaterialReceiptController::class, 'printPdf'])->name('material-receipts.print-pdf');
        Route::post('material-receipts/{materialReceipt}/confirm', [MaterialReceiptController::class, 'confirm'])->name('material-receipts.confirm');

        // Vendor Production Orders
        Route::get('production-orders', [VendorProductionOrderController::class, 'index'])->name('production-orders.index');
        Route::get('production-orders/export-excel', [VendorProductionOrderController::class, 'exportExcel'])->name('production-orders.export-excel');
        Route::get('production-orders/export-pdf', [VendorProductionOrderController::class, 'exportPdf'])->name('production-orders.export-pdf');
        Route::get('production-orders/create', [VendorProductionOrderController::class, 'create'])->name('production-orders.create');
        Route::post('production-orders', [VendorProductionOrderController::class, 'store'])->name('production-orders.store');
        Route::get('production-orders/{productionOrder}', [VendorProductionOrderController::class, 'show'])->name('production-orders.show');
        Route::get('production-orders/{productionOrder}/print-pdf', [VendorProductionOrderController::class, 'printPdf'])->name('production-orders.print-pdf');
        Route::post('production-orders/{productionOrder}/release', [VendorProductionOrderController::class, 'release'])->name('production-orders.release');
        Route::post('production-orders/{productionOrder}/report', [VendorProductionOrderController::class, 'report'])->middleware('throttle:20,1')->name('production-orders.report');
        Route::post('production-orders/{productionOrder}/complete', [VendorProductionOrderController::class, 'complete'])->middleware('throttle:10,1')->name('production-orders.complete');
        Route::post('production-orders/{productionOrder}/cancel', [VendorProductionOrderController::class, 'cancel'])->name('production-orders.cancel');

        // Stock Overview
        Route::get('stocks', [VendorStockController::class, 'index'])->name('stocks.index');
        Route::get('stocks/print-pdf', [VendorStockController::class, 'printPdf'])->name('stocks.print-pdf');
        Route::get('stocks/export-excel', [VendorStockController::class, 'exportExcel'])->name('stocks.export-excel');
    });

// Profile routes – accessible to all authenticated users (incl. vendors)
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'no.vendor'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ===================== SAP MM =====================
    Route::prefix('mm')->name('mm.')->middleware('route.permission')->group(function () {

        // Storage Locations
        Route::get('storage-locations/export', [StorageLocationController::class, 'exportExcel'])->name('storage-locations.export');
        Route::get('storage-locations/export-pdf', [StorageLocationController::class, 'exportPdf'])->name('storage-locations.export-pdf');
        Route::get('storage-locations/template', [StorageLocationController::class, 'downloadTemplate'])->name('storage-locations.template');
        Route::post('storage-locations/import', [StorageLocationController::class, 'importExcel'])->name('storage-locations.import');
        Route::resource('storage-locations', StorageLocationController::class);

        // Materials
        Route::get('materials/export', [MaterialController::class, 'exportExcel'])->name('materials.export');
        Route::get('materials/export-pdf', [MaterialController::class, 'exportPdf'])->name('materials.export-pdf');
        Route::get('materials/template', [MaterialController::class, 'downloadTemplate'])->name('materials.template');
        Route::post('materials/import', [MaterialController::class, 'importExcel'])->name('materials.import');
        Route::resource('materials', MaterialController::class);

        // Customers
        Route::get('customers/export', [CustomerController::class, 'exportExcel'])->name('customers.export');
        Route::get('customers/export-pdf', [CustomerController::class, 'exportPdf'])->name('customers.export-pdf');
        Route::get('customers/template', [CustomerController::class, 'downloadTemplate'])->name('customers.template');
        Route::post('customers/import', [CustomerController::class, 'importExcel'])->name('customers.import');
        Route::resource('customers', CustomerController::class);

        // Vendors
        Route::get('vendors/export', [VendorController::class, 'exportExcel'])->name('vendors.export');
        Route::get('vendors/export-pdf', [VendorController::class, 'exportPdf'])->name('vendors.export-pdf');
        Route::get('vendors/template', [VendorController::class, 'downloadTemplate'])->name('vendors.template');
        Route::post('vendors/import', [VendorController::class, 'importExcel'])->name('vendors.import');
        Route::resource('vendors', VendorController::class);

        // Purchase Orders
        Route::get('purchase-orders/export', [PurchaseOrderController::class, 'exportExcel'])->name('purchase-orders.export');
        Route::get('purchase-orders/export-pdf', [PurchaseOrderController::class, 'exportPdf'])->name('purchase-orders.export-pdf');
        Route::get('purchase-orders/import-template', [PurchaseOrderController::class, 'downloadTemplate'])->name('purchase-orders.import-template');
        Route::post('purchase-orders/import-excel', [PurchaseOrderController::class, 'importExcel'])->name('purchase-orders.import-excel');
        Route::post('purchase-orders/import-create', [PurchaseOrderController::class, 'importCreate'])->name('purchase-orders.import-create');
        Route::resource('purchase-orders', PurchaseOrderController::class);
        Route::post('purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
        Route::post('purchase-orders/{purchaseOrder}/close', [PurchaseOrderController::class, 'close'])->name('purchase-orders.close');
        Route::get('purchase-orders/{purchaseOrder}/pdf', [PurchaseOrderController::class, 'printPdf'])->name('purchase-orders.pdf');

        // Goods Receipts
        Route::get('goods-receipts/export', [GoodsReceiptController::class, 'exportExcel'])->name('goods-receipts.export');
        Route::get('goods-receipts/export-pdf', [GoodsReceiptController::class, 'exportPdf'])->name('goods-receipts.export-pdf');
        Route::get('goods-receipts/create-non-po', [GoodsReceiptController::class, 'createNonPo'])->name('goods-receipts.create-non-po');
        Route::post('goods-receipts/store-non-po', [GoodsReceiptController::class, 'storeNonPo'])->name('goods-receipts.store-non-po');
        Route::resource('goods-receipts', GoodsReceiptController::class);

        // Delivery Notes (Surat Jalan dari Vendor)
        Route::get('delivery-notes/export', [\App\Http\Controllers\MM\DeliveryNoteController::class, 'exportExcel'])->name('delivery-notes.export');
        Route::get('delivery-notes', [\App\Http\Controllers\MM\DeliveryNoteController::class, 'index'])->name('delivery-notes.index');
        Route::get('delivery-notes/{deliveryNote}', [\App\Http\Controllers\MM\DeliveryNoteController::class, 'show'])->name('delivery-notes.show');
        Route::put('delivery-notes/{deliveryNote}/qty', [\App\Http\Controllers\MM\DeliveryNoteController::class, 'updateQty'])->name('delivery-notes.update-qty');
        Route::patch('delivery-notes/{deliveryNote}/confirm', [\App\Http\Controllers\MM\DeliveryNoteController::class, 'confirm'])->name('delivery-notes.confirm');
        Route::patch('delivery-notes/{deliveryNote}/receive', [\App\Http\Controllers\MM\DeliveryNoteController::class, 'receive'])->name('delivery-notes.receive');

        // Vendor Material Deliveries (Kiriman Bahan ke Vendor / Subcon)
        Route::get('vendor-deliveries', [VendorMaterialDeliveryController::class, 'index'])->name('vendor-deliveries.index');
        Route::get('vendor-deliveries/create', [VendorMaterialDeliveryController::class, 'create'])->name('vendor-deliveries.create');
        Route::post('vendor-deliveries', [VendorMaterialDeliveryController::class, 'store'])->name('vendor-deliveries.store');
        Route::get('vendor-deliveries/{vendorDelivery}', [VendorMaterialDeliveryController::class, 'show'])->name('vendor-deliveries.show');

        // Vendor Result Deliveries (Hasil Proses dari Vendor / Subcon ke IPPI)


        // Goods Issues
        Route::get('goods-issues/export', [GoodsIssueController::class, 'exportExcel'])->name('goods-issues.export');
        Route::get('goods-issues/export-pdf', [GoodsIssueController::class, 'exportPdf'])->name('goods-issues.export-pdf');
        Route::get('goods-issues/{goodsIssue}/pdf', [GoodsIssueController::class, 'printPdf'])->name('goods-issues.pdf');
        Route::get('goods-issues/{goodsIssue}/excel', [GoodsIssueController::class, 'exportExcelDetail'])->name('goods-issues.excel');
        Route::resource('goods-issues', GoodsIssueController::class);

        // Label Check (Traceability)
        Route::get('label-checks', [LabelCheckController::class, 'index'])->name('label-checks.index');
        Route::post('label-checks', [LabelCheckController::class, 'store'])->name('label-checks.store');
        Route::delete('label-checks/{labelCheck}', [LabelCheckController::class, 'destroy'])->name('label-checks.destroy');

        // Stock Overview
        Route::get('stocks/export', [StockController::class, 'exportExcel'])->name('stocks.export');
        Route::get('stocks/export-pdf', [StockController::class, 'exportPdf'])->name('stocks.export-pdf');
        Route::get('stocks', [StockController::class, 'index'])->name('stocks.index');
        Route::get('stocks/movements', [StockController::class, 'movements'])->name('stocks.movements');

        // SKM
        Route::get('skm', [SkmController::class, 'index'])->name('skm.index');
        Route::get('skm/create', [SkmController::class, 'create'])->name('skm.create');
        Route::post('skm', [SkmController::class, 'store'])->name('skm.store');
        Route::get('skm/demands/template', [SkmController::class, 'demandTemplate'])->name('skm.demands.template');
        Route::post('skm/demands/import', [SkmController::class, 'importDemands'])->name('skm.demands.import');
        Route::delete('skm/demands/clear', [SkmController::class, 'clearDemands'])->name('skm.demands.clear');
        Route::get('skm/{skm}/excel', [SkmController::class, 'exportExcel'])->name('skm.excel');
        Route::get('skm/{skm}/pdf', [SkmController::class, 'exportPdf'])->name('skm.pdf');
        Route::patch('skm/{skm}/status', [SkmController::class, 'updateStatus'])->name('skm.status');
        Route::post('skm/{skm}/generate-po', [SkmController::class, 'generatePo'])->name('skm.generate-po');
        Route::get('skm/{skm}', [SkmController::class, 'show'])->name('skm.show');
        Route::delete('skm/{skm}', [SkmController::class, 'destroy'])->name('skm.destroy');

        // Business Event Logs
        Route::get('business-event-logs', [BusinessEventLogController::class, 'index'])->middleware('role:super_admin,admin')->name('business-event-logs.index');
        Route::get('business-event-logs/export', [BusinessEventLogController::class, 'exportExcel'])->middleware('role:super_admin,admin')->name('business-event-logs.export');
    });

    // ===================== SAP PP =====================
    Route::prefix('pp')->name('pp.')->middleware('route.permission')->group(function () {

        // Work Centers
        Route::get('work-centers/export', [WorkCenterController::class, 'exportExcel'])->name('work-centers.export');
        Route::get('work-centers/export-pdf', [WorkCenterController::class, 'exportPdf'])->name('work-centers.export-pdf');
        Route::get('work-centers/import-template', [WorkCenterController::class, 'downloadTemplate'])->name('work-centers.import-template');
        Route::post('work-centers/import', [WorkCenterController::class, 'import'])->name('work-centers.import');
        Route::resource('work-centers', WorkCenterController::class);

        // BOMs
        Route::get('boms/export', [BomController::class, 'exportExcel'])->name('boms.export');
        Route::get('boms/export-pdf', [BomController::class, 'exportPdf'])->name('boms.export-pdf');
        Route::get('boms/import-template', [BomController::class, 'downloadTemplate'])->name('boms.import-template');
        Route::post('boms/import', [BomController::class, 'import'])->name('boms.import');
        Route::resource('boms', BomController::class);

        // Routings
        Route::get('routings/export', [RoutingController::class, 'exportExcel'])->name('routings.export');
        Route::get('routings/export-pdf', [RoutingController::class, 'exportPdf'])->name('routings.export-pdf');
        Route::get('routings/import-template', [RoutingController::class, 'downloadTemplate'])->name('routings.import-template');
        Route::post('routings/import', [RoutingController::class, 'import'])->name('routings.import');
        Route::resource('routings', RoutingController::class);

        // Production Orders
        Route::get('production-orders/export-pdf', [ProductionOrderController::class, 'exportPdf'])->name('production-orders.export-pdf');
        Route::get('production-orders/import-template', [ProductionOrderController::class, 'importTemplate'])->name('production-orders.import-template');
        Route::post('production-orders/import-excel', [ProductionOrderController::class, 'importExcel'])->name('production-orders.import-excel');
        Route::get('production-orders/{productionOrder}/print', [ProductionOrderController::class, 'printLabel'])->name('production-orders.print');
        Route::resource('production-orders', ProductionOrderController::class);
        Route::post('production-orders/{productionOrder}/release', [ProductionOrderController::class, 'release'])->name('production-orders.release');
        Route::post('production-orders/bulk-release', [ProductionOrderController::class, 'bulkRelease'])->name('production-orders.bulk-release');
        Route::post('production-orders/{productionOrder}/goods-issue', [ProductionOrderController::class, 'goodsIssue'])->name('production-orders.goods-issue');
        Route::post('production-orders/{productionOrder}/confirm', [ProductionOrderController::class, 'confirm'])->name('production-orders.confirm');

        // MRP
        Route::get('mrp', [MrpController::class, 'index'])->name('mrp.index');
        Route::get('mrp/export-pdf', [MrpController::class, 'exportListPdf'])->name('mrp.export-pdf');
        Route::post('mrp/run', [MrpController::class, 'run'])->name('mrp.run');
        Route::get('mrp/demands/template', [MrpController::class, 'downloadDemandTemplate'])->name('mrp.demands.template');
        Route::post('mrp/demands/import', [MrpController::class, 'importDemands'])->name('mrp.demands.import');
        Route::delete('mrp/demands/clear', [MrpController::class, 'clearDemands'])->name('mrp.demands.clear');
        Route::delete('mrp/demands/{mrpDemand}', [MrpController::class, 'destroyDemand'])->name('mrp.demands.destroy');
        Route::get('mrp/{mrpRun}', [MrpController::class, 'show'])->name('mrp.show');
        Route::delete('mrp/{mrpRun}', [MrpController::class, 'destroy'])->name('mrp.destroy');
        Route::get('mrp/{mrpRun}/excel', [MrpController::class, 'exportExcel'])->name('mrp.excel');
        Route::get('mrp/{mrpRun}/pdf', [MrpController::class, 'exportPdf'])->name('mrp.pdf');
    });
});

// ===================== Admin =====================
Route::middleware(['auth', 'verified', 'role:super_admin,admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('roles', RoleController::class)->except(['show']);
    Route::resource('users', AdminUserController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    Route::get('users/{user}/permissions', [AdminUserController::class, 'editPermissions'])->name('users.permissions');
    Route::put('users/{user}/permissions', [AdminUserController::class, 'updatePermissions'])->name('users.permissions.update');
});

require __DIR__.'/auth.php';
