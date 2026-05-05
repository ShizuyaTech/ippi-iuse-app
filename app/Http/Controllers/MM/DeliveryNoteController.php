<?php

namespace App\Http\Controllers\MM;

use App\Http\Controllers\Controller;
use App\Models\DeliveryNote;
use App\Services\ExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class DeliveryNoteController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->buildFilteredQuery($request)
            ->withCount('items');

        $deliveryNotes = $query->latest()->paginate(25)->withQueryString();
        $vendors       = \App\Models\Vendor::orderBy('name')->get(['id', 'name']);

        return view('mm.delivery-notes.index', compact('deliveryNotes', 'vendors'));
    }

    public function exportExcel(Request $request)
    {
        $deliveryNotes = $this->buildFilteredQuery($request)
            ->withCount('items')
            ->latest()
            ->limit(5000)
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Delivery Notes');

        $headers = ['No SJ', 'Vendor', 'No PO', 'Est Pengiriman', 'Items', 'Status', 'Source', 'Ref VPO'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        $row = 2;
        foreach ($deliveryNotes as $dn) {
            $source = $dn->source_type === 'vendor_production_order' ? 'AUTO VPO' : 'MANUAL';
            $sheet->setCellValue('A' . $row, $dn->dn_number);
            $sheet->setCellValue('B' . $row, $dn->vendor?->name ?? '-');
            $sheet->setCellValue('C' . $row, $dn->purchaseOrder?->po_number ?? '-');
            $sheet->setCellValue('D' . $row, $dn->estimated_delivery_date?->format('Y-m-d') ?? '-');
            $sheet->setCellValue('E' . $row, (int) $dn->items_count);
            $sheet->setCellValue('F' . $row, $dn->statusLabel());
            $sheet->setCellValue('G' . $row, $source);
            $sheet->setCellValue('H' . $row, $dn->sourceVendorProductionOrder?->order_number ?? '-');
            $row++;
        }

        ExcelService::applyHeaderStyle($spreadsheet, 'A1:H1');
        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        if ($row > 2) {
            for ($dataRow = 2; $dataRow < $row; $dataRow++) {
                ExcelService::applyDataStyle($spreadsheet, 'A' . $dataRow . ':H' . $dataRow, $dataRow % 2 !== 0);
            }
        }

        return ExcelService::download($spreadsheet, 'delivery-notes-' . now()->format('Ymd-His') . '.xlsx');
    }

    public function show(DeliveryNote $deliveryNote)
    {
        $deliveryNote->load('purchaseOrder.vendor', 'vendor', 'goodsReceipt', 'items.purchaseOrderItem.material', 'createdBy', 'sourceVendorProductionOrder');

        return view('mm.delivery-notes.show', compact('deliveryNote'));
    }

    public function updateQty(Request $request, DeliveryNote $deliveryNote)
    {
        abort_if($deliveryNote->status === 'received' || $deliveryNote->status === 'cancelled', 403, 'Surat Jalan ini tidak dapat diubah.');

        $request->validate([
            'items'            => ['required', 'array'],
            'items.*.id'       => ['required', 'exists:delivery_note_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($request, $deliveryNote) {
            foreach ($request->items as $itemData) {
                $deliveryNote->items()->where('id', $itemData['id'])->update([
                    'quantity' => $itemData['quantity'],
                ]);
            }
        });

        return back()->with('success', 'Qty surat jalan berhasil diperbarui.');
    }

    public function confirm(DeliveryNote $deliveryNote)
    {
        abort_if($deliveryNote->status !== 'pending', 422, 'Hanya Surat Jalan berstatus pending yang dapat dikonfirmasi.');

        $deliveryNote->update(['status' => 'confirmed']);

        return redirect()->route('mm.delivery-notes.show', $deliveryNote)
            ->with('success', "Surat Jalan {$deliveryNote->dn_number} berhasil dikonfirmasi.");
    }

    public function receive(DeliveryNote $deliveryNote)
    {
        abort_if($deliveryNote->status !== 'confirmed', 422, 'Konfirmasi surat jalan terlebih dahulu.');

        $deliveryNote->update(['status' => 'received']);

        return redirect()->route('mm.delivery-notes.show', $deliveryNote)
            ->with('success', "Surat Jalan {$deliveryNote->dn_number} ditandai sudah diterima. Silakan buat GR.");
    }

    private function buildFilteredQuery(Request $request)
    {
        $query = DeliveryNote::with('purchaseOrder', 'vendor', 'sourceVendorProductionOrder', 'goodsReceipt');

        if ($request->search) {
            $query->where(fn($q) => $q
                ->where('dn_number', 'like', "%{$request->search}%")
                ->orWhereHas('vendor', fn($v) => $v->where('name', 'like', "%{$request->search}%"))
                ->orWhereHas('purchaseOrder', fn($p) => $p->where('po_number', 'like', "%{$request->search}%")));
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->vendor_id) {
            $query->where('vendor_id', $request->vendor_id);
        }
        if ($request->source_type) {
            if ($request->source_type === 'manual') {
                $query->whereNull('source_type');
            } else {
                $query->where('source_type', $request->source_type);
            }
        }
        if ($request->gr_status === 'created') {
            $query->has('goodsReceipt');
        }
        if ($request->gr_status === 'pending') {
            $query->doesntHave('goodsReceipt');
        }

        return $query;
    }
}
