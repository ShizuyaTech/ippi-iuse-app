<?php

namespace App\Http\Controllers\MM;

use App\Http\Controllers\Controller;
use App\Models\GoodsIssue;
use App\Models\Material;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StorageLocation;
use App\Models\Vendor;
use App\Models\VendorMaterialDelivery;
use App\Services\ExcelService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class GoodsIssueController extends Controller
{
    public function index(Request $request)
    {
        $query = GoodsIssue::with('storageLocation', 'destinationStorageLocation', 'vendor', 'createdBy');
        if ($request->search)      $query->where('gi_number', 'like', "%{$request->search}%");
        if ($request->date_from)   $query->whereDate('issue_date', '>=', $request->date_from);
        if ($request->date_to)     $query->whereDate('issue_date', '<=', $request->date_to);
        if ($request->f_gi_number) $query->where('gi_number', 'like', "%{$request->f_gi_number}%");
        if ($request->f_location)  $query->whereHas('storageLocation', fn($q) => $q->where('name', 'like', "%{$request->f_location}%")->orWhere('code', 'like', "%{$request->f_location}%"));
        if ($request->location_id)  $query->where('storage_location_id', $request->location_id);
        $issues    = $query->latest()->paginate(20)->withQueryString();
        $locations = StorageLocation::orderBy('name')->get();
        return view('mm.goods-issues.index', compact('issues', 'locations'));
    }

    public function create()
    {
        $materials = Material::where('is_active', true)->orderBy('code')->get();
        $locations = StorageLocation::all();
        $vendors   = Vendor::where('is_active', true)->orderBy('name')->get();
        $customers = \App\Models\Customer::where('is_active', true)->orderBy('name')->get();
        $stocks    = \App\Models\Stock::all()->mapWithKeys(fn($s) => [$s->material_id.'_'.$s->storage_location_id => (float)$s->quantity])->toArray();
        return view('mm.goods-issues.create', compact('materials', 'locations', 'vendors', 'customers', 'stocks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'issue_date'                       => 'required|date',
            'issue_type'                       => 'required|in:internal,to_vendor,to_customer',
            'vendor_id'                        => 'nullable|exists:vendors,id',
            'destination_name'                 => 'nullable|string|max:255',
            'destination_storage_location_id'  => 'nullable|exists:storage_locations,id',
            'storage_location_id'              => 'required|exists:storage_locations,id',
            'notes'                            => 'nullable|string',
            'items'                            => 'required|array|min:1',
            'items.*.material_id'              => 'required|exists:materials,id',
            'items.*.quantity'                 => 'required|numeric|min:0.001',
            'items.*.note'                     => 'nullable|string|max:500',
        ]);

        // Pisahkan item: cukup stok vs tidak cukup stok
        $processableItems = [];
        $skippedItems     = [];

        foreach ($request->items as $item) {
            $stock     = Stock::where('material_id', $item['material_id'])
                ->where('storage_location_id', $request->storage_location_id)
                ->first();
            $available = $stock ? (float) $stock->quantity : 0;

            if ($available < (float) $item['quantity']) {
                $material       = Material::find($item['material_id']);
                $skippedItems[] = "- {$material->code} ({$material->name}): butuh {$item['quantity']}, tersedia {$available}";
            } else {
                $processableItems[] = $item;
            }
        }

        // Jika semua item stok tidak cukup, kembalikan ke form
        if (empty($processableItems)) {
            $msg = implode("\n", $skippedItems);
            return back()->withErrors(['items' => "Semua item tidak dapat diproses karena stok tidak mencukupi:\n{$msg}"])->withInput();
        }

        $giNumber = null;
        DB::transaction(function () use ($request, $processableItems, &$giNumber) {
            $gi = GoodsIssue::create([
                'gi_number'           => GoodsIssue::generateNumber(),
                'reference_type'      => 'manual',
                'issue_date'          => $request->issue_date,
                'issue_type'          => $request->issue_type,
                'vendor_id'           => $request->issue_type === 'to_vendor' ? $request->vendor_id : null,
                'destination_name'                => in_array($request->issue_type, ['to_vendor', 'to_customer']) ? $request->destination_name : null,
                'destination_storage_location_id' => $request->issue_type === 'internal' ? $request->destination_storage_location_id : null,
                'storage_location_id' => $request->storage_location_id,
                'status'              => 'posted',
                'notes'               => $request->notes,
                'created_by'          => auth()->id(),
            ]);

            $giNumber = $gi->gi_number;

            foreach ($processableItems as $item) {
                $gi->items()->create([
                    'material_id'     => $item['material_id'],
                    'quantity_issued' => $item['quantity'],
                    'note'            => $item['note'] ?? null,
                ]);

                // Deduct from source
                $stock  = Stock::where('material_id', $item['material_id'])
                    ->where('storage_location_id', $request->storage_location_id)
                    ->first();
                $newQty = $stock->quantity - $item['quantity'];
                $stock->update(['quantity' => $newQty]);

                StockMovement::create([
                    'material_id'         => $item['material_id'],
                    'storage_location_id' => $request->storage_location_id,
                    'movement_type'       => 'GI',
                    'quantity'            => $item['quantity'],
                    'quantity_after'      => $newQty,
                    'reference_document'  => $gi->gi_number,
                    'movement_date'       => $request->issue_date,
                    'created_by'          => auth()->id(),
                ]);

                // For internal transfers: add stock to destination location
                if ($request->issue_type === 'internal' && $request->destination_storage_location_id) {
                    $destStock = Stock::firstOrCreate(
                        [
                            'material_id'         => $item['material_id'],
                            'storage_location_id' => $request->destination_storage_location_id,
                        ],
                        ['quantity' => 0]
                    );
                    $destNewQty = $destStock->quantity + $item['quantity'];
                    $destStock->update(['quantity' => $destNewQty]);

                    StockMovement::create([
                        'material_id'         => $item['material_id'],
                        'storage_location_id' => $request->destination_storage_location_id,
                        'movement_type'       => 'GI_IN',
                        'quantity'            => $item['quantity'],
                        'quantity_after'      => $destNewQty,
                        'reference_document'  => $gi->gi_number,
                        'movement_date'       => $request->issue_date,
                        'created_by'          => auth()->id(),
                    ]);
                }
            }

            // Auto-create VMD so vendor can confirm receipt in the vendor portal
            if ($request->issue_type === 'to_vendor' && $request->vendor_id) {
                $vmd = VendorMaterialDelivery::create([
                    'vmd_number'       => VendorMaterialDelivery::generateNumber(),
                    'goods_issue_id'   => $gi->id,
                    'vendor_id'        => $request->vendor_id,
                    'delivery_date'    => $request->issue_date,
                    'notes'            => $gi->notes,
                    'status'           => 'sent',
                    'created_by'       => auth()->id(),
                ]);

                foreach ($gi->items as $giItem) {
                    $vmd->items()->create([
                        'material_id'         => $giItem->material_id,
                        'storage_location_id' => $gi->storage_location_id,
                        'quantity'            => $giItem->quantity_issued,
                        'notes'               => $giItem->note,
                    ]);
                }
            }
        });

        if (!empty($skippedItems)) {
            $warning = "Goods Issue {$giNumber} berhasil diposting, namun item berikut dilewati karena stok tidak mencukupi:\n" . implode("\n", $skippedItems);
            return redirect()->route('mm.goods-issues.index')->with('warning', $warning);
        }

        return redirect()->route('mm.goods-issues.index')->with('success', 'Goods Issue berhasil diposting.');
    }

    public function show(GoodsIssue $goodsIssue)
    {
        $goodsIssue->load('items.material.bomItems.bom.material', 'storageLocation', 'destinationStorageLocation', 'createdBy');
        return view('mm.goods-issues.show', compact('goodsIssue'));
    }

    public function destroy(GoodsIssue $goodsIssue)
    {
        DB::transaction(function () use ($goodsIssue) {
            $goodsIssue->load('items');

            foreach ($goodsIssue->items as $item) {
                // Reverse: add back to source
                $stock = Stock::where('material_id', $item->material_id)
                    ->where('storage_location_id', $goodsIssue->storage_location_id)
                    ->first();
                if ($stock) {
                    $stock->increment('quantity', $item->quantity_issued);
                    $stock->refresh();
                    StockMovement::create([
                        'material_id'         => $item->material_id,
                        'storage_location_id' => $goodsIssue->storage_location_id,
                        'movement_type'       => 'GI_REV',
                        'quantity'            => $item->quantity_issued,
                        'quantity_after'      => $stock->quantity,
                        'reference_document'  => $goodsIssue->gi_number,
                        'movement_date'       => now()->toDateString(),
                        'created_by'          => auth()->id(),
                    ]);
                }

                // Reverse internal transfer: deduct from destination
                if ($goodsIssue->issue_type === 'internal' && $goodsIssue->destination_storage_location_id) {
                    $destStock = Stock::where('material_id', $item->material_id)
                        ->where('storage_location_id', $goodsIssue->destination_storage_location_id)
                        ->first();
                    if ($destStock) {
                        $destNewQty = max(0, $destStock->quantity - $item->quantity_issued);
                        $destStock->update(['quantity' => $destNewQty]);
                        StockMovement::create([
                            'material_id'         => $item->material_id,
                            'storage_location_id' => $goodsIssue->destination_storage_location_id,
                            'movement_type'       => 'GI_IN_REV',
                            'quantity'            => $item->quantity_issued,
                            'quantity_after'      => $destNewQty,
                            'reference_document'  => $goodsIssue->gi_number,
                            'movement_date'       => now()->toDateString(),
                            'created_by'          => auth()->id(),
                        ]);
                    }
                }
            }

            $goodsIssue->items()->delete();
            $goodsIssue->delete();
        });

        return redirect()->route('mm.goods-issues.index')->with('success', 'Goods Issue berhasil dihapus dan stok dibalik.');
    }

    public function edit(GoodsIssue $goodsIssue)
    {
        $goodsIssue->load('items.material', 'storageLocation');
        $materials = Material::where('is_active', true)->orderBy('code')->get();
        $locations = StorageLocation::all();
        return view('mm.goods-issues.edit', compact('goodsIssue', 'materials', 'locations'));
    }

    public function update(Request $request, GoodsIssue $goodsIssue)
    {
        $request->validate([
            'issue_date'          => 'required|date',
            'storage_location_id' => 'required|exists:storage_locations,id',
            'notes'               => 'nullable|string',
            'items'               => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.quantity'    => 'required|numeric|min:0.001',
        ]);

        DB::transaction(function () use ($request, $goodsIssue) {
            $goodsIssue->load('items');

            // 1. Reverse old stock
            foreach ($goodsIssue->items as $item) {
                // Add back to source
                $stock = Stock::where('material_id', $item->material_id)
                    ->where('storage_location_id', $goodsIssue->storage_location_id)
                    ->first();
                if ($stock) {
                    $stock->increment('quantity', $item->quantity_issued);
                    $stock->refresh();
                    StockMovement::create([
                        'material_id'         => $item->material_id,
                        'storage_location_id' => $goodsIssue->storage_location_id,
                        'movement_type'       => 'GI_REV',
                        'quantity'            => $item->quantity_issued,
                        'quantity_after'      => $stock->quantity,
                        'reference_document'  => $goodsIssue->gi_number,
                        'movement_date'       => now()->toDateString(),
                        'created_by'          => auth()->id(),
                    ]);
                }

                // Reverse destination for old internal transfer
                if ($goodsIssue->issue_type === 'internal' && $goodsIssue->destination_storage_location_id) {
                    $destStock = Stock::where('material_id', $item->material_id)
                        ->where('storage_location_id', $goodsIssue->destination_storage_location_id)
                        ->first();
                    if ($destStock) {
                        $destNewQty = max(0, $destStock->quantity - $item->quantity_issued);
                        $destStock->update(['quantity' => $destNewQty]);
                        StockMovement::create([
                            'material_id'         => $item->material_id,
                            'storage_location_id' => $goodsIssue->destination_storage_location_id,
                            'movement_type'       => 'GI_IN_REV',
                            'quantity'            => $item->quantity_issued,
                            'quantity_after'      => $destNewQty,
                            'reference_document'  => $goodsIssue->gi_number,
                            'movement_date'       => now()->toDateString(),
                            'created_by'          => auth()->id(),
                        ]);
                    }
                }
            }

            // 2. Validate new stock availability
            foreach ($request->items as $item) {
                $stock = Stock::where('material_id', $item['material_id'])
                    ->where('storage_location_id', $request->storage_location_id)
                    ->first();
                $available = $stock ? $stock->quantity : 0;
                if ($available < $item['quantity']) {
                    $material = Material::find($item['material_id']);
                    throw new \Exception("Stok tidak cukup untuk {$material->code}. Tersedia: {$available}");
                }
            }

            // 3. Delete old items & update header
            $goodsIssue->items()->delete();
            $goodsIssue->update([
                'issue_date'          => $request->issue_date,
                'storage_location_id' => $request->storage_location_id,
                'notes'               => $request->notes,
            ]);

            // 4. Apply new items + stock
            foreach ($request->items as $item) {
                $goodsIssue->items()->create([
                    'material_id'    => $item['material_id'],
                    'quantity_issued' => $item['quantity'],
                ]);

                // Deduct from source
                $stock = Stock::where('material_id', $item['material_id'])
                    ->where('storage_location_id', $request->storage_location_id)
                    ->first();
                $newQty = $stock->quantity - $item['quantity'];
                $stock->update(['quantity' => $newQty]);

                StockMovement::create([
                    'material_id'         => $item['material_id'],
                    'storage_location_id' => $request->storage_location_id,
                    'movement_type'       => 'GI',
                    'quantity'            => $item['quantity'],
                    'quantity_after'      => $newQty,
                    'reference_document'  => $goodsIssue->gi_number,
                    'movement_date'       => $request->issue_date,
                    'created_by'          => auth()->id(),
                ]);

                // Add to destination for internal transfers
                if ($goodsIssue->issue_type === 'internal' && $goodsIssue->destination_storage_location_id) {
                    $destStock = Stock::firstOrCreate(
                        [
                            'material_id'         => $item['material_id'],
                            'storage_location_id' => $goodsIssue->destination_storage_location_id,
                        ],
                        ['quantity' => 0]
                    );
                    $destNewQty = $destStock->quantity + $item['quantity'];
                    $destStock->update(['quantity' => $destNewQty]);

                    StockMovement::create([
                        'material_id'         => $item['material_id'],
                        'storage_location_id' => $goodsIssue->destination_storage_location_id,
                        'movement_type'       => 'GI_IN',
                        'quantity'            => $item['quantity'],
                        'quantity_after'      => $destNewQty,
                        'reference_document'  => $goodsIssue->gi_number,
                        'movement_date'       => $request->issue_date,
                        'created_by'          => auth()->id(),
                    ]);
                }
            }
        });

        return redirect()->route('mm.goods-issues.show', $goodsIssue)
            ->with('success', 'Goods Issue berhasil diperbarui.');
    }

    public function printPdf(GoodsIssue $goodsIssue)
    {
        $goodsIssue->load('items.material', 'storageLocation', 'destinationStorageLocation', 'createdBy');
        $pdf = Pdf::loadView('mm.goods-issues.pdf', compact('goodsIssue'))
            ->setPaper('a4', 'portrait');
        return $pdf->stream('GI_' . $goodsIssue->gi_number . '.pdf');
    }

    public function exportExcelDetail(GoodsIssue $goodsIssue)
    {
        $goodsIssue->load('items.material.bomItems.bom.material', 'storageLocation', 'destinationStorageLocation', 'createdBy');

        $typeLabel = ['internal' => 'Pemakaian Internal', 'to_vendor' => 'Kirim ke Vendor (Proses)', 'to_customer' => 'Kirim ke Customer'];
        $t         = $goodsIssue->issue_type ?? 'internal';

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Goods Issue');

        // ── colour palette (mirrors PDF) ──────────────────────────────
        $orange      = 'FFEA580C'; // thead bg
        $orangeLight = 'FFFFF7ED'; // even row / total bg
        $blue        = 'FF1D4ED8'; // vendor dest border
        $blueLight   = 'FFEFF6FF';
        $green       = 'FF15803D';
        $greenLight   = 'FFF0FDF4';
        $yellow      = 'FFFFF8DC'; // note chip bg
        $gray        = 'FFF3F4F6';
        $darkText    = 'FF1A1A1A';
        $white       = 'FFFFFFFF';

        $colCount = 7; // A–H
        $lastCol  = 'G';

        // ── helper: merge + style a full-width banner row ─────────────
        $setRow = function (int $row, string $text, array $style = []) use ($sheet, $lastCol) {
            $sheet->setCellValue("A{$row}", $text);
            $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
            if (!empty($style)) $sheet->getStyle("A{$row}")->applyFromArray($style);
        };

        // ═══════════════════════════════════════════════════════════════
        // ROW 1  — Company name (left) + Document title (right)
        // ═══════════════════════════════════════════════════════════════
        $sheet->getRowDimension(1)->setRowHeight(26);
        $sheet->setCellValue('A1', 'IPPI — Integrated Production & Inventory System');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['argb' => $orange]],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                            'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ]);

        $sheet->setCellValue('F1', 'GOODS ISSUE');
        $sheet->mergeCells('F1:G1');
        $sheet->getStyle('F1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['argb' => $orange]],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                            'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ]);

        // ROW 2  — GI number (right) + status badge
        $sheet->getRowDimension(2)->setRowHeight(18);
        $sheet->setCellValue('F2', $goodsIssue->gi_number . '  [' . strtoupper($goodsIssue->status ?? 'posted') . ']');
        $sheet->mergeCells('F2:G2');
        $sheet->getStyle('F2')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FF374151']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                            'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ]);

        // Divider row 3
        $sheet->getRowDimension(3)->setRowHeight(4);
        $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => $orange]],
        ]);

        // ═══════════════════════════════════════════════════════════════
        // ROWS 4–7  — Info grid (2 columns × 4 rows, mirrors PDF)
        // ═══════════════════════════════════════════════════════════════
        $infoStart = 4;
        $infoRows  = [
            ['Tanggal Issue',        $goodsIssue->issue_date->format('d F Y'),
             'Dari Storage Location', ($goodsIssue->storageLocation->code ?? '-') . ' — ' . ($goodsIssue->storageLocation->name ?? '-')],
            ['Tipe Issue',           $typeLabel[$t] ?? $t,
             'Dibuat oleh',          $goodsIssue->createdBy->name ?? '-'],
        ];

        $ir = $infoStart;
        foreach ($infoRows as $cols) {
            $sheet->getRowDimension($ir)->setRowHeight(28);

            // Left label / value
            $sheet->setCellValue("A{$ir}", $cols[0]);
            $sheet->mergeCells("A{$ir}:A{$ir}");
            $sheet->setCellValue("B{$ir}", $cols[1]);
            $sheet->mergeCells("B{$ir}:C{$ir}");

            // Right label / value
            $sheet->setCellValue("E{$ir}", $cols[2]);
            $sheet->mergeCells("E{$ir}:E{$ir}");
            $sheet->setCellValue("F{$ir}", $cols[3]);
            $sheet->mergeCells("F{$ir}:G{$ir}");

            // Label style
            foreach (["A{$ir}", "E{$ir}"] as $lc) {
                $sheet->getStyle($lc)->applyFromArray([
                    'font'      => ['size' => 8, 'color' => ['argb' => 'FF6B7280']],
                    'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => $gray]],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                                    'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_BOTTOM,
                                    'wrapText'   => false],
                ]);
            }
            // Value style
            foreach (["B{$ir}:C{$ir}", "F{$ir}:G{$ir}"] as $vc) {
                $sheet->getStyle($vc)->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 10, 'color' => ['argb' => $darkText]],
                    'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => $white]],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                                    'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                ]);
            }
            $sheet->getStyle("A{$ir}:{$lastCol}{$ir}")->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                ->getColor()->setARGB('FFE5E7EB');
            $ir++;
        }

        // ═══════════════════════════════════════════════════════════════
        // DESTINATION BOX  (vendor / customer)
        // ═══════════════════════════════════════════════════════════════
        $r = $ir + 1; // blank gap row
        $sheet->getRowDimension($r - 1)->setRowHeight(6);

        if ($goodsIssue->destination_name) {
            $isVendor = ($t === 'to_vendor');
            $destBg   = $isVendor ? $blueLight : $greenLight;
            $destFg   = $isVendor ? $blue      : $green;
            $destLbl  = $isVendor ? 'DIKIRIM KE VENDOR' : 'DIKIRIM KE CUSTOMER';

            // Label row
            $sheet->getRowDimension($r)->setRowHeight(16);
            $sheet->setCellValue("A{$r}", $destLbl);
            $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
            $sheet->getStyle("A{$r}")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 8, 'color' => ['argb' => $destFg]],
                'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => $destBg]],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                                'indent'     => 1],
                'borders'   => ['top'  => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => $destFg]],
                                'left' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => $destFg]],
                                'right'=> ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => $destFg]]],
            ]);
            $r++;

            // Name row
            $sheet->getRowDimension($r)->setRowHeight(22);
            $sheet->setCellValue("A{$r}", $goodsIssue->destination_name);
            $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
            $sheet->getStyle("A{$r}")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 12, 'color' => ['argb' => $isVendor ? 'FF1E3A8A' : 'FF14532D']],
                'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => $destBg]],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                                'indent'     => 1],
                'borders'   => ['bottom'=> ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => $destFg]],
                                'left'  => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => $destFg]],
                                'right' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => $destFg]]],
            ]);
            $r++;
            $sheet->getRowDimension($r)->setRowHeight(6); // gap
            $r++;
        }

        // ═══════════════════════════════════════════════════════════════
        // ITEMS TABLE HEADER
        // ═══════════════════════════════════════════════════════════════
        $tableStart = $r;
        $sheet->getRowDimension($r)->setRowHeight(22);
        $headers = ['#', 'Kode Material', 'Kode WIP/FP', 'Nama Material', 'UoM', 'Qty Keluar', 'Note / ID Packing'];
        foreach ($headers as $i => $g) {
            $col = chr(65 + $i);
            $sheet->setCellValue("{$col}{$r}", $g);
        }
        $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['argb' => $white]],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => $orange]],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                            'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                             'color'       => ['argb' => 'FFCC5500']]],
        ]);
        // right-align Qty header
        $sheet->getStyle("G{$r}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $r++;

        // ═══════════════════════════════════════════════════════════════
        // ITEMS ROWS
        // ═══════════════════════════════════════════════════════════════
        $no      = 1;
        $totalQty = 0;
        foreach ($goodsIssue->items as $item) {
            $isEven  = ($no % 2 === 0);
            $rowBg   = $isEven ? $orangeLight : $white;

            $sheet->getRowDimension($r)->setRowHeight(18);
            $sheet->setCellValue("A{$r}", $no++);
            $wipCodes = $item->material->bomItems
                ->map(fn($bi) => $bi->bom?->material?->code)
                ->filter()->unique()->join(', ');
            $sheet->setCellValue("B{$r}", $item->material->code ?? '-');
            $sheet->setCellValue("C{$r}", $wipCodes ?: '-');
            // $sheet->setCellValue("D{$r}", $item->material->type ?? '-');
            $sheet->setCellValue("D{$r}", $item->material->name ?? '-');
            $sheet->setCellValue("E{$r}", $item->material->unit_of_measure ?? '-');
            $sheet->setCellValue("F{$r}", (int) round((float) $item->quantity_issued));
            $sheet->setCellValue("G{$r}", $item->note ?? '');

            $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                'fill'    => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => $rowBg]],
                'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                               'color'       => ['argb' => 'FFE5E7EB']]],
                'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            ]);

            // # column — center
            $sheet->getStyle("A{$r}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            // Kode — monospaced colour
            $sheet->getStyle("B{$r}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF1D4ED8'));
            // UoM — center
            $sheet->getStyle("E{$r}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            // Qty — right, bold, orange
            $sheet->getStyle("F{$r}")->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFC2410C']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT],
                'numberFormat' => ['formatCode' => '#,##0'],
            ]);
            // Note — yellow chip style
            if ($item->note) {
                $sheet->getStyle("G{$r}")->applyFromArray([
                    'font' => ['size' => 9, 'color' => ['argb' => 'FF92400E']],
                    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => $yellow]],
                ]);
            }

            $totalQty += (float) $item->quantity_issued;
            $r++;
        }

        // ═══════════════════════════════════════════════════════════════
        // TOTAL ROW
        // ═══════════════════════════════════════════════════════════════
        $sheet->getRowDimension($r)->setRowHeight(20);
        $sheet->setCellValue("F{$r}", 'Total Qty Keluar:');
        $sheet->mergeCells("A{$r}:E{$r}");
        $sheet->getStyle("A{$r}:E{$r}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10],
            'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => $orangeLight]],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT],
            'borders'   => ['top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['argb' => $orange]]],
        ]);
        $sheet->setCellValue("F{$r}", (int) round($totalQty));
        $sheet->getStyle("F{$r}")->applyFromArray([
            'font'         => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FFC2410C']],
            'fill'         => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => $orangeLight]],
            'alignment'    => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT],
            'numberFormat' => ['formatCode' => '#,##0'],
            'borders'      => ['top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['argb' => $orange]]],
        ]);
        $sheet->getStyle("G{$r}")->applyFromArray([
            'fill'    => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => $orangeLight]],
            'borders' => ['top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['argb' => $orange]]],
        ]);
        $r++;

        // ═══════════════════════════════════════════════════════════════
        // NOTES BOX
        // ═══════════════════════════════════════════════════════════════
        if ($goodsIssue->notes) {
            $sheet->getRowDimension($r)->setRowHeight(6); $r++;   // gap

            $sheet->getRowDimension($r)->setRowHeight(14);
            $sheet->setCellValue("A{$r}", 'KETERANGAN');
            $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
            $sheet->getStyle("A{$r}")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 8, 'color' => ['argb' => 'FF92400E']],
                'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => $yellow]],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                                'indent'     => 1],
                'borders'   => ['top'  => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FFFDE68A']],
                                'left' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FFFDE68A']],
                                'right'=> ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FFFDE68A']]],
            ]);
            $r++;

            $sheet->getRowDimension($r)->setRowHeight(18);
            $sheet->setCellValue("A{$r}", $goodsIssue->notes);
            $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
            $sheet->getStyle("A{$r}")->applyFromArray([
                'font'      => ['size' => 10, 'color' => ['argb' => 'FF374151']],
                'fill'      => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => $yellow]],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                                'indent'     => 1],
                'borders'   => ['bottom'=> ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FFFDE68A']],
                                'left'  => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FFFDE68A']],
                                'right' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FFFDE68A']]],
            ]);
            $r++;
        }

        // ═══════════════════════════════════════════════════════════════
        // SIGNATURE SECTION (3 columns: Dikeluarkan / Diperiksa / Disetujui)
        // ═══════════════════════════════════════════════════════════════
        $sheet->getRowDimension($r)->setRowHeight(6);  $r++;  // gap
        $sheet->getRowDimension($r)->setRowHeight(4);
        $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
            'borders' => ['top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FFE5E7EB']]],
        ]);
        $r++;

        $sigTitles = ['Dikeluarkan oleh', 'Diperiksa oleh', 'Disetujui oleh'];
        $sigNames  = [$goodsIssue->createdBy->name ?? '___________________', '___________________', '___________________'];
        $sigRoles  = ['Warehouse / Inventory', 'Supervisor', 'Manager'];
        $sigCols   = [['A', 'B'], ['C', 'D'], ['E', 'F']];

        // Title row
        $noBorder = ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE];
        $sheet->getRowDimension($r)->setRowHeight(14);
        foreach ($sigCols as $i => [$c1, $c2]) {
            $cell = "{$c1}{$r}";
            $sheet->setCellValue($cell, $sigTitles[$i]);
            $sheet->mergeCells("{$c1}{$r}:{$c2}{$r}");
            $sheet->getStyle("{$c1}{$r}:{$c2}{$r}")->applyFromArray([
                'font'      => ['size' => 8, 'color' => ['argb' => 'FF6B7280']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                'borders'   => ['top' => $noBorder, 'bottom' => $noBorder, 'left' => $noBorder, 'right' => $noBorder],
            ]);
        }
        $r++;

        // Blank space for physical signature — clear any inherited borders
        for ($g = 0; $g < 4; $g++) {
            $sheet->getRowDimension($r)->setRowHeight(12);
            $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                'borders' => ['allBorders' => $noBorder],
            ]);
            $r++;
        }

        // Signature line + name
        $sheet->getRowDimension($r)->setRowHeight(16);
        foreach ($sigCols as $i => [$c1, $c2]) {
            $sheet->setCellValue("{$c1}{$r}", $sigNames[$i]);
            $sheet->mergeCells("{$c1}{$r}:{$c2}{$r}");
            $sheet->getStyle("{$c1}{$r}")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 10],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                'borders'   => ['top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                                          'color'       => ['argb' => 'FF374151']]],
            ]);
        }
        $r++;

        // Role row
        $sheet->getRowDimension($r)->setRowHeight(13);
        foreach ($sigCols as $i => [$c1, $c2]) {
            $sheet->setCellValue("{$c1}{$r}", $sigRoles[$i]);
            $sheet->mergeCells("{$c1}{$r}:{$c2}{$r}");
            $sheet->getStyle("{$c1}{$r}")->applyFromArray([
                'font'      => ['size' => 8, 'color' => ['argb' => 'FF6B7280']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            ]);
        }
        $r++;

        // ═══════════════════════════════════════════════════════════════
        // FOOTER
        // ═══════════════════════════════════════════════════════════════
        $sheet->getRowDimension($r)->setRowHeight(4);
        $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
            'borders' => ['top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['argb' => 'FFF3F4F6']]],
        ]);
        $r++;
        $sheet->getRowDimension($r)->setRowHeight(14);
        $sheet->setCellValue("A{$r}", 'Dicetak pada: ' . now()->format('d M Y H:i') . '  |  ' . $goodsIssue->gi_number . '  |  IPPI - Integrated Production & Inventory System');
        $sheet->mergeCells("A{$r}:{$lastCol}{$r}");
        $sheet->getStyle("A{$r}")->applyFromArray([
            'font'      => ['size' => 8, 'color' => ['argb' => 'FF9CA3AF']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                            'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
        ]);

        // ═══════════════════════════════════════════════════════════════
        // COLUMN WIDTHS  (match PDF proportions)
        // ═══════════════════════════════════════════════════════════════
        $sheet->getColumnDimension('A')->setWidth(5);   // #
        $sheet->getColumnDimension('B')->setWidth(14);  // Kode RM
        $sheet->getColumnDimension('C')->setWidth(14);  // Kode WIP/FP
        $sheet->getColumnDimension('D')->setWidth(8);   // Jenis
        $sheet->getColumnDimension('E')->setWidth(28);  // Nama
        $sheet->getColumnDimension('F')->setWidth(8);   // UoM
        $sheet->getColumnDimension('G')->setWidth(12);  // Qty
        $sheet->getColumnDimension('H')->setWidth(22);  // Note

        // Print setup
        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
            ->setFitToPage(true)->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.4)->setBottom(0.4)->setLeft(0.4)->setRight(0.4);

        return ExcelService::download($spreadsheet, 'GI_' . $goodsIssue->gi_number . '.xlsx');
    }

    public function exportExcel(Request $request)
    {
        $query = GoodsIssue::with('storageLocation', 'items.material');
        if ($request->search)      $query->where('gi_number', 'like', "%{$request->search}%");
        if ($request->date_from)   $query->whereDate('issue_date', '>=', $request->date_from);
        if ($request->date_to)     $query->whereDate('issue_date', '<=', $request->date_to);
        $issues = $query->orderBy('id', 'desc')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Goods Issues');

        $headers = ['No. GI','Tgl Issue','Lokasi','Keterangan','Material','Job Number','Qty Keluar'];
        foreach ($headers as $i => $h) $sheet->setCellValue(chr(65+$i).'1', $h);
        ExcelService::applyHeaderStyle($spreadsheet, 'A1:G1');
        $sheet->getRowDimension(1)->setRowHeight(20);

        $r = 2;
        foreach ($issues as $gi) {
            foreach ($gi->items as $item) {
                $sheet->setCellValue("A{$r}", $gi->gi_number);
                $sheet->setCellValue("B{$r}", $gi->issue_date->format('d/m/Y'));
                $sheet->setCellValue("C{$r}", $gi->storageLocation->code ?? '-');
                $sheet->setCellValue("D{$r}", $gi->notes);
                $sheet->setCellValue("E{$r}", ($item->material->code ?? ''));
                $sheet->setCellValue("F{$r}", ($item->material->name ?? ''));
                $sheet->setCellValue("G{$r}", (float)$item->quantity_issued);
                ExcelService::applyDataStyle($spreadsheet, "A{$r}:G{$r}", $r % 2 === 0);
                $r++;
            }
            if ($gi->items->isEmpty()) {
                $sheet->setCellValue("A{$r}", $gi->gi_number);
                $sheet->setCellValue("B{$r}", $gi->issue_date->format('d/m/Y'));
                ExcelService::applyDataStyle($spreadsheet, "A{$r}:G{$r}", $r % 2 === 0);
                $r++;
            }
        }
        foreach (range('A','F') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        return ExcelService::download($spreadsheet, 'goods_issues_'.date('Ymd').'.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $query = GoodsIssue::with('storageLocation', 'items');
        if ($request->search)    $query->where('gi_number', 'like', "%{$request->search}%");
        if ($request->date_from) $query->whereDate('issue_date', '>=', $request->date_from);
        if ($request->date_to)   $query->whereDate('issue_date', '<=', $request->date_to);
        $issues  = $query->orderBy('id', 'desc')->get();
        $filters = $request->only(['search', 'date_from', 'date_to']);
        $pdf = Pdf::loadView('mm.goods-issues.pdf-list', compact('issues', 'filters'))
            ->setPaper('a4', 'landscape');
        return $pdf->stream('goods_issues_'.date('Ymd').'.pdf');
    }
}
