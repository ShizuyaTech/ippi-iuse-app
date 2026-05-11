<?php

namespace App\Http\Controllers\MM;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Services\ExcelService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = Vendor::query();
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', "%{$request->search}%")
                  ->orWhere('name', 'like', "%{$request->search}%");
            });
        }
        if ($request->date_from) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to)   $query->whereDate('created_at', '<=', $request->date_to);
        $vendors = $query->latest()->paginate(20)->withQueryString();
        return view('mm.vendors.index', compact('vendors'));
    }

    public function create()
    {
        return view('mm.vendors.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code'           => 'required|string|max:20|unique:vendors,code',
            'name'           => 'required|string|max:255',
            'vendor_type'    => 'required|in:coil_center,process,general',
            'contact_person' => 'nullable|string|max:255',
            'email'          => 'nullable|email|max:255',
            'phone'          => 'nullable|string|max:20',
            'address'        => 'nullable|string',
        ]);
        Vendor::create($request->only('code', 'name', 'vendor_type', 'contact_person', 'email', 'phone', 'address') + ['is_active' => true]);
        return redirect()->route('mm.vendors.index')->with('success', 'Vendor berhasil dibuat.');
    }

    public function show(Vendor $vendor)
    {
        $vendor->load('purchaseOrders');
        return view('mm.vendors.show', compact('vendor'));
    }

    public function edit(Vendor $vendor)
    {
        return view('mm.vendors.edit', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $request->validate([
            'code'           => 'required|string|max:20|unique:vendors,code,' . $vendor->id,
            'name'           => 'required|string|max:255',
            'vendor_type'    => 'required|in:coil_center,process,general',
            'contact_person' => 'nullable|string|max:255',
            'email'          => 'nullable|email|max:255',
            'phone'          => 'nullable|string|max:20',
            'address'        => 'nullable|string',
        ]);
        $vendor->update($request->only('code', 'name', 'vendor_type', 'contact_person', 'email', 'phone', 'address'));
        return redirect()->route('mm.vendors.index')->with('success', 'Vendor berhasil diperbarui.');
    }

    public function destroy(Vendor $vendor)
    {
        $vendor->delete();
        return redirect()->route('mm.vendors.index')->with('success', 'Vendor berhasil dihapus.');
    }

    public function exportExcel(Request $request)
    {
        $query = Vendor::query();
        if ($request->search) $query->where(fn($q) => $q->where('code','like',"%{$request->search}%")->orWhere('name','like',"%{$request->search}%"));
        $vendors = $query->orderBy('code')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Vendors');

        $headers = ['Kode','Nama','Tipe Vendor','Contact Person','Email','Telepon','Alamat','Aktif'];
        foreach ($headers as $i => $h) $sheet->setCellValue(chr(65+$i).'1', $h);
        ExcelService::applyHeaderStyle($spreadsheet, 'A1:H1');
        $sheet->getRowDimension(1)->setRowHeight(20);

        foreach ($vendors as $row => $v) {
            $r = $row + 2;
            $sheet->setCellValue("A{$r}", $v->code);
            $sheet->setCellValue("B{$r}", $v->name);
            $sheet->setCellValue("C{$r}", $v->vendor_type ?? 'general');
            $sheet->setCellValue("D{$r}", $v->contact_person);
            $sheet->setCellValue("E{$r}", $v->email);
            $sheet->setCellValue("F{$r}", $v->phone);
            $sheet->setCellValue("G{$r}", $v->address);
            $sheet->setCellValue("H{$r}", $v->is_active ? 'Ya' : 'Tidak');
            ExcelService::applyDataStyle($spreadsheet, "A{$r}:H{$r}", $row % 2 === 0);
        }
        foreach (range('A','H') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        return ExcelService::download($spreadsheet, 'vendors_'.date('Ymd').'.xlsx');
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import');

        $sheet->setCellValue('A1', 'TEMPLATE IMPORT VENDOR');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->setCellValue('A2', 'Petunjuk: Isi data mulai baris 5. Jangan ubah header. Kolom bertanda * wajib diisi.');
        ExcelService::applyNoteStyle($spreadsheet, 'A2:H2');
        $sheet->mergeCells('A2:H2');
        $sheet->setCellValue('A3', 'Tipe Vendor: coil_center | process | general  |  Aktif: Ya atau Tidak');
        ExcelService::applyNoteStyle($spreadsheet, 'A3:H3');
        $sheet->mergeCells('A3:H3');

        $headers = ['Kode *','Nama *','Tipe Vendor *','Contact Person','Email','Telepon','Alamat','Aktif *'];
        foreach ($headers as $i => $h) $sheet->setCellValue(chr(65+$i).'4', $h);
        ExcelService::applyHeaderStyle($spreadsheet, 'A4:H4');

        $samples = [
            ['VND-001','PT Sumber Makmur','coil_center','Budi Santoso','budi@sumber.com','021-5551234','Jl. Industri No.1, Jakarta','Ya'],
            ['VND-002','CV Proses Jaya','process','','','','','Ya'],
            ['VND-003','UD Umum Sejahtera','general','','','','','Ya'],
        ];
        foreach ($samples as $row => $s) {
            $r = $row + 5;
            foreach ($s as $i => $v) $sheet->setCellValue(chr(65+$i)."{$r}", $v);
            ExcelService::applyDataStyle($spreadsheet, "A{$r}:H{$r}", $row % 2 === 0);
        }
        foreach (range('A','H') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        return ExcelService::download($spreadsheet, 'template_import_vendor.xlsx');
    }

    public function importExcel(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls']);
        $path = $request->file('file')->store('imports');
        $fullPath = storage_path('app/private/'.$path);

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
        $rows = $spreadsheet->getActiveSheet()->toArray();

        $imported = 0; $errors = [];
        foreach (array_slice($rows, 4) as $idx => $row) {
            if (empty($row[0])) continue;
            [$code, $name, $vendorType, $contact, $email, $phone, $address, $active] = array_pad($row, 8, null);
            $vendorType = in_array(trim((string) ($vendorType ?? '')), ['coil_center', 'process', 'general'])
                ? trim($vendorType)
                : 'general';
            try {
                Vendor::updateOrCreate(
                    ['code' => strtoupper(trim($code))],
                    [
                        'name'           => $name,
                        'vendor_type'    => $vendorType,
                        'contact_person' => $contact ?? null,
                        'email'          => $email ?? null,
                        'phone'          => $phone ?? null,
                        'address'        => $address ?? null,
                        'is_active'      => strtolower(trim($active ?? 'ya')) === 'ya',
                    ]
                );
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Baris ".($idx+5).": ".$e->getMessage();
            }
        }
        \Illuminate\Support\Facades\Storage::delete($path);
        $msg = "Import selesai: {$imported} vendor berhasil diproses.";
        if ($errors) $msg .= ' Peringatan: '.implode(' | ', array_slice($errors, 0, 5));
        return redirect()->route('mm.vendors.index')->with('success', $msg);
    }

    public function exportPdf(Request $request)
    {
        $query = Vendor::query();
        if ($request->search) $query->where(fn($q) => $q->where('code','like',"%{$request->search}%")->orWhere('name','like',"%{$request->search}%"));
        $vendors = $query->orderBy('code')->get();
        $filters = $request->only(['search']);
        $pdf = Pdf::loadView('mm.vendors.pdf-list', compact('vendors', 'filters'))
            ->setPaper('a4', 'portrait');
        return $pdf->stream('vendors_'.date('Ymd').'.pdf');
    }
}
