<?php

namespace App\Http\Controllers\MM;

use App\Http\Controllers\Controller;
use App\Models\StorageLocation;
use App\Services\ExcelService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class StorageLocationController extends Controller
{
    public function index(Request $request)
    {
        $query = StorageLocation::query();
        if ($request->search)    $query->where(fn($q) => $q->where('code', 'like', "%{$request->search}%")->orWhere('name', 'like', "%{$request->search}%"));
        if ($request->date_from) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to)   $query->whereDate('created_at', '<=', $request->date_to);
        $locations = $query->latest()->paginate(20)->withQueryString();
        return view('mm.storage-locations.index', compact('locations'));
    }

    public function create()
    {
        return view('mm.storage-locations.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code'          => 'required|string|max:10|unique:storage_locations,code',
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'material_type' => 'nullable|in:RM,WIP,FP',
            'is_scrap'      => 'boolean',
        ]);
        StorageLocation::create([
            ...$request->only('code', 'name', 'description', 'material_type'),
            'is_scrap' => $request->boolean('is_scrap'),
        ]);
        return redirect()->route('mm.storage-locations.index')->with('success', 'Storage Location berhasil dibuat.');
    }

    public function show(StorageLocation $storageLocation)
    {
        $storageLocation->load('stocks.material');
        return view('mm.storage-locations.show', compact('storageLocation'));
    }

    public function edit(StorageLocation $storageLocation)
    {
        return view('mm.storage-locations.edit', compact('storageLocation'));
    }

    public function update(Request $request, StorageLocation $storageLocation)
    {
        $request->validate([
            'code'          => 'required|string|max:10|unique:storage_locations,code,' . $storageLocation->id,
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'material_type' => 'nullable|in:RM,WIP,FP',
            'is_scrap'      => 'boolean',
        ]);
        $storageLocation->update([
            ...$request->only('code', 'name', 'description', 'material_type'),
            'is_scrap' => $request->boolean('is_scrap'),
        ]);
        return redirect()->route('mm.storage-locations.index')->with('success', 'Storage Location berhasil diperbarui.');
    }

    public function destroy(StorageLocation $storageLocation)
    {
        $storageLocation->delete();
        return redirect()->route('mm.storage-locations.index')->with('success', 'Storage Location berhasil dihapus.');
    }

    public function exportExcel()
    {
        $locations = StorageLocation::orderBy('code')->get();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Storage Locations');

        $headers = ['Kode','Nama','Deskripsi','Tipe Material'];
        foreach ($headers as $i => $h) $sheet->setCellValue(chr(65+$i).'1', $h);
        ExcelService::applyHeaderStyle($spreadsheet, 'A1:D1');
        $sheet->getRowDimension(1)->setRowHeight(20);

        foreach ($locations as $row => $loc) {
            $r = $row + 2;
            $sheet->setCellValue("A{$r}", $loc->code);
            $sheet->setCellValue("B{$r}", $loc->name);
            $sheet->setCellValue("C{$r}", $loc->description);
            $sheet->setCellValue("D{$r}", $loc->material_type ?? '');
            ExcelService::applyDataStyle($spreadsheet, "A{$r}:D{$r}", $row % 2 === 0);
        }
        foreach (['A','B','C','D'] as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        return ExcelService::download($spreadsheet, 'storage_locations_'.date('Ymd').'.xlsx');
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import');

        $sheet->setCellValue('A1', 'TEMPLATE IMPORT STORAGE LOCATION');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->setCellValue('A2', 'Petunjuk: Isi data mulai baris 5. Jangan ubah header. Kolom bertanda * wajib diisi.');
        ExcelService::applyNoteStyle($spreadsheet, 'A2:D2');
        $sheet->mergeCells('A2:D2');
        $sheet->setCellValue('A3', 'Tipe Material: RM = Raw Material | WIP = Work In Progress | FP = Finished Product | (kosongkan jika gudang umum)');
        ExcelService::applyNoteStyle($spreadsheet, 'A3:D3');
        $sheet->mergeCells('A3:D3');

        $headers = ['Kode *','Nama *','Deskripsi','Tipe Material'];
        foreach ($headers as $i => $h) $sheet->setCellValue(chr(65+$i).'4', $h);
        ExcelService::applyHeaderStyle($spreadsheet, 'A4:D4');

        $samples = [
            ['I101','Gudang IRM',     'Penyimpanan Raw Material',   'RM'],
            ['I100','Gudang WIP',     'Work-in-Process',            'WIP'],
            ['I107','Gudang Logistik','Penyimpanan Finished Product','FP'],
            ['I999','Gudang Scrap',   'Area material reject/scrap', ''],
        ];
        foreach ($samples as $row => $s) {
            $r = $row + 5;
            foreach ($s as $i => $v) $sheet->setCellValue(chr(65+$i)."{$r}", $v);
            ExcelService::applyDataStyle($spreadsheet, "A{$r}:D{$r}", $row % 2 === 0);
        }
        foreach (['A','B','C','D'] as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        return ExcelService::download($spreadsheet, 'template_import_storage_location.xlsx');
    }

    public function importExcel(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls']);
        $path = $request->file('file')->store('imports');
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load(storage_path('app/private/'.$path));
        $rows = $spreadsheet->getActiveSheet()->toArray();

        $imported = 0; $errors = [];
        foreach (array_slice($rows, 4) as $idx => $row) {
            if (empty($row[0])) continue;
            [$code, $name, $desc, $materialType] = array_pad($row, 4, null);
            $materialType = strtoupper(trim((string) ($materialType ?? '')));
            $materialType = in_array($materialType, ['RM', 'WIP', 'FP']) ? $materialType : null;
            try {
                StorageLocation::updateOrCreate(
                    ['code' => strtoupper(trim($code))],
                    ['name' => $name, 'description' => $desc ?? null, 'material_type' => $materialType]
                );
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Baris ".($idx+5).": ".$e->getMessage();
            }
        }
        \Illuminate\Support\Facades\Storage::delete($path);
        $msg = "Import selesai: {$imported} lokasi berhasil diproses.";
        if ($errors) $msg .= ' Peringatan: '.implode(' | ', array_slice($errors, 0, 5));
        return redirect()->route('mm.storage-locations.index')->with('success', $msg);
    }

    public function exportPdf()
    {
        $locations = StorageLocation::orderBy('code')->get();
        $pdf = Pdf::loadView('mm.storage-locations.pdf-list', compact('locations'))
            ->setPaper('a4', 'portrait');
        return $pdf->stream('storage_locations_'.date('Ymd').'.pdf');
    }
}
