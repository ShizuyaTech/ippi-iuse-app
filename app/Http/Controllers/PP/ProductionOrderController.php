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
use App\Services\ExcelService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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
            'orders.*.order_number'           => 'nullable|string|max:50|distinct',
            'orders.*.material_id'            => 'required|exists:materials,id',
            'orders.*.quantity_planned'       => 'required|numeric|min:0.001',
            'orders.*.notes'                  => 'nullable|string',
        ]);

        // Validate uniqueness of each order_number against the database
        foreach ($request->orders as $idx => $row) {
            if (ProductionOrder::where('order_number', $row['order_number'])->exists()) {
                return back()->withErrors(["orders.{$idx}.order_number" => "Nomor order '{$row['order_number']}' sudah digunakan."])->withInput();
            }
        }

        DB::transaction(function () use ($request) {
            foreach ($request->orders as $row) {
                // Auto-find active BOM for the material
                $bom = Bom::with('items')
                    ->where('material_id', $row['material_id'])
                    ->where('status', 'active')
                    ->orderByDesc('valid_from')
                    ->first();

                // Auto-find active Routing for the material
                $routing = Routing::where('material_id', $row['material_id'])
                    ->where('status', 'active')
                    ->first();

                $order = ProductionOrder::create([
                    'order_number'       => $row['order_number'],
                    'material_id'        => $row['material_id'],
                    'bom_id'             => $bom?->id,
                    'routing_id'         => $routing?->id,
                    'quantity_planned'   => $row['quantity_planned'],
                    'quantity_produced'  => 0,
                    'planned_start_date' => $request->planned_start_date,
                    'planned_end_date'   => $request->planned_end_date,
                    'status'             => 'created',
                    'notes'              => $row['notes'] ?? null,
                    'created_by'         => Auth::id(),
                ]);

                if ($bom) {
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

    public function importTemplate()
    {
        $spreadsheet = new Spreadsheet();

        // ── Sheet 1: Template ─────────────────────────────────────────
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Template PO Produksi');

        $instruction = 'TEMPLATE IMPORT PRODUCTION ORDER — A: No. Order * | B: Kode Material * | C: Qty Planned * | D: Catatan (opsional). Lihat sheet "Daftar Material" untuk kode material yang valid.';
        $sheet->setCellValue('A1', $instruction);
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['italic' => true, 'size' => 9, 'color' => ['argb' => 'FF92400E']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFFF8DC']],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(18);

        $headers = ['No. Order *', 'Kode Material *', 'Qty Planned *', 'Catatan'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . '2', $h);
        }
        $sheet->getStyle('A2:D2')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A8A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Sample rows
        $samples = [
            ['PO-2026-00001', 'FP-001', 100, ''],
            ['PO-2026-00002', 'FP-002', 50, 'Prioritas'],
        ];
        foreach ($samples as $i => $row) {
            $r = $i + 3;
            foreach ($row as $j => $val) {
                $sheet->setCellValue(chr(65 + $j) . $r, $val);
            }
            $sheet->getStyle("A{$r}:D{$r}")->applyFromArray([
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF3F6FA']],
                'font'    => ['color' => ['argb' => 'FF9CA3AF'], 'italic' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE5E7EB']]],
            ]);
        }

        $sheet->getStyle('C3:C1000')->getNumberFormat()->setFormatCode('#,##0.000');
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(30);

        // ── Sheet 2: Daftar Material ──────────────────────────────────
        $ref = $spreadsheet->createSheet()->setTitle('Daftar Material');
        $ref->setCellValue('A1', 'Kode Material');
        $ref->setCellValue('B1', 'Nama Material');
        $ref->setCellValue('C1', 'Tipe');
        $ref->setCellValue('D1', 'UoM');
        $ref->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A8A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $materials = Material::where('is_active', true)->whereIn('type', ['FP', 'WIP'])->orderBy('type')->orderBy('code')->get();
        foreach ($materials as $i => $m) {
            $r  = $i + 2;
            $bg = $i % 2 === 0 ? 'FFFFFFFF' : 'FFF3F6FA';
            $ref->setCellValue("A{$r}", $m->code);
            $ref->setCellValue("B{$r}", $m->name);
            $ref->setCellValue("C{$r}", $m->type);
            $ref->setCellValue("D{$r}", $m->unit_of_measure);
            $ref->getStyle("A{$r}:D{$r}")->applyFromArray([
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bg]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE5E7EB']]],
            ]);
        }
        foreach (['A' => 16, 'B' => 36, 'C' => 8, 'D' => 8] as $col => $w) {
            $ref->getColumnDimension($col)->setWidth($w);
        }

        $spreadsheet->setActiveSheetIndex(0);
        return ExcelService::download($spreadsheet, 'Template_Import_ProductionOrder.xlsx');
    }

    public function importExcel(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls|max:5120']);

        $path        = $request->file('file')->getRealPath();
        $spreadsheet = IOFactory::load($path);
        $rows        = $spreadsheet->getSheet(0)->toArray(null, true, false, true);

        $materialMap = Material::where('is_active', true)
            ->whereIn('type', ['FP', 'WIP'])
            ->get(['id', 'code', 'name', 'unit_of_measure'])
            ->keyBy('code');

        $items  = [];
        $errors = [];

        foreach ($rows as $rowNum => $row) {
            if ($rowNum <= 2) continue; // skip instruction + header

            $orderNo  = trim((string) ($row['A'] ?? ''));
            $matCode  = trim((string) ($row['B'] ?? ''));
            $qty      = (float) ($row['C'] ?? 0);
            $note     = trim((string) ($row['D'] ?? ''));

            // blank row
            if ($orderNo === '' && $matCode === '' && $qty == 0) continue;

            if ($orderNo === '') {
                $errors[] = "Baris {$rowNum}: No. Order kosong.";
                continue;
            }
            if ($matCode === '') {
                $errors[] = "Baris {$rowNum}: Kode Material kosong.";
                continue;
            }
            if ($qty <= 0) {
                $errors[] = "Baris {$rowNum}: Qty harus lebih dari 0 (order {$orderNo}).";
                continue;
            }

            $material = $materialMap->get($matCode);
            if (!$material) {
                $errors[] = "Baris {$rowNum}: Kode material '{$matCode}' tidak ditemukan atau bukan FP/WIP.";
                continue;
            }

            if (ProductionOrder::where('order_number', $orderNo)->exists()) {
                $errors[] = "Baris {$rowNum}: No. Order '{$orderNo}' sudah digunakan.";
                continue;
            }

            // check duplicate within this import batch
            $duplicate = collect($items)->first(fn($i) => $i['order_number'] === $orderNo);
            if ($duplicate) {
                $errors[] = "Baris {$rowNum}: No. Order '{$orderNo}' muncul lebih dari sekali dalam file.";
                continue;
            }

            $items[] = [
                'order_number'  => $orderNo,
                'material_id'   => $material->id,
                'material_code' => $material->code,
                'material_name' => $material->name,
                'material_uom'  => $material->unit_of_measure,
                'qty'           => $qty,
                'notes'         => $note,
            ];
        }

        return response()->json(['items' => $items, 'errors' => $errors]);
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
            'bom_id'             => 'nullable|exists:boms,id',
            'routing_id'         => 'nullable|exists:routings,id',
            'quantity_planned'   => 'required|numeric|min:0.001',
            'planned_start_date' => 'required|date',
            'planned_end_date'   => 'required|date|after_or_equal:planned_start_date',
            'notes'              => 'nullable|string|max:1000',
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
                'created_by'          => Auth::id(),
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
                    'created_by'          => Auth::id(),
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
                    'created_by'          => Auth::id(),
                ]);
            }

            // 2. Kembalikan sisa material ke gudang jika issued > yang benar-benar dipakai
            // qtyActuallyUsed = quantity_required * actualRatio (berdasarkan BOM)
            // qtyReturn = qty_issued - qtyActuallyUsed
            // Mencakup dua kasus: (a) issued > required (kirim lebih dari rencana),
            //                     (b) produksi < planned (hasilkan kurang dari rencana)
            $actualRatio = $productionOrder->quantity_planned > 0
                ? $totalConfirmed / $productionOrder->quantity_planned
                : 1;

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
                    'created_by'          => Auth::id(),
                ]);
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
        ini_set('memory_limit', '256M');
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
