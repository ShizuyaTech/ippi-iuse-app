<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorStock;
use App\Services\ExcelService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class StockController extends Controller
{
    private function buildQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $vendorId = $user->vendor_id;

        $query = VendorStock::with('material')
            ->when($vendorId, fn($q, $v) => $q->where('vendor_id', $v))
            ->where('quantity', '>', 0);

        if ($request->search) {
            $query->whereHas('material', fn($q) => $q
                ->where('code', 'like', "%{$request->search}%")
                ->orWhere('name', 'like', "%{$request->search}%"));
        }
        if ($request->type) {
            $query->whereHas('material', fn($q) => $q->where('type', $request->type));
        }

        return $query;
    }

    public function index(Request $request)
    {
        $stocks = $this->buildQuery($request)
            ->get()
            ->sortBy(fn($s) => [$s->material?->type, $s->material?->code]);

        return view('vendor-portal.stocks.index', compact('stocks'));
    }

    public function printPdf(Request $request)
    {
        $stocks = $this->buildQuery($request)
            ->get()
            ->sortBy(fn($s) => [$s->material?->type, $s->material?->code]);

        $filters = ['search' => $request->search, 'type' => $request->type];

        $pdf = Pdf::loadView('vendor-portal.stocks.pdf', compact('stocks', 'filters'))
            ->setPaper('a4', 'portrait');
        return $pdf->stream('stock-overview-vendor.pdf');
    }

    public function exportExcel(Request $request)
    {
        $stocks = $this->buildQuery($request)
            ->get()
            ->sortBy(fn($s) => [$s->material?->type, $s->material?->code]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stock Vendor');

        // Title
        $sheet->setCellValue('A1', 'STOCK OVERVIEW - VENDOR');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Dicetak: ' . now()->format('d/m/Y H:i'));
        $sheet->mergeCells('A2:E2');
        $sheet->getStyle('A2')->getFont()->setSize(9)->setItalic(true);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Header
        $headers = ['Kode', 'Nama Material', 'Tipe', 'Qty Stok', 'Satuan'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValueByColumnAndRow($i + 1, 4, $h);
        }
        ExcelService::applyHeaderStyle($spreadsheet, 'A4:E4');
        $sheet->getRowDimension(4)->setRowHeight(22);

        // Data
        $row = 5;
        foreach ($stocks as $s) {
            $m = $s->material;
            $sheet->setCellValue("A{$row}", $m?->code);
            $sheet->setCellValue("B{$row}", $m?->name);
            $sheet->setCellValue("C{$row}", $m?->type);
            $sheet->setCellValue("D{$row}", (float) $s->quantity);
            $sheet->setCellValue("E{$row}", $m?->unit_of_measure);
            ExcelService::applyDataStyle($spreadsheet, "A{$row}:E{$row}", $row % 2 !== 0);
            $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $row++;
        }

        // Column widths
        $sheet->getColumnDimension('A')->setWidth(16);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(10);
        $sheet->getColumnDimension('D')->setWidth(14);
        $sheet->getColumnDimension('E')->setWidth(10);

        return ExcelService::download($spreadsheet, 'stock-overview-vendor-' . now()->format('Ymd') . '.xlsx');
    }
}
