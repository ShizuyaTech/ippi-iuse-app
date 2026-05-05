<?php

namespace App\Http\Controllers\MM;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Stock;
use App\Models\StorageLocation;
use App\Models\Vendor;
use App\Services\ExcelService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        $query = Material::withSum('stocks', 'quantity');
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', "%{$request->search}%")
                  ->orWhere('name', 'like', "%{$request->search}%");
            });
        }
        if ($request->type) {
            $query->where('type', $request->type);
        }
        if ($request->date_from) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to)   $query->whereDate('created_at', '<=', $request->date_to);
        $materials = $query->latest()->paginate(20)->withQueryString();
        return view('mm.materials.index', compact('materials'));
    }

    public function create()
    {
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        return view('mm.materials.create', compact('vendors'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code'            => 'required|string|max:20|unique:materials,code',
            'name'            => 'required|string|max:255',
            'description'     => 'nullable|string',
            'type'            => 'required|in:RM,WIP,FP',
            'unit_of_measure' => 'required|string|max:10',
            'standard_price'  => 'required|numeric|min:0',
            'qty_per_case'    => 'nullable|numeric|min:0',
            'min_stock'       => 'nullable|numeric|min:0',
            'order_method'    => 'required|in:mrp,skm',
            'vendor_id'       => 'nullable|exists:vendors,id',
            'process_vendor_id' => 'nullable|exists:vendors,id',
        ]);
        $material = Material::create($request->only(
            'code', 'name', 'description', 'type', 'unit_of_measure',
            'standard_price', 'qty_per_case', 'min_stock', 'order_method', 'vendor_id', 'process_vendor_id'
        ) + ['is_active' => true]);
        $locations = StorageLocation::where(fn($q) => $q->whereNull('material_type')->orWhere('material_type', $material->type))->get();
        foreach ($locations as $loc) {
            Stock::firstOrCreate(
                ['material_id' => $material->id, 'storage_location_id' => $loc->id],
                ['quantity' => 0]
            );
        }
        return redirect()->route('mm.materials.index')->with('success', 'Material berhasil dibuat.');
    }

    public function show(Material $material)
    {
        $material->load('stocks.storageLocation', 'stockMovements.storageLocation');
        return view('mm.materials.show', compact('material'));
    }

    public function edit(Material $material)
    {
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        return view('mm.materials.edit', compact('material', 'vendors'));
    }

    public function update(Request $request, Material $material)
    {
        $request->validate([
            'code'            => 'required|string|max:20|unique:materials,code,' . $material->id,
            'name'            => 'required|string|max:255',
            'description'     => 'nullable|string',
            'type'            => 'required|in:RM,WIP,FP',
            'unit_of_measure' => 'required|string|max:10',
            'standard_price'  => 'required|numeric|min:0',
            'qty_per_case'    => 'nullable|numeric|min:0',
            'min_stock'       => 'nullable|numeric|min:0',
            'order_method'    => 'required|in:mrp,skm',
            'vendor_id'       => 'nullable|exists:vendors,id',
            'process_vendor_id' => 'nullable|exists:vendors,id',
        ]);
        $material->update($request->only(
            'code', 'name', 'description', 'type', 'unit_of_measure',
            'standard_price', 'qty_per_case', 'min_stock', 'order_method', 'vendor_id', 'process_vendor_id'
        ));
        return redirect()->route('mm.materials.index')->with('success', 'Material berhasil diperbarui.');
    }

    public function destroy(Material $material)
    {
        // Blokir hapus jika material sudah ada transaksi
        $hasTransactions = $material->stockMovements()->exists()
            || $material->bomItems()->exists()
            || $material->productionOrders()->exists()
            || \App\Models\GoodsReceiptItem::where('material_id', $material->id)->exists()
            || \App\Models\GoodsIssueItem::where('material_id', $material->id)->exists();

        if ($hasTransactions) {
            return redirect()->route('mm.materials.index')
                ->with('error', "Material [{$material->code}] tidak dapat dihapus karena sudah memiliki transaksi (mutasi stok, GR, GI, BOM, atau Production Order).");
        }

        // Hapus stocks (qty=0) yang terkait, lalu hapus material
        $material->stocks()->delete();
        $material->delete();
        return redirect()->route('mm.materials.index')->with('success', 'Material berhasil dihapus.');
    }

    public function exportExcel(Request $request)
    {
        $query = Material::withSum('stocks', 'quantity');
        if ($request->search) $query->where(fn($q) => $q->where('code','like',"%{$request->search}%")->orWhere('name','like',"%{$request->search}%"));
        if ($request->type)   $query->where('type', $request->type);
        $materials = $query->orderBy('code')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Materials');

        $headers = ['Kode','Nama','Deskripsi','Tipe','Satuan','Harga Standar','Qty/Case','Min Stock','Stok Saat Ini','Aktif'];
        foreach ($headers as $i => $h) $sheet->setCellValue(chr(65+$i).'1', $h);
        ExcelService::applyHeaderStyle($spreadsheet, 'A1:J1');
        $sheet->getRowDimension(1)->setRowHeight(20);

        foreach ($materials as $row => $m) {
            $r = $row + 2;
            $sheet->setCellValue("A{$r}", $m->code);
            $sheet->setCellValue("B{$r}", $m->name);
            $sheet->setCellValue("C{$r}", $m->description);
            $sheet->setCellValue("D{$r}", $m->type);
            $sheet->setCellValue("E{$r}", $m->unit_of_measure);
            $sheet->setCellValue("F{$r}", (float)$m->standard_price);
            $sheet->setCellValue("G{$r}", (float)$m->qty_per_case);
            $sheet->setCellValue("H{$r}", (float)$m->min_stock);
            $sheet->setCellValue("I{$r}", (float)$m->stocks_sum_quantity);
            $sheet->setCellValue("J{$r}", $m->is_active ? 'Ya' : 'Tidak');
            ExcelService::applyDataStyle($spreadsheet, "A{$r}:J{$r}", $row % 2 === 0);
        }
        foreach (range('A','J') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

        return ExcelService::download($spreadsheet, 'materials_'.date('Ymd').'.xlsx');
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import');

        // Info sheet
        $sheet->setCellValue('A1', 'TEMPLATE IMPORT MATERIAL');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->setCellValue('A2', 'Petunjuk: Isi data mulai baris 7. Jangan ubah header. Kolom bertanda * wajib diisi.');
        ExcelService::applyNoteStyle($spreadsheet, 'A2:M2');
        $sheet->mergeCells('A2:M2');

        $sheet->setCellValue('A3', 'Tipe: RM = Raw Material, WIP = Work In Progress, FP = Finished Product');
        $sheet->setCellValue('A4', 'Order Method: mrp = MRP, skm = Summary Kanban | Aktif: Ya atau Tidak');
        $sheet->setCellValue('A5', 'Proses di Vendor: Ya = material diproses oleh vendor. Jika Ya, kolom Vendor Proses wajib diisi. Lihat sheet "Ref Vendor" untuk daftar kode vendor.');
        ExcelService::applyNoteStyle($spreadsheet, 'A3:M3');
        ExcelService::applyNoteStyle($spreadsheet, 'A4:M4');
        ExcelService::applyNoteStyle($spreadsheet, 'A5:M5');
        $sheet->mergeCells('A3:M3');
        $sheet->mergeCells('A4:M4');
        $sheet->mergeCells('A5:M5');

        $headers = ['Kode *', 'Nama *', 'Deskripsi', 'Tipe *', 'Satuan *', 'Order Method *', 'Harga Standar', 'Qty/Case', 'Min Stock', 'Aktif *', 'Vendor Planning (Kode)', 'Proses di Vendor', 'Vendor Proses (Kode)'];
        foreach ($headers as $i => $h) $sheet->setCellValue(chr(65 + $i) . '6', $h);
        ExcelService::applyHeaderStyle($spreadsheet, 'A6:M6');

        // Sample rows: RM tanpa vendor proses, WIP in-house, FP diproses vendor
        $samples = [
            ['RM-001', 'Baja Plat 3mm',       'Raw material baja', 'RM',  'KG',  'mrp', 15000,  25, 100, 'Ya', 'VND-001', 'Tidak', ''],
            ['WIP-001', 'Rangka Sub-assy',     '',                  'WIP', 'PCS', 'mrp', 120000, 1,  10,  'Ya', '',        'Tidak', ''],
            ['FP-001',  'Meja Besi Industrial','',                  'FP',  'PCS', 'mrp', 850000, 1,  5,   'Ya', '',        'Ya',    'VND-002'],
        ];
        foreach ($samples as $row => $s) {
            $r = $row + 7;
            foreach ($s as $i => $v) $sheet->setCellValue(chr(65 + $i) . "{$r}", $v);
            ExcelService::applyDataStyle($spreadsheet, "A{$r}:M{$r}", $row % 2 === 0);
        }

        foreach (range('A', 'M') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

        // Sheet 2: Vendor reference
        $refSheet = $spreadsheet->createSheet();
        $refSheet->setTitle('Ref Vendor');
        $refHeaders = ['Kode Vendor', 'Nama Vendor'];
        foreach ($refHeaders as $i => $h) $refSheet->setCellValue(chr(65 + $i) . '1', $h);
        $spreadsheet->setActiveSheetIndex(1);
        ExcelService::applyHeaderStyle($spreadsheet, 'A1:B1');

        $vendors = \App\Models\Vendor::where('is_active', true)->orderBy('code')->get();
        foreach ($vendors as $vi => $v) {
            $r = $vi + 2;
            $refSheet->setCellValue("A{$r}", $v->code);
            $refSheet->setCellValue("B{$r}", $v->name);
            ExcelService::applyDataStyle($spreadsheet, "A{$r}:B{$r}", $vi % 2 === 0);
        }
        $refSheet->getColumnDimension('A')->setWidth(18);
        $refSheet->getColumnDimension('B')->setWidth(36);

        $spreadsheet->setActiveSheetIndex(0);
        return ExcelService::download($spreadsheet, 'template_import_material.xlsx');
    }

    public function importExcel(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls']);
        $path = $request->file('file')->store('imports');
        $fullPath = storage_path('app/private/' . $path);

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, false, false);

        // Build vendor lookup: code → id
        $vendorMap = \App\Models\Vendor::pluck('id', 'code')->mapWithKeys(fn($id, $code) => [strtoupper($code) => $id]);

        $imported = 0; $errors = [];
        $allLocations = StorageLocation::all()->groupBy('material_type');
        // Data starts at row index 6 (0-based = row 7)
        foreach (array_slice($rows, 6) as $idx => $row) {
            if (empty($row[0])) continue;
            [$code, $name, $desc, $type, $uom, $orderMethod, $price, $qpc, $minStock, $active, $vendorCode, $prosesVendor, $processVendorCode] = array_pad($row, 13, null);
            $type = strtoupper(trim($type ?? ''));
            if (!in_array($type, ['RM', 'WIP', 'FP'])) {
                $errors[] = "Baris " . ($idx + 7) . ": Tipe '$type' tidak valid (harus RM/WIP/FP).";
                continue;
            }
            $orderMethod = strtolower(trim($orderMethod ?? 'mrp'));
            if (!in_array($orderMethod, ['mrp', 'skm'])) $orderMethod = 'mrp';

            $isProsesVendor = strtolower(trim($prosesVendor ?? 'tidak')) === 'ya';

            // Resolve vendor IDs
            $vendorId = null;
            $processVendorId = null;
            if (!empty($vendorCode)) {
                $vendorId = $vendorMap->get(strtoupper(trim($vendorCode)));
                if (!$vendorId) {
                    $errors[] = "Baris " . ($idx + 7) . ": Kode Vendor Planning '$vendorCode' tidak ditemukan.";
                    continue;
                }
            }
            if ($isProsesVendor) {
                if (empty($processVendorCode)) {
                    $errors[] = "Baris " . ($idx + 7) . ": Kolom 'Vendor Proses (Kode)' wajib diisi jika 'Proses di Vendor' = Ya.";
                    continue;
                }
                $processVendorId = $vendorMap->get(strtoupper(trim($processVendorCode)));
                if (!$processVendorId) {
                    $errors[] = "Baris " . ($idx + 7) . ": Kode Vendor Proses '$processVendorCode' tidak ditemukan.";
                    continue;
                }
            }

            try {
                $mat = Material::updateOrCreate(
                    ['code' => strtoupper(trim($code))],
                    [
                        'name'              => $name,
                        'description'       => $desc ?? null,
                        'type'              => $type,
                        'unit_of_measure'   => strtoupper(trim($uom)),
                        'order_method'      => $orderMethod,
                        'standard_price'    => is_numeric($price) ? $price : 0,
                        'qty_per_case'      => is_numeric($qpc) ? $qpc : 0,
                        'min_stock'         => is_numeric($minStock) ? $minStock : 0,
                        'is_active'         => strtolower(trim($active ?? 'ya')) === 'ya',
                        'vendor_id'         => $vendorId,
                        'process_vendor_id' => $processVendorId,
                    ]
                );
                if ($mat->wasRecentlyCreated) {
                    $locs = ($allLocations->get(null) ?? collect())->merge($allLocations->get($type) ?? collect());
                    foreach ($locs as $loc) {
                        Stock::firstOrCreate(
                            ['material_id' => $mat->id, 'storage_location_id' => $loc->id],
                            ['quantity' => 0]
                        );
                    }
                }
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Baris " . ($idx + 7) . ": " . $e->getMessage();
            }
        }

        \Illuminate\Support\Facades\Storage::delete($path);
        $msg = "Import selesai: {$imported} material berhasil diproses.";
        if ($errors) $msg .= ' Peringatan: ' . implode(' | ', array_slice($errors, 0, 5));
        return redirect()->route('mm.materials.index')->with('success', $msg);
    }

    public function exportPdf(Request $request)
    {
        $query = Material::withSum('stocks', 'quantity');
        if ($request->search) $query->where(fn($q) => $q->where('code','like',"%{$request->search}%")->orWhere('name','like',"%{$request->search}%"));
        if ($request->type)   $query->where('type', $request->type);
        $materials = $query->orderBy('code')->get();
        $filters   = $request->only(['search', 'type', 'low_stock']);
        $pdf = Pdf::loadView('mm.materials.pdf-list', compact('materials', 'filters'))
            ->setPaper('a4', 'landscape');
        return $pdf->stream('materials_'.date('Ymd').'.pdf');
    }
}
