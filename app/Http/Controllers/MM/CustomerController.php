<?php

namespace App\Http\Controllers\MM;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\ExcelService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', "%{$request->search}%")
                  ->orWhere('name', 'like', "%{$request->search}%");
            });
        }
        if ($request->date_from) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to)   $query->whereDate('created_at', '<=', $request->date_to);
        $customers = $query->latest()->paginate(20)->withQueryString();
        return view('mm.customers.index', compact('customers'));
    }

    public function create()
    {
        return view('mm.customers.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code'           => 'required|string|max:20|unique:customers,code',
            'name'           => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email'          => 'nullable|email|max:255',
            'phone'          => 'nullable|string|max:30',
            'address'        => 'nullable|string',
        ]);
        Customer::create($request->only('code', 'name', 'contact_person', 'email', 'phone', 'address') + ['is_active' => true]);
        return redirect()->route('mm.customers.index')->with('success', 'Customer berhasil dibuat.');
    }

    public function show(Customer $customer)
    {
        return view('mm.customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('mm.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $request->validate([
            'code'           => 'required|string|max:20|unique:customers,code,' . $customer->id,
            'name'           => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'email'          => 'nullable|email|max:255',
            'phone'          => 'nullable|string|max:30',
            'address'        => 'nullable|string',
            'is_active'      => 'nullable|boolean',
        ]);
        $customer->update($request->only('code', 'name', 'contact_person', 'email', 'phone', 'address') + [
            'is_active' => $request->boolean('is_active'),
        ]);
        return redirect()->route('mm.customers.index')->with('success', 'Customer berhasil diperbarui.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('mm.customers.index')->with('success', 'Customer berhasil dihapus.');
    }

    public function exportExcel(Request $request)
    {
        $query = Customer::query();
        if ($request->search) {
            $query->where(fn($q) => $q->where('code', 'like', "%{$request->search}%")->orWhere('name', 'like', "%{$request->search}%"));
        }
        $customers = $query->orderBy('code')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Customers');

        $headers = ['Kode', 'Nama', 'Contact Person', 'Email', 'Telepon', 'Alamat', 'Aktif'];
        foreach ($headers as $i => $h) $sheet->setCellValue(chr(65 + $i) . '1', $h);
        ExcelService::applyHeaderStyle($spreadsheet, 'A1:G1');
        $sheet->getRowDimension(1)->setRowHeight(20);

        foreach ($customers as $row => $c) {
            $r = $row + 2;
            $sheet->setCellValue("A{$r}", $c->code);
            $sheet->setCellValue("B{$r}", $c->name);
            $sheet->setCellValue("C{$r}", $c->contact_person);
            $sheet->setCellValue("D{$r}", $c->email);
            $sheet->setCellValue("E{$r}", $c->phone);
            $sheet->setCellValue("F{$r}", $c->address);
            $sheet->setCellValue("G{$r}", $c->is_active ? 'Ya' : 'Tidak');
            ExcelService::applyDataStyle($spreadsheet, "A{$r}:G{$r}", $row % 2 === 0);
        }
        foreach (range('A', 'G') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        return ExcelService::download($spreadsheet, 'customers_' . date('Ymd') . '.xlsx');
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import');

        $sheet->setCellValue('A1', 'TEMPLATE IMPORT CUSTOMER');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->setCellValue('A2', 'Petunjuk: Isi data mulai baris 5. Jangan ubah header. Kolom bertanda * wajib diisi.');
        ExcelService::applyNoteStyle($spreadsheet, 'A2:G2');
        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A3', 'Aktif: Ya atau Tidak');
        ExcelService::applyNoteStyle($spreadsheet, 'A3:G3');
        $sheet->mergeCells('A3:G3');

        $headers = ['Kode *', 'Nama *', 'Contact Person', 'Email', 'Telepon', 'Alamat', 'Aktif *'];
        foreach ($headers as $i => $h) $sheet->setCellValue(chr(65 + $i) . '4', $h);
        ExcelService::applyHeaderStyle($spreadsheet, 'A4:G4');

        $samples = [
            ['CST-001', 'PT Maju Bersama', 'Andi Wijaya', 'andi@maju.com', '021-5551234', 'Jl. Raya No.1, Jakarta', 'Ya'],
            ['CST-002', 'CV Sejahtera', '', '', '', '', 'Ya'],
        ];
        foreach ($samples as $row => $s) {
            $r = $row + 5;
            foreach ($s as $i => $v) $sheet->setCellValue(chr(65 + $i) . "{$r}", $v);
            ExcelService::applyDataStyle($spreadsheet, "A{$r}:G{$r}", $row % 2 === 0);
        }
        foreach (range('A', 'G') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        return ExcelService::download($spreadsheet, 'template_import_customer.xlsx');
    }

    public function importExcel(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls']);
        $path     = $request->file('file')->store('imports');
        $fullPath = storage_path('app/private/' . $path);

        $spreadsheet = IOFactory::load($fullPath);
        $rows        = $spreadsheet->getActiveSheet()->toArray();

        $imported = 0;
        $errors   = [];
        foreach (array_slice($rows, 4) as $idx => $row) {
            if (empty($row[0])) continue;
            [$code, $name, $contact, $email, $phone, $address, $active] = array_pad($row, 7, null);
            try {
                Customer::updateOrCreate(
                    ['code' => strtoupper(trim($code))],
                    [
                        'name'           => $name,
                        'contact_person' => $contact ?? null,
                        'email'          => $email ?? null,
                        'phone'          => $phone ?? null,
                        'address'        => $address ?? null,
                        'is_active'      => strtolower(trim($active ?? 'ya')) === 'ya',
                    ]
                );
                $imported++;
            } catch (\Exception $e) {
                $errors[] = 'Baris ' . ($idx + 5) . ': ' . $e->getMessage();
            }
        }
        \Illuminate\Support\Facades\Storage::delete($path);
        $msg = "Import selesai: {$imported} customer berhasil diproses.";
        if ($errors) $msg .= ' Peringatan: ' . implode(' | ', array_slice($errors, 0, 5));
        return redirect()->route('mm.customers.index')->with('success', $msg);
    }

    public function exportPdf(Request $request)
    {
        $query = Customer::query();
        if ($request->search) {
            $query->where(fn($q) => $q->where('code', 'like', "%{$request->search}%")->orWhere('name', 'like', "%{$request->search}%"));
        }
        $customers = $query->orderBy('code')->get();
        $filters   = $request->only(['search']);
        $pdf       = Pdf::loadView('mm.customers.pdf-list', compact('customers', 'filters'))
            ->setPaper('a4', 'portrait');
        return $pdf->stream('customers_' . date('Ymd') . '.pdf');
    }
}
