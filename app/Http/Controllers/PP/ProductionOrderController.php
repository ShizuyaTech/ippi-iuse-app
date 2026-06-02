<?php

namespace App\Http\Controllers\PP;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use App\Models\GoodsIssue;
use App\Models\GoodsReceipt;
use App\Models\Material;
use App\Models\ProductionOrder;
use App\Models\Routing;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StorageLocation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductionOrder::with('material');
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('order_number', 'like', "%{$request->search}%")
                  ->orWhereHas('material', fn($m) => $m->where('name', 'like', "%{$request->search}%"));
            });
        }
        if ($request->date_from) $query->whereDate('planned_start_date', '>=', $request->date_from);
        if ($request->date_to)   $query->whereDate('planned_start_date', '<=', $request->date_to);
        $orders = $query->latest()->paginate(20)->withQueryString();
        return view('pp.production-orders.index', compact('orders'));
    }

    public function create()
    {
        $materials = Material::where('is_active', true)
            ->whereIn('type', ['FP', 'WIP'])
            ->orderBy('code')->get();
        $boms      = Bom::with('material')->where('status', 'active')->get();
        $routings  = Routing::with('material')->where('status', 'active')->get();
        return view('pp.production-orders.create', compact('materials', 'boms', 'routings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'planned_start_date'              => 'required|date',
            'planned_end_date'                => 'required|date|after_or_equal:planned_start_date',
            'orders'                          => 'required|array|min:1',
            'orders.*.material_id'            => 'required|exists:materials,id',
            'orders.*.bom_id'                 => 'nullable|exists:boms,id',
            'orders.*.routing_id'             => 'nullable|exists:routings,id',
            'orders.*.quantity_planned'       => 'required|numeric|min:0.001',
            'orders.*.notes'                  => 'nullable|string',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->orders as $row) {
                $order = ProductionOrder::create([
                    'order_number'       => ProductionOrder::generateNumber(),
                    'material_id'        => $row['material_id'],
                    'bom_id'             => $row['bom_id'] ?: null,
                    'routing_id'         => $row['routing_id'] ?: null,
                    'quantity_planned'   => $row['quantity_planned'],
                    'quantity_produced'  => 0,
                    'planned_start_date' => $request->planned_start_date,
                    'planned_end_date'   => $request->planned_end_date,
                    'status'             => 'created',
                    'notes'              => $row['notes'] ?? null,
                    'created_by'         => auth()->id(),
                ]);

                if (!empty($row['bom_id'])) {
                    $bom = Bom::with('items')->find($row['bom_id']);
                    $multiplier = $row['quantity_planned'] / $bom->base_quantity;
                    foreach ($bom->items as $item) {
                        $order->components()->create([
                            'material_id'       => $item->material_id,
                            'quantity_required' => $item->quantity * $multiplier,
                            'quantity_issued'   => 0,
                        ]);
                    }
                }
            }
        });

        $count = count($request->orders);
        return redirect()->route('pp.production-orders.index')->with('success', "{$count} Production Order berhasil dibuat.");
    }

    public function show(ProductionOrder $productionOrder)
    {
        $productionOrder->load('material', 'bom', 'routing.operations.workCenter', 'components.material', 'components.storageLocation', 'createdBy');
        $locations = StorageLocation::orderBy('code')->get();

        // Confirm destination: cari gudang berdasarkan material_type dari material yang diproduksi
        // (tidak hardcode kode gudang – ambil dari kolom material_type di storage_locations)
        $warehouseByType  = $locations->whereNotNull('material_type')->groupBy('material_type')->map->first();
        $defaultFgLocation = $warehouseByType->get($productionOrder->material->type)
                             ?? $locations->last();

        // Stock available per component – lookup by material_type
        $componentStocks = [];
        foreach ($productionOrder->components as $comp) {
            $location = $warehouseByType->get($comp->material->type);
            $stock    = $location
                ? Stock::where('material_id', $comp->material_id)->where('storage_location_id', $location->id)->first()
                : null;
            $componentStocks[$comp->id] = [
                'location_code' => $location?->code ?? '-',
                'available'     => $stock ? (float) $stock->quantity : 0,
            ];
        }

        // Max confirmable qty = minimum ratio (issued/required) × planned across all components
        $maxConfirmQty = (float) $productionOrder->quantity_planned;
        foreach ($productionOrder->components as $comp) {
            if ((float) $comp->quantity_required > 0) {
                $ratio         = (float) $comp->quantity_issued / (float) $comp->quantity_required;
                $possible      = round($ratio * (float) $productionOrder->quantity_planned, 3);
                $maxConfirmQty = min($maxConfirmQty, $possible);
            }
        }
        $maxConfirmQty = max(0, $maxConfirmQty);

        return view('pp.production-orders.show', compact(
            'productionOrder', 'locations', 'defaultFgLocation', 'componentStocks', 'maxConfirmQty'
        ));
    }

    public function edit(ProductionOrder $productionOrder)
    {
        if (!in_array($productionOrder->status, ['created'])) {
            return back()->with('error', 'Production Order tidak dapat diedit.');
        }
        $materials = Material::where('is_active', true)->orderBy('code')->get();
        $boms      = Bom::with('material')->where('status', 'active')->get();
        $routings  = Routing::with('material')->where('status', 'active')->get();
        return view('pp.production-orders.edit', compact('productionOrder', 'materials', 'boms', 'routings'));
    }

    public function update(Request $request, ProductionOrder $productionOrder)
    {
        if ($productionOrder->status !== 'created') {
            return back()->with('error', 'Production Order tidak dapat diedit.');
        }
        $request->validate([
            'material_id'        => 'required|exists:materials,id',
            'quantity_planned'   => 'required|numeric|min:0.001',
            'planned_start_date' => 'required|date',
            'planned_end_date'   => 'required|date|after_or_equal:planned_start_date',
        ]);
        $productionOrder->update($request->only('material_id', 'bom_id', 'routing_id', 'quantity_planned', 'planned_start_date', 'planned_end_date', 'notes'));
        return redirect()->route('pp.production-orders.show', $productionOrder)->with('success', 'Production Order diperbarui.');
    }

    public function release(ProductionOrder $productionOrder)
    {
        if ($productionOrder->status !== 'created') {
            return back()->with('error', 'Hanya Production Order Created yang dapat di-release.');
        }
        $productionOrder->update(['status' => 'released', 'actual_start_date' => now()]);
        return back()->with('success', 'Production Order berhasil di-release.');
    }

    public function bulkRelease(Request $request)
    {
        $request->validate(['ids' => 'required|array|min:1', 'ids.*' => 'exists:production_orders,id']);

        $orders = ProductionOrder::whereIn('id', $request->ids)->where('status', 'created')->get();
        if ($orders->isEmpty()) {
            return back()->with('error', 'Tidak ada Production Order berstatus Created yang dipilih.');
        }

        $now = now();
        foreach ($orders as $order) {
            $order->update(['status' => 'released', 'actual_start_date' => $now]);
        }

        return back()->with('success', $orders->count() . ' Production Order berhasil di-release.');
    }

    public function goodsIssue(Request $request, ProductionOrder $productionOrder)
    {
        if (!in_array($productionOrder->status, ['released', 'in_progress'])) {
            return back()->with('error', 'Production Order harus berstatus Released atau In Progress.');
        }

        $request->validate([
            'quantities'   => 'required|array',
            'quantities.*' => 'nullable|numeric|min:0',
        ]);

        $productionOrder->load('components.material');

        // Lookup gudang berdasarkan material_type (dinamis, tidak hardcode kode gudang)
        $warehouseByType = StorageLocation::whereNotNull('material_type')
            ->get()->groupBy('material_type')->map->first();

        // Pre-validate each submitted qty
        $validationErrors = [];
        foreach ($productionOrder->components as $component) {
            $inputQty = (float) ($request->quantities[$component->id] ?? 0);
            if ($inputQty <= 0) continue;

            $remaining = round((float) $component->quantity_required - (float) $component->quantity_issued, 3);
            if ($inputQty > $remaining + 0.001) {
                $validationErrors[] = "{$component->material->code}: qty input ({$inputQty}) melebihi sisa yang dibutuhkan (" . number_format($remaining, 3) . ")";
                continue;
            }

            $location = $warehouseByType->get($component->material->type);
            if (!$location) continue;

            $stock     = Stock::where('material_id', $component->material_id)->where('storage_location_id', $location->id)->first();
            $available = $stock ? (float) $stock->quantity : 0;
            if ($inputQty > $available + 0.001) {
                $validationErrors[] = "{$component->material->code}: stok {$location->code} tidak cukup (tersedia: " . number_format($available, 3) . ", diminta: " . number_format($inputQty, 3) . ")";
            }
        }

        if (!empty($validationErrors)) {
            return back()->withErrors(['quantities' => $validationErrors])->withInput();
        }

        $hasAny = collect($request->quantities)->filter(fn($v) => (float) $v > 0)->isNotEmpty();
        if (!$hasAny) {
            return back()->with('error', 'Tidak ada qty yang diinput. Isi minimal satu komponen untuk di-GI.');
        }

        DB::transaction(function () use ($request, $productionOrder, $warehouseByType) {
            $rmLocation = $warehouseByType->get('RM') ?? $warehouseByType->first();

            $gi = GoodsIssue::create([
                'gi_number'           => GoodsIssue::generateNumber(),
                'reference_type'      => 'production_order',
                'reference_id'        => $productionOrder->id,
                'issue_date'          => now()->toDateString(),
                'storage_location_id' => $rmLocation?->id,
                'status'              => 'posted',
                'notes'               => 'GI for Production Order ' . $productionOrder->order_number,
                'created_by'          => auth()->id(),
            ]);

            foreach ($productionOrder->components as $component) {
                $inputQty = (float) ($request->quantities[$component->id] ?? 0);
                if ($inputQty <= 0) continue;

                $location = $warehouseByType->get($component->material->type);
                if (!$location) continue;

                $gi->items()->create(['material_id' => $component->material_id, 'quantity_issued' => $inputQty]);
                $component->update([
                    'quantity_issued'     => round((float) $component->quantity_issued + $inputQty, 3),
                    'storage_location_id' => $location->id,
                ]);

                $stock  = Stock::where('material_id', $component->material_id)->where('storage_location_id', $location->id)->first();
                $newQty = round((float) $stock->quantity - $inputQty, 3);
                $stock->update(['quantity' => $newQty]);

                StockMovement::create([
                    'material_id'         => $component->material_id,
                    'storage_location_id' => $location->id,
                    'movement_type'       => 'GI',
                    'quantity'            => $inputQty,
                    'quantity_after'      => $newQty,
                    'reference_document'  => $gi->gi_number,
                    'movement_date'       => now()->toDateString(),
                    'created_by'          => auth()->id(),
                ]);
            }

            $productionOrder->update(['status' => 'in_progress']);
        });

        return back()->with('success', 'Goods Issue to Production berhasil diposting.');
    }

    public function confirm(Request $request, ProductionOrder $productionOrder)
    {
        $request->validate([
            'quantity_ok'         => 'required|numeric|min:0',
            'quantity_ng'         => 'required|numeric|min:0',
            'storage_location_id' => 'required|exists:storage_locations,id',
        ]);

        $totalConfirmed = $request->quantity_ok + $request->quantity_ng;
        if ($totalConfirmed <= 0) {
            return back()->withErrors(['quantity_ok' => 'Total Qty OK + Qty NG harus lebih dari 0.'])->withInput();
        }

        if (!in_array($productionOrder->status, ['released', 'in_progress'])) {
            return back()->with('error', 'Production Order harus berstatus Released atau In Progress.');
        }

        $productionOrder->load('components.material');

        // Validasi: cek material komponen yang sudah di-GI cukup untuk qty yang dikonfirmasi
        if ($productionOrder->components->isNotEmpty()) {
            $ratio = $productionOrder->quantity_planned > 0
                ? $totalConfirmed / $productionOrder->quantity_planned
                : 1;

            $kurang = [];
            foreach ($productionOrder->components as $component) {
                $requiredForConfirm = round($component->quantity_required * $ratio, 3);
                $issued = (float) $component->quantity_issued;

                if ($issued < $requiredForConfirm - 0.001) {
                    $kurang[] = sprintf(
                        '%s (dibutuhkan: %s %s, sudah GI: %s %s)',
                        $component->material->name,
                        number_format($requiredForConfirm, 3),
                        $component->material->unit_of_measure,
                        number_format($issued, 3),
                        $component->material->unit_of_measure
                    );
                }
            }

            if (!empty($kurang)) {
                $errorBag = ['quantity_ok' => 'Material komponen tidak mencukupi untuk konfirmasi ' . number_format($totalConfirmed, 3) . ' unit. Lakukan Goods Issue terlebih dahulu:'];
                foreach ($kurang as $i => $item) {
                    $errorBag["comp_{$i}"] = $item;
                }
                return back()->withErrors($errorBag)->withInput();
            }
        }

        DB::transaction(function () use ($request, $productionOrder, $totalConfirmed) {
            // 1. Posting GR ke stok FG (hanya qty_ok)
            if ($request->quantity_ok > 0) {
                $stock = Stock::firstOrCreate(
                    ['material_id' => $productionOrder->material_id, 'storage_location_id' => $request->storage_location_id],
                    ['quantity' => 0]
                );
                $newQty = $stock->quantity + $request->quantity_ok;
                $stock->update(['quantity' => $newQty]);

                StockMovement::create([
                    'material_id'         => $productionOrder->material_id,
                    'storage_location_id' => $request->storage_location_id,
                    'movement_type'       => 'GR',
                    'quantity'            => $request->quantity_ok,
                    'quantity_after'      => $newQty,
                    'reference_document'  => $productionOrder->order_number,
                    'movement_date'       => now()->toDateString(),
                    'created_by'          => auth()->id(),
                ]);
            }

            // 2. Kembalikan sisa material ke stok jika aktual < planned
            // qtyReturn = qty_issued - qty_yang_benar_dipakai
            // qty_yang_benar_dipakai = quantity_required * ratio (berdasarkan BOM, bukan issued)
            // Ini benar menangani kasus GI parsial: jika issued < required*ratio, return = 0
            $actualRatio = $productionOrder->quantity_planned > 0
                ? $totalConfirmed / $productionOrder->quantity_planned
                : 1;

            if ($actualRatio < 0.9999) {
                foreach ($productionOrder->components as $component) {
                    if ($component->quantity_issued <= 0 || !$component->storage_location_id) continue;

                    $qtyActuallyUsed = round($component->quantity_required * $actualRatio, 3);
                    $qtyReturn = round((float) $component->quantity_issued - $qtyActuallyUsed, 3);
                    if ($qtyReturn < 0.001) continue;

                    $compStock = Stock::firstOrCreate(
                        ['material_id' => $component->material_id, 'storage_location_id' => $component->storage_location_id],
                        ['quantity' => 0]
                    );
                    $newCompQty = $compStock->quantity + $qtyReturn;
                    $compStock->update(['quantity' => $newCompQty]);

                    StockMovement::create([
                        'material_id'         => $component->material_id,
                        'storage_location_id' => $component->storage_location_id,
                        'movement_type'       => 'GR',
                        'quantity'            => $qtyReturn,
                        'quantity_after'      => $newCompQty,
                        'reference_document'  => $productionOrder->order_number . '/RET',
                        'movement_date'       => now()->toDateString(),
                        'created_by'          => auth()->id(),
                    ]);
                }
            }

            // 3. Update production order
            $productionOrder->update([
                'quantity_produced' => $productionOrder->quantity_produced + $totalConfirmed,
                'quantity_ok'       => $productionOrder->quantity_ok + $request->quantity_ok,
                'quantity_ng'       => $productionOrder->quantity_ng + $request->quantity_ng,
                'status'            => 'completed',
                'actual_end_date'   => now(),
            ]);
        });

        return back()->with('success', 'Konfirmasi produksi berhasil. Stok produk jadi telah diperbarui.');
    }

    public function printLabel(ProductionOrder $productionOrder)
    {
        $productionOrder->load('material', 'components.material');
        $generator = new \Picqer\Barcode\BarcodeGeneratorSVG();
        $barcode   = $generator->getBarcode($productionOrder->order_number, $generator::TYPE_CODE_128, 1, 40);
        return view('pp.production-orders.print', compact('productionOrder', 'barcode'));
    }

    public function destroy(ProductionOrder $productionOrder)
    {
        if ($productionOrder->status !== 'created') {
            return back()->with('error', 'Hanya Production Order Created yang dapat dihapus.');
        }
        $productionOrder->delete();
        return redirect()->route('pp.production-orders.index')->with('success', 'Production Order berhasil dihapus.');
    }

    public function exportPdf(Request $request)
    {
        $query = ProductionOrder::with('material');
        if ($request->status)    $query->where('status', $request->status);
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('order_number', 'like', "%{$request->search}%")
                  ->orWhereHas('material', fn($m) => $m->where('name', 'like', "%{$request->search}%"));
            });
        }
        if ($request->date_from) $query->whereDate('planned_start_date', '>=', $request->date_from);
        if ($request->date_to)   $query->whereDate('planned_start_date', '<=', $request->date_to);
        $orders = $query->latest()->get();

        $filters = $request->only(['search', 'status', 'date_from', 'date_to']);

        $pdf = Pdf::loadView('pp.production-orders.pdf-list', compact('orders', 'filters'))
            ->setPaper('a4', 'landscape');
        return $pdf->stream('production_orders_' . date('Ymd') . '.pdf');
    }
}
