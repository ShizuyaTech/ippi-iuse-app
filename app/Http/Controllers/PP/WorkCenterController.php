<?php

namespace App\Http\Controllers\PP;

use App\Http\Controllers\Controller;
use App\Models\WorkCenter;
use App\Services\ExcelService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class WorkCenterController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkCenter::query();
        if ($request->search)    $query->where(fn($q) => $q->where('code', 'like', "%{$request->search}%")->orWhere('name', 'like', "%{$request->search}%"));
        if ($request->date_from) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to)   $query->whereDate('created_at', '<=', $request->date_to);
        $workCenters = $query->latest()->paginate(20)->withQueryString();
        return view('pp.work-centers.index', compact('workCenters'));
    }

    public function create()
    {
        return view('pp.work-centers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code'              => 'required|string|max:20|unique:work_centers,code',
            'name'              => 'required|string|max:255',
            'description'       => 'nullable|string',
            'capacity_per_hour' => 'required|numeric|min:0',
            'cost_per_hour'     => 'nullable|numeric|min:0',
        ]);
        WorkCenter::create($request->only('code', 'name', 'description', 'capacity_per_hour', 'cost_per_hour') + ['is_active' => true]);
        return redirect()->route('pp.work-centers.index')->with('success', 'Work Center berhasil dibuat.');
    }

    public function show(WorkCenter $workCenter)
    {
        $workCenter->load('routingOperations.routing.material');
        return view('pp.work-centers.show', compact('workCenter'));
    }

    public function edit(WorkCenter $workCenter)
    {
        return view('pp.work-centers.edit', compact('workCenter'));
    }

    public function update(Request $request, WorkCenter $workCenter)
    {
        $request->validate([
            'code'              => 'required|string|max:20|unique:work_centers,code,' . $workCenter->id,
            'name'              => 'required|string|max:255',
            'description'       => 'nullable|string',
            'capacity_per_hour' => 'required|numeric|min:0',
            'cost_per_hour'     => 'nullable|numeric|min:0',
        ]);
        $workCenter->update($request->only('code', 'name', 'description', 'capacity_per_hour', 'cost_per_hour'));
        return redirect()->route('pp.work-centers.index')->with('success', 'Work Center berhasil diperbarui.');
    }

    public function destroy(WorkCenter $workCenter)
    {
        $workCenter->delete();
        return redirect()->route('pp.work-centers.index')->with('success', 'Work Center berhasil dihapus.');
    }

    public function exportExcel()
    {
        $workCenters = WorkCenter::orderBy('code')->get();

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Work Center');

        $headers = ['Kode', 'Nama', 'Deskripsi', 'Kapasitas/Jam', 'Biaya/Jam (Rp)', 'Status'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . '1', $h);
        }
        ExcelService::applyHeaderStyle($spreadsheet, 'A1:F1');
        $sheet->getRowDimension(1)->setRowHeight(20);

        foreach ($workCenters as $ri => $wc) {
            $r = $ri + 2;
            $sheet->setCellValue("A{$r}", $wc->code);
            $sheet->setCellValue("B{$r}", $wc->name);
            $sheet->setCellValue("C{$r}", $wc->description ?? '');
            $sheet->setCellValue("D{$r}", (float) ($wc->capacity_per_hour ?? 0));
            $sheet->setCellValue("E{$r}", (float) ($wc->cost_per_hour ?? 0));
            $sheet->setCellValue("F{$r}", $wc->is_active ? 'Aktif' : 'Nonaktif');
            ExcelService::applyDataStyle($spreadsheet, "A{$r}:F{$r}", $ri % 2 === 0);
        }
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return ExcelService::download($spreadsheet, 'work_center_' . date('Ymd') . '.xlsx');
    }

    public function downloadTemplate()
    {        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Work Center');

        $sheet->setCellValue('A2', 'Petunjuk: Isi data mulai baris 3. Jangan ubah header. Kolom bertanda * wajib diisi. Aktif: Ya atau Tidak.');
        ExcelService::applyNoteStyle($spreadsheet, 'A2:F2');
        $sheet->mergeCells('A2:F2');

        $headers = ['Kode *', 'Nama *', 'Deskripsi', 'Kapasitas per Jam', 'Biaya per Jam (Rp)', 'Aktif *'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . '1', $h);
        }
        ExcelService::applyHeaderStyle($spreadsheet, 'A1:F1');
        $sheet->getRowDimension(1)->setRowHeight(20);

        $samples = [
            ['WC001', 'Mesin Potong A',    'Mesin potong CNC',     8,  50000, 'Ya'],
            ['WC002', 'Line Assembling 1', 'Assembly line manual', 10, 30000, 'Ya'],
            ['WC003', 'Quality Control',   '',                      6,  25000, 'Ya'],
        ];
        foreach ($samples as $ri => $row) {
            $r = $ri + 3;
            foreach ($row as $ci => $val) {
                $sheet->setCellValue(chr(65 + $ci) . $r, $val);
            }
            ExcelService::applyDataStyle($spreadsheet, "A{$r}:F{$r}", $ri % 2 === 0);
        }
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return ExcelService::download($spreadsheet, 'template_work_center.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls']);

        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        $rows        = $spreadsheet->getActiveSheet()->toArray(null, true, false, false);

        $errors  = [];
        $created = 0;

        foreach ($rows as $i => $row) {
            if ($i === 0 || $i === 1) continue; // skip header + note rows
            $code = trim($row[0] ?? '');
            $name = trim($row[1] ?? '');
            if (!$code && !$name) continue;

            if (!$code) { $errors[] = 'Baris ' . ($i + 1) . ': Kode wajib diisi.'; continue; }
            if (!$name) { $errors[] = 'Baris ' . ($i + 1) . ': Nama wajib diisi.'; continue; }

            if (WorkCenter::where('code', $code)->exists()) {
                $errors[] = 'Baris ' . ($i + 1) . ": Kode '{$code}' sudah ada, dilewati.";
                continue;
            }

            WorkCenter::create([
                'code'              => $code,
                'name'              => $name,
                'description'       => trim($row[2] ?? '') ?: null,
                'capacity_per_hour' => is_numeric($row[3] ?? '') ? (float) $row[3] : null,
                'cost_per_hour'     => is_numeric($row[4] ?? '') ? (float) $row[4] : null,
                'is_active'         => strtolower(trim($row[5] ?? 'ya')) === 'ya',
            ]);
            $created++;
        }

        $msg = "Berhasil mengimpor {$created} work center.";
        if ($errors) $msg .= ' ' . count($errors) . ' baris dilewati.';

        return redirect()->route('pp.work-centers.index')
            ->with('success', $msg)
            ->with('import_errors', $errors);
    }

    public function exportPdf(Request $request)
    {
        $query = WorkCenter::query();
        if ($request->search)    $query->where(fn($q) => $q->where('code', 'like', "%{$request->search}%")->orWhere('name', 'like', "%{$request->search}%"));
        if ($request->date_from) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to)   $query->whereDate('created_at', '<=', $request->date_to);
        $workCenters = $query->orderBy('code')->get();

        $filters = $request->only(['search', 'date_from', 'date_to']);

        $pdf = Pdf::loadView('pp.work-centers.pdf-list', compact('workCenters', 'filters'))
            ->setPaper('a4', 'landscape');
        return $pdf->stream('work_centers_' . date('Ymd') . '.pdf');
    }
}
