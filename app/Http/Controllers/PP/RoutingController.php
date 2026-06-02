<?php

namespace App\Http\Controllers\PP;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Routing;
use App\Models\WorkCenter;
use App\Services\ExcelService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class RoutingController extends Controller
{
    public function index(Request $request)
    {
        $query = Routing::with('material');
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('routing_number', 'like', "%{$request->search}%")
                  ->orWhereHas('material', fn($m) => $m->where('name', 'like', "%{$request->search}%"));
            });
        }
        if ($request->date_from) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to)   $query->whereDate('created_at', '<=', $request->date_to);
        $routings = $query->latest()->paginate(20)->withQueryString();
        return view('pp.routings.index', compact('routings'));
    }

    public function create()
    {
        $materials   = Material::where('is_active', true)->orderBy('code')->get();
        $workCenters = WorkCenter::where('is_active', true)->orderBy('code')->get();
        return view('pp.routings.create', compact('materials', 'workCenters'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'material_id'                   => 'required|exists:materials,id',
            'description'                   => 'nullable|string',
            'operations'                    => 'required|array|min:1',
            'operations.*.work_center_id'   => 'required|exists:work_centers,id',
            'operations.*.description'      => 'required|string|max:255',
            'operations.*.setup_time'       => 'required|numeric|min:0',
            'operations.*.standard_time'    => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $routing = Routing::create([
                'routing_number' => Routing::generateNumber(),
                'material_id'    => $request->material_id,
                'description'    => $request->description,
                'status'         => 'active',
            ]);
            foreach ($request->operations as $i => $op) {
                $routing->operations()->create([
                    'operation_number' => ($i + 1) * 10,
                    'work_center_id'   => $op['work_center_id'],
                    'description'      => $op['description'],
                    'setup_time'       => $op['setup_time'],
                    'standard_time'    => $op['standard_time'],
                ]);
            }
        });

        return redirect()->route('pp.routings.index')->with('success', 'Routing berhasil dibuat.');
    }

    public function show(Routing $routing)
    {
        $routing->load('material', 'operations.workCenter');
        return view('pp.routings.show', compact('routing'));
    }

    public function edit(Routing $routing)
    {
        $materials   = Material::where('is_active', true)->orderBy('code')->get();
        $workCenters = WorkCenter::where('is_active', true)->orderBy('code')->get();
        $routing->load('operations.workCenter');
        return view('pp.routings.edit', compact('routing', 'materials', 'workCenters'));
    }

    public function update(Request $request, Routing $routing)
    {
        $request->validate([
            'material_id'                   => 'required|exists:materials,id',
            'operations'                    => 'required|array|min:1',
            'operations.*.work_center_id'   => 'required|exists:work_centers,id',
            'operations.*.description'      => 'required|string|max:255',
            'operations.*.setup_time'       => 'required|numeric|min:0',
            'operations.*.standard_time'    => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $routing) {
            $routing->operations()->delete();
            $routing->update($request->only('material_id', 'description'));
            foreach ($request->operations as $i => $op) {
                $routing->operations()->create([
                    'operation_number' => ($i + 1) * 10,
                    'work_center_id'   => $op['work_center_id'],
                    'description'      => $op['description'],
                    'setup_time'       => $op['setup_time'],
                    'standard_time'    => $op['standard_time'],
                ]);
            }
        });

        return redirect()->route('pp.routings.show', $routing)->with('success', 'Routing berhasil diperbarui.');
    }

    public function destroy(Routing $routing)
    {
        $routing->delete();
        return redirect()->route('pp.routings.index')->with('success', 'Routing berhasil dihapus.');
    }

    public function exportExcel()
    {
        $routings = Routing::with('material', 'operations.workCenter')->orderBy('routing_number')->get();

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Routing');

        $headers = ['No. Routing', 'Kode Material', 'Nama Material', 'Deskripsi', 'No. Operasi', 'Deskripsi Operasi', 'Work Center', 'Setup Time (jam)', 'Std. Time (jam)', 'Status'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . '1', $h);
        }
        ExcelService::applyHeaderStyle($spreadsheet, 'A1:J1');
        $sheet->getRowDimension(1)->setRowHeight(20);

        $r = 2;
        foreach ($routings as $routing) {
            $ops = $routing->operations;
            if ($ops->isEmpty()) {
                $sheet->setCellValue("A{$r}", $routing->routing_number);
                $sheet->setCellValue("B{$r}", $routing->material->code ?? '');
                $sheet->setCellValue("C{$r}", $routing->material->name ?? '');
                $sheet->setCellValue("D{$r}", $routing->description ?? '');
                $sheet->setCellValue("J{$r}", $routing->status);
                ExcelService::applyDataStyle($spreadsheet, "A{$r}:J{$r}", $r % 2 === 0);
                $r++;
                continue;
            }
            foreach ($ops as $oi => $op) {
                if ($oi === 0) {
                    $sheet->setCellValue("A{$r}", $routing->routing_number);
                    $sheet->setCellValue("B{$r}", $routing->material->code ?? '');
                    $sheet->setCellValue("C{$r}", $routing->material->name ?? '');
                    $sheet->setCellValue("D{$r}", $routing->description ?? '');
                    $sheet->setCellValue("J{$r}", $routing->status);
                }
                $sheet->setCellValue("E{$r}", $op->operation_number);
                $sheet->setCellValue("F{$r}", $op->description);
                $sheet->setCellValue("G{$r}", $op->workCenter->code ?? '');
                $sheet->setCellValue("H{$r}", (float) $op->setup_time);
                $sheet->setCellValue("I{$r}", (float) $op->standard_time);
                ExcelService::applyDataStyle($spreadsheet, "A{$r}:J{$r}", $r % 2 === 0);
                $r++;
            }
        }
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return ExcelService::download($spreadsheet, 'routing_' . date('Ymd') . '.xlsx');
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();

        // ── Sheet 1: Routing Data ─────────────────────────────────
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Routing Data');
        $headers = [
            'Kode Material *', 'Deskripsi Routing',
            'Deskripsi Operasi *', 'Kode Work Center *', 'Setup Time (jam)', 'Standard Time (jam) *',
        ];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . '1', $h);
        }
        ExcelService::applyHeaderStyle($spreadsheet, 'A1:F1');
        $sheet->getRowDimension(1)->setRowHeight(20);

        $samples = [
            ['FG001', 'Routing produksi FG001', 'Pemotongan Bahan', 'WC001', 0.5,  2.0],
            ['',      '',                        'Assembling',       'WC002', 0.25, 3.0],
            ['',      '',                        'Quality Check',    'WC003', 0.1,  0.5],
            ['FG002', 'Routing FG002',           'Mixing',           'WC001', 0.3,  1.5],
            ['',      '',                        'Pengemasan',       'WC002', 0.2,  1.0],
        ];
        foreach ($samples as $ri => $row) {
            foreach ($row as $ci => $val) {
                $sheet->setCellValue(chr(65 + $ci) . ($ri + 2), $val);
            }
        }
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ── Sheet 2: Material Reference ───────────────────────────
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Ref Material');
        $sheet2->setCellValue('A1', 'Kode Material');
        $sheet2->setCellValue('B1', 'Nama');
        $sheet2->setCellValue('C1', 'Tipe');
        $spreadsheet->setActiveSheetIndex(1);
        ExcelService::applyHeaderStyle($spreadsheet, 'A1:C1');

        $materials = Material::where('is_active', true)->orderBy('code')->get();
        foreach ($materials as $ri => $m) {
            $sheet2->setCellValue('A' . ($ri + 2), $m->code);
            $sheet2->setCellValue('B' . ($ri + 2), $m->name);
            $sheet2->setCellValue('C' . ($ri + 2), $m->type);
        }
        foreach (range('A', 'C') as $col) {
            $sheet2->getColumnDimension($col)->setAutoSize(true);
        }

        // ── Sheet 3: Work Center Reference ───────────────────────
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Ref Work Center');
        $sheet3->setCellValue('A1', 'Kode Work Center');
        $sheet3->setCellValue('B1', 'Nama');
        $spreadsheet->setActiveSheetIndex(2);
        ExcelService::applyHeaderStyle($spreadsheet, 'A1:B1');

        $workCenters = WorkCenter::where('is_active', true)->orderBy('code')->get();
        foreach ($workCenters as $ri => $wc) {
            $sheet3->setCellValue('A' . ($ri + 2), $wc->code);
            $sheet3->setCellValue('B' . ($ri + 2), $wc->name);
        }
        foreach (range('A', 'B') as $col) {
            $sheet3->getColumnDimension($col)->setAutoSize(true);
        }

        $spreadsheet->setActiveSheetIndex(0);
        return ExcelService::download($spreadsheet, 'template_routing.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls']);

        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        $rows        = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);

        $errors  = [];
        $created = 0;

        // Group rows into routing groups: new group starts when col A (material code) is non-empty
        $groups       = [];
        $currentIndex = -1;

        foreach ($rows as $i => $row) {
            if ($i === 0) continue;
            $matCode = trim($row[0] ?? '');
            $opDesc  = trim($row[2] ?? '');
            if (!$matCode && !$opDesc) continue;

            if ($matCode) {
                $groups[] = [
                    'row'  => $i + 1,
                    'code' => $matCode,
                    'desc' => trim($row[1] ?? ''),
                    'ops'  => [],
                ];
                $currentIndex = count($groups) - 1;
            }

            if ($opDesc && $currentIndex >= 0) {
                $groups[$currentIndex]['ops'][] = [
                    'desc'       => $opDesc,
                    'wc_code'    => trim($row[3] ?? ''),
                    'setup_time' => trim($row[4] ?? '0'),
                    'std_time'   => trim($row[5] ?? ''),
                    'row'        => $i + 1,
                ];
            }
        }

        DB::transaction(function () use ($groups, &$errors, &$created) {
            foreach ($groups as $g) {
                $material = Material::where('code', $g['code'])->first();
                if (!$material) { $errors[] = "Baris {$g['row']}: Kode material '{$g['code']}' tidak ditemukan."; continue; }
                if (empty($g['ops'])) { $errors[] = "Baris {$g['row']}: Routing '{$g['code']}' tidak memiliki operasi."; continue; }

                $opsData = [];
                $opsOk   = true;
                foreach ($g['ops'] as $opIdx => $op) {
                    $wc = WorkCenter::where('code', $op['wc_code'])->first();
                    if (!$wc) { $errors[] = "Baris {$op['row']}: Kode Work Center '{$op['wc_code']}' tidak ditemukan."; $opsOk = false; continue; }
                    if (!is_numeric($op['std_time']) || (float)$op['std_time'] < 0) { $errors[] = "Baris {$op['row']}: Standard Time tidak valid."; $opsOk = false; continue; }
                    $opsData[] = [
                        'operation_number' => ($opIdx + 1) * 10,
                        'work_center_id'   => $wc->id,
                        'description'      => $op['desc'],
                        'setup_time'       => is_numeric($op['setup_time']) ? (float) $op['setup_time'] : 0,
                        'standard_time'    => (float) $op['std_time'],
                    ];
                }
                if (!$opsOk) continue;

                $routing = Routing::create([
                    'routing_number' => Routing::generateNumber(),
                    'material_id'    => $material->id,
                    'description'    => $g['desc'] ?: null,
                    'status'         => 'active',
                ]);
                foreach ($opsData as $op) {
                    $routing->operations()->create($op);
                }
                $created++;
            }
        });

        $msg = "Berhasil mengimpor {$created} routing.";
        if ($errors) $msg .= ' ' . count($errors) . ' masalah ditemukan.';

        return redirect()->route('pp.routings.index')
            ->with('success', $msg)
            ->with('import_errors', $errors);
    }

    public function exportPdf(Request $request)
    {
        $query = Routing::with('material')->withCount('operations');
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('routing_number', 'like', "%{$request->search}%")
                  ->orWhereHas('material', fn($m) => $m->where('name', 'like', "%{$request->search}%"));
            });
        }
        if ($request->date_from) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to)   $query->whereDate('created_at', '<=', $request->date_to);
        $routings = $query->orderBy('routing_number')->get();

        $filters = $request->only(['search', 'date_from', 'date_to']);

        $pdf = Pdf::loadView('pp.routings.pdf-list', compact('routings', 'filters'))
            ->setPaper('a4', 'landscape');
        return $pdf->stream('routings_' . date('Ymd') . '.pdf');
    }
}
