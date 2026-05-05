<?php

namespace App\Http\Controllers\PP;

use App\Http\Controllers\Controller;
use App\Models\Bom;
use App\Models\Material;
use App\Services\ExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class BomController extends Controller
{
    public function index(Request $request)
    {
        $query = Bom::with('material');
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('bom_number', 'like', "%{$request->search}%")
                  ->orWhereHas('material', fn($m) => $m->where('name', 'like', "%{$request->search}%"));
            });
        }
        if ($request->date_from) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to)   $query->whereDate('created_at', '<=', $request->date_to);
        $boms = $query->latest()->paginate(20)->withQueryString();
        return view('pp.boms.index', compact('boms'));
    }

    public function create()
    {
        $materials = Material::where('is_active', true)->orderBy('code')->get();
        return view('pp.boms.create', compact('materials'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'material_id'          => 'required|exists:materials,id',
            'base_quantity'        => 'required|numeric|min:0.001',
            'valid_from'           => 'required|date',
            'valid_to'             => 'nullable|date|after:valid_from',
            'description'          => 'nullable|string',
            'items'                => 'required|array|min:1',
            'items.*.material_id'  => 'required|exists:materials,id',
            'items.*.quantity'     => 'required|numeric|min:0.001',
            'items.*.unit'         => 'required|string|max:10',
        ]);

        DB::transaction(function () use ($request) {
            $bom = Bom::create([
                'bom_number'    => Bom::generateNumber(),
                'material_id'   => $request->material_id,
                'base_quantity' => $request->base_quantity,
                'valid_from'    => $request->valid_from,
                'valid_to'      => $request->valid_to,
                'description'   => $request->description,
                'status'        => 'active',
            ]);
            foreach ($request->items as $item) {
                $bom->items()->create([
                    'material_id' => $item['material_id'],
                    'quantity'    => $item['quantity'],
                    'unit'        => $item['unit'],
                    'notes'       => $item['notes'] ?? null,
                ]);
            }
        });

        return redirect()->route('pp.boms.index')->with('success', 'BOM berhasil dibuat.');
    }

    public function show(Bom $bom)
    {
        $bom->load('material', 'items.material');
        return view('pp.boms.show', compact('bom'));
    }

    public function edit(Bom $bom)
    {
        $materials = Material::where('is_active', true)->orderBy('code')->get();
        $bom->load('items.material');
        return view('pp.boms.edit', compact('bom', 'materials'));
    }

    public function update(Request $request, Bom $bom)
    {
        $request->validate([
            'material_id'          => 'required|exists:materials,id',
            'base_quantity'        => 'required|numeric|min:0.001',
            'valid_from'           => 'required|date',
            'valid_to'             => 'nullable|date|after:valid_from',
            'items'                => 'required|array|min:1',
            'items.*.material_id'  => 'required|exists:materials,id',
            'items.*.quantity'     => 'required|numeric|min:0.001',
            'items.*.unit'         => 'required|string|max:10',
        ]);

        DB::transaction(function () use ($request, $bom) {
            $bom->items()->delete();
            $bom->update($request->only('material_id', 'base_quantity', 'valid_from', 'valid_to', 'description'));
            foreach ($request->items as $item) {
                $bom->items()->create([
                    'material_id' => $item['material_id'],
                    'quantity'    => $item['quantity'],
                    'unit'        => $item['unit'],
                    'notes'       => $item['notes'] ?? null,
                ]);
            }
        });

        return redirect()->route('pp.boms.show', $bom)->with('success', 'BOM berhasil diperbarui.');
    }

    public function destroy(Bom $bom)
    {
        $bom->delete();
        return redirect()->route('pp.boms.index')->with('success', 'BOM berhasil dihapus.');
    }

    public function exportExcel()
    {
        $boms = Bom::with('material', 'items.material')->orderBy('bom_number')->get();

        $spreadsheet = new Spreadsheet();

        // ── Sheet 1: BOM Data (same format as import template) ────
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('BOM Data');

        $headers = [
            'Kode Material FP *', 'Base Qty *', 'Valid From * (YYYY-MM-DD)',
            'Valid To (YYYY-MM-DD)', 'Deskripsi BOM',
            'Kode Komponen *', 'Qty Komponen *', 'UoM Komponen *', 'Catatan Komponen',
        ];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . '1', $h);
        }
        ExcelService::applyHeaderStyle($spreadsheet, 'A1:I1');
        $sheet->getRowDimension(1)->setRowHeight(20);

        $r = 2;
        foreach ($boms as $bom) {
            $items    = $bom->items;
            $firstRow = $r;

            if ($items->isEmpty()) {
                // BOM with no components — write header row only
                $sheet->setCellValue("A{$r}", $bom->material->code ?? '');
                $sheet->setCellValue("B{$r}", (float) $bom->base_quantity);
                $sheet->setCellValue("C{$r}", $bom->valid_from?->format('Y-m-d') ?? '');
                $sheet->setCellValue("D{$r}", $bom->valid_to?->format('Y-m-d') ?? '');
                $sheet->setCellValue("E{$r}", $bom->description ?? '');
                ExcelService::applyDataStyle($spreadsheet, "A{$r}:I{$r}", $r % 2 === 0);
                $r++;
                continue;
            }

            foreach ($items as $idx => $item) {
                if ($idx === 0) {
                    // First row of this BOM: fill FP header columns
                    $sheet->setCellValue("A{$r}", $bom->material->code ?? '');
                    $sheet->setCellValue("B{$r}", (float) $bom->base_quantity);
                    $sheet->setCellValue("C{$r}", $bom->valid_from?->format('Y-m-d') ?? '');
                    $sheet->setCellValue("D{$r}", $bom->valid_to?->format('Y-m-d') ?? '');
                    $sheet->setCellValue("E{$r}", $bom->description ?? '');
                }
                // Component columns (always filled)
                $sheet->setCellValue("F{$r}", $item->material->code ?? '');
                $sheet->setCellValue("G{$r}", (float) $item->quantity);
                $sheet->setCellValue("H{$r}", $item->unit ?? '');
                $sheet->setCellValue("I{$r}", $item->notes ?? '');
                ExcelService::applyDataStyle($spreadsheet, "A{$r}:I{$r}", $r % 2 === 0);
                $r++;
            }

            // Light separator row between BOMs
            $sheet->getStyle("A{$firstRow}:I" . ($r - 1))
                ->getBorders()->getBottom()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM)
                ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF93C5FD'));
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ── Sheet 2: Material Reference ───────────────────────────
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Ref Material');
        $sheet2->setCellValue('A1', 'Kode Material');
        $sheet2->setCellValue('B1', 'Nama');
        $sheet2->setCellValue('C1', 'Tipe');
        $sheet2->setCellValue('D1', 'UoM');
        $spreadsheet->setActiveSheetIndex(1);
        ExcelService::applyHeaderStyle($spreadsheet, 'A1:D1');

        $materials = Material::where('is_active', true)->orderBy('code')->get();
        foreach ($materials as $ri => $m) {
            $sheet2->setCellValue('A' . ($ri + 2), $m->code);
            $sheet2->setCellValue('B' . ($ri + 2), $m->name);
            $sheet2->setCellValue('C' . ($ri + 2), $m->type);
            $sheet2->setCellValue('D' . ($ri + 2), $m->unit_of_measure);
        }
        foreach (range('A', 'D') as $col) {
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);
        return ExcelService::download($spreadsheet, 'bom_export_' . date('Ymd') . '.xlsx');
    }

    public function downloadTemplate()
    {        $spreadsheet = new Spreadsheet();

        // ── Sheet 1: BOM Data ──────────────────────────────────────
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('BOM Data');
        $headers = [
            'Kode Material FP *', 'Base Qty *', 'Valid From * (YYYY-MM-DD)',
            'Valid To (YYYY-MM-DD)', 'Deskripsi BOM',
            'Kode Komponen *', 'Qty Komponen *', 'UoM Komponen *', 'Catatan Komponen',
        ];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . '1', $h);
        }
        ExcelService::applyHeaderStyle($spreadsheet, 'A1:I1');
        $sheet->getRowDimension(1)->setRowHeight(20);

        $today = date('Y-m-d');
        $samples = [
            ['FG001', 1,  $today, '', 'BOM FG001', 'RM001', 2,   'KG',  ''],
            ['',      '',  '',    '', '',           'RM002', 0.5, 'PCS', 'Gasket'],
            ['FG002', 10, $today, '', 'BOM FG002', 'RM003', 5,   'LTR', ''],
            ['',      '',  '',    '', '',           'RM001', 1,   'KG',  ''],
        ];
        foreach ($samples as $ri => $row) {
            foreach ($row as $ci => $val) {
                $sheet->setCellValue(chr(65 + $ci) . ($ri + 2), $val);
            }
        }
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ── Sheet 2: Material Reference ───────────────────────────
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Ref Material');
        $sheet2->setCellValue('A1', 'Kode Material');
        $sheet2->setCellValue('B1', 'Nama');
        $sheet2->setCellValue('C1', 'Tipe');
        $sheet2->setCellValue('D1', 'UoM');
        $spreadsheet->setActiveSheetIndex(1);
        ExcelService::applyHeaderStyle($spreadsheet, 'A1:D1');

        $materials = Material::where('is_active', true)->orderBy('code')->get();
        foreach ($materials as $ri => $m) {
            $sheet2->setCellValue('A' . ($ri + 2), $m->code);
            $sheet2->setCellValue('B' . ($ri + 2), $m->name);
            $sheet2->setCellValue('C' . ($ri + 2), $m->type);
            $sheet2->setCellValue('D' . ($ri + 2), $m->unit_of_measure);
        }
        foreach (range('A', 'D') as $col) {
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);
        return ExcelService::download($spreadsheet, 'template_bom.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls']);

        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        $rows        = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);

        $errors  = [];
        $created = 0;

        // Group rows into BOM groups: new group starts when col A (FP code) is non-empty
        $groups       = [];
        $currentIndex = -1;

        foreach ($rows as $i => $row) {
            if ($i === 0) continue;
            $fpCode   = trim($row[0] ?? '');
            $compCode = trim($row[5] ?? '');
            if (!$fpCode && !$compCode) continue;

            if ($fpCode) {
                $groups[] = [
                    'row'        => $i + 1,
                    'fp_code'    => $fpCode,
                    'base_qty'   => trim($row[1] ?? ''),
                    'valid_from' => trim($row[2] ?? ''),
                    'valid_to'   => trim($row[3] ?? ''),
                    'desc'       => trim($row[4] ?? ''),
                    'items'      => [],
                ];
                $currentIndex = count($groups) - 1;
            }

            if ($compCode && $currentIndex >= 0) {
                $groups[$currentIndex]['items'][] = [
                    'code' => $compCode,
                    'qty'  => trim($row[6] ?? ''),
                    'uom'  => trim($row[7] ?? ''),
                    'note' => trim($row[8] ?? ''),
                    'row'  => $i + 1,
                ];
            }
        }

        DB::transaction(function () use ($groups, &$errors, &$created) {
            foreach ($groups as $g) {
                $fpMat = Material::where('code', $g['fp_code'])->first();
                if (!$fpMat) { $errors[] = "Baris {$g['row']}: Kode material FP '{$g['fp_code']}' tidak ditemukan."; continue; }
                if (!is_numeric($g['base_qty']) || (float)$g['base_qty'] <= 0) { $errors[] = "Baris {$g['row']}: Base Qty tidak valid."; continue; }
                if (!$g['valid_from']) { $errors[] = "Baris {$g['row']}: Valid From wajib diisi."; continue; }
                if (empty($g['items'])) { $errors[] = "Baris {$g['row']}: BOM '{$g['fp_code']}' tidak memiliki komponen."; continue; }

                $itemsData = [];
                $itemOk    = true;
                foreach ($g['items'] as $item) {
                    $compMat = Material::where('code', $item['code'])->first();
                    if (!$compMat) { $errors[] = "Baris {$item['row']}: Kode komponen '{$item['code']}' tidak ditemukan."; $itemOk = false; continue; }
                    if (!is_numeric($item['qty']) || (float)$item['qty'] <= 0) { $errors[] = "Baris {$item['row']}: Qty komponen tidak valid."; $itemOk = false; continue; }
                    $itemsData[] = [
                        'material_id' => $compMat->id,
                        'quantity'    => (float) $item['qty'],
                        'unit'        => $item['uom'] ?: ($compMat->unit_of_measure ?? 'PCS'),
                        'notes'       => $item['note'] ?: null,
                    ];
                }
                if (!$itemOk) continue;

                $bom = Bom::create([
                    'bom_number'    => Bom::generateNumber(),
                    'material_id'   => $fpMat->id,
                    'base_quantity' => (float) $g['base_qty'],
                    'valid_from'    => $g['valid_from'],
                    'valid_to'      => $g['valid_to'] ?: null,
                    'description'   => $g['desc'] ?: null,
                    'status'        => 'active',
                ]);
                foreach ($itemsData as $item) {
                    $bom->items()->create($item);
                }
                $created++;
            }
        });

        $msg = "Berhasil mengimpor {$created} BOM.";
        if ($errors) $msg .= ' ' . count($errors) . ' masalah ditemukan.';

        return redirect()->route('pp.boms.index')
            ->with('success', $msg)
            ->with('import_errors', $errors);
    }
}
