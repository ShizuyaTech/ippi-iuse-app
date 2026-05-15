<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Services\ExcelService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class PurchaseOrderController extends Controller
{
    private function vendorScopeId(): ?int
    {
        /** @var User $user */
        $user = Auth::user();
        return $user->vendor_id;
    }

    public function index(Request $request)
    {
        $query = PurchaseOrder::with('vendor', 'items')
            ->when($this->vendorScopeId(), fn($q, $v) => $q->where('vendor_id', $v))
            ->whereIn('status', ['approved', 'partially_received']);

        if ($request->search) {
            $query->where('po_number', 'like', "%{$request->search}%");
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->date_from) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }

        $pos = $query->latest()->paginate(20)->withQueryString();

        return view('vendor-portal.purchase-orders.index', compact('pos'));
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        // Guard: only own vendor's POs (skip for internal users)
        if ($this->vendorScopeId() !== null) {
            abort_if($purchaseOrder->vendor_id !== $this->vendorScopeId(), 403);
        }

        $purchaseOrder->load('vendor', 'items.material', 'storageLocation');

        return view('vendor-portal.purchase-orders.show', compact('purchaseOrder'));
    }

    public function printPdf(PurchaseOrder $purchaseOrder)
    {
        if ($this->vendorScopeId() !== null) {
            abort_if($purchaseOrder->vendor_id !== $this->vendorScopeId(), 403);
        }
        $purchaseOrder->load('vendor', 'items.material', 'storageLocation');
        $pdf = Pdf::loadView('vendor-portal.purchase-orders.pdf', compact('purchaseOrder'))
            ->setPaper('a4', 'portrait');
        return $pdf->stream("PO-{$purchaseOrder->po_number}.pdf");
    }

    private function buildQuery(Request $request)
    {
        $query = PurchaseOrder::with('vendor', 'items')
            ->when($this->vendorScopeId(), fn($q, $v) => $q->where('vendor_id', $v))
            ->whereIn('status', ['approved', 'partially_received', 'completed']);

        if ($request->search) {
            $query->where('po_number', 'like', "%{$request->search}%");
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->date_from) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }
        return $query;
    }

    public function exportPdf(Request $request)
    {
        $pos = $this->buildQuery($request)->latest()->get();
        $filters = $request->only(['search', 'status', 'date_from', 'date_to']);
        $pdf = Pdf::loadView('vendor-portal.purchase-orders.pdf-list', compact('pos', 'filters'))
            ->setPaper('a4', 'landscape');
        return $pdf->stream('purchase-orders-list.pdf');
    }

    public function exportExcel(Request $request)
    {
        $pos = $this->buildQuery($request)->latest()->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Purchase Orders');

        $sheet->setCellValue('A1', 'DAFTAR PURCHASE ORDER – VENDOR');
        $sheet->setCellValue('A2', 'Dicetak: ' . now()->format('d/m/Y H:i'));
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');

        $headers = ['No. PO', 'Tanggal Order', 'Vendor', 'Lokasi', 'Status', 'Total Item', 'Est. Pengiriman'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . '4', $h);
        }
        ExcelService::applyHeaderStyle($spreadsheet, 'A4:G4');

        $row = 5;
        foreach ($pos as $po) {
            $sheet->setCellValue("A{$row}", $po->po_number);
            $sheet->setCellValue("B{$row}", $po->order_date?->format('d/m/Y') ?? '-');
            $sheet->setCellValue("C{$row}", $po->vendor?->name ?? '-');
            $sheet->setCellValue("D{$row}", $po->storageLocation?->name ?? '-');
            $sheet->setCellValue("E{$row}", ucfirst(str_replace('_', ' ', $po->status)));
            $sheet->setCellValue("F{$row}", $po->items->count());
            $sheet->setCellValue("G{$row}", $po->expected_delivery_date?->format('d/m/Y') ?? '-');
            $row++;
        }
        ExcelService::applyDataStyle($spreadsheet, "A5:G" . ($row - 1));

        return ExcelService::download($spreadsheet, 'purchase-orders-list.xlsx');
    }
}
