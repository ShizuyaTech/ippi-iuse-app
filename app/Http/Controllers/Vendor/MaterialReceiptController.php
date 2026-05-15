<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\VendorMaterialDelivery;
use App\Models\VendorStock;
use App\Models\VendorStockMovement;
use App\Services\ExcelService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class MaterialReceiptController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $receipts = $this->buildQuery($request)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('vendor-portal.material-receipts.index', compact('receipts'));
    }

    private function buildQuery(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $query = VendorMaterialDelivery::with('items.material')
            ->when($user->vendor_id, fn($q, $v) => $q->where('vendor_id', $v));
        if ($request->search) {
            $query->where('vmd_number', 'like', "%{$request->search}%");
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->date_from) {
            $query->whereDate('delivery_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('delivery_date', '<=', $request->date_to);
        }
        return $query;
    }

    public function exportPdf(Request $request)
    {
        $receipts = $this->buildQuery($request)->latest()->get();
        $filters = $request->only(['search', 'status', 'date_from', 'date_to']);
        $pdf = Pdf::loadView('vendor-portal.material-receipts.pdf-list', compact('receipts', 'filters'))
            ->setPaper('a4', 'landscape');
        return $pdf->stream('material-receipts-list.pdf');
    }

    public function exportExcel(Request $request)
    {
        $receipts = $this->buildQuery($request)->latest()->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Material Receipts');

        $sheet->setCellValue('A1', 'DAFTAR KIRIMAN BAHAN DARI IPPI');
        $sheet->setCellValue('A2', 'Dicetak: ' . now()->format('d/m/Y H:i'));
        $sheet->mergeCells('A1:F1');
        $sheet->mergeCells('A2:F2');

        $headers = ['No. VMD', 'Tanggal Kirim', 'No. Kendaraan', 'Driver', 'Jumlah Item', 'Status'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . '4', $h);
        }
        ExcelService::applyHeaderStyle($spreadsheet, 'A4:F4');

        $row = 5;
        foreach ($receipts as $r) {
            $sheet->setCellValue("A{$row}", $r->vmd_number);
            $sheet->setCellValue("B{$row}", $r->delivery_date?->format('d/m/Y') ?? '-');
            $sheet->setCellValue("C{$row}", $r->vehicle_number ?? '-');
            $sheet->setCellValue("D{$row}", $r->driver_name ?? '-');
            $sheet->setCellValue("E{$row}", $r->items->count());
            $sheet->setCellValue("F{$row}", $r->statusLabel());
            $row++;
        }
        ExcelService::applyDataStyle($spreadsheet, "A5:F" . ($row - 1));

        return ExcelService::download($spreadsheet, 'material-receipts-list.xlsx');
    }

    public function show(VendorMaterialDelivery $materialReceipt)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        // Vendor users: only own receipts; internal users: any receipt
        if ($user->vendor_id !== null) {
            abort_if($materialReceipt->vendor_id !== $user->vendor_id, 403);
        }

        $materialReceipt->load('items.material', 'items.storageLocation', 'createdBy');

        return view('vendor-portal.material-receipts.show', compact('materialReceipt'));
    }

    public function printPdf(VendorMaterialDelivery $materialReceipt)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->vendor_id !== null) {
            abort_if($materialReceipt->vendor_id !== $user->vendor_id, 403);
        }
        $materialReceipt->load('items.material', 'items.storageLocation', 'createdBy');
        $pdf = Pdf::loadView('vendor-portal.material-receipts.pdf', compact('materialReceipt'))
            ->setPaper('a4', 'portrait');
        return $pdf->stream("VMD-{$materialReceipt->vmd_number}.pdf");
    }

    public function confirm(Request $request, VendorMaterialDelivery $materialReceipt)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        // Vendor users: only confirm own receipts; internal users: any receipt
        if ($user->vendor_id !== null) {
            abort_if($materialReceipt->vendor_id !== $user->vendor_id, 403);
        }
        abort_if($materialReceipt->status !== 'sent', 422, 'Kiriman ini sudah dikonfirmasi.');

        $request->validate([
            'items'                       => 'required|array',
            'items.*.quantity_confirmed'  => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $materialReceipt, $user) {
            $materialReceipt->load('items.material');

            // VMD created from a GI: IPPI stock was already deducted at GI time
            $skipIppiDeduction = $materialReceipt->goods_issue_id !== null;

            foreach ($materialReceipt->items as $item) {
                $qtyConfirmed = (float) ($request->input("items.{$item->id}.quantity_confirmed") ?? 0);

                // Simpan qty aktual di item
                $item->update(['quantity_confirmed' => $qtyConfirmed]);

                if ($qtyConfirmed <= 0) continue;

                if ($skipIppiDeduction) {
                    // VMD from GI: full qty was deducted at GI time.
                    // Return the difference (qty_sent - qty_confirmed) back to IPPI stock.
                    $qtySent = (float) $item->quantity;
                    $qtyReturn = $qtySent - $qtyConfirmed;
                    if ($qtyReturn > 0) {
                        $ippi_stock = Stock::firstOrCreate(
                            ['material_id' => $item->material_id, 'storage_location_id' => $item->storage_location_id],
                            ['quantity' => 0]
                        );
                        $ippi_stock->increment('quantity', $qtyReturn);
                        $ippi_stock->refresh();

                        StockMovement::create([
                            'material_id'         => $item->material_id,
                            'storage_location_id' => $item->storage_location_id,
                            'movement_type'       => 'GI_REV',
                            'quantity'            => $qtyReturn,
                            'quantity_after'      => $ippi_stock->quantity,
                            'reference_document'  => $materialReceipt->vmd_number,
                            'movement_date'       => $materialReceipt->delivery_date,
                            'created_by'          => $user->id,
                        ]);
                    }
                } else {
                    // Manual VMD (not from GI): deduct IPPI stock now based on confirmed qty
                    $ippi_stock = Stock::firstOrCreate(
                        ['material_id' => $item->material_id, 'storage_location_id' => $item->storage_location_id],
                        ['quantity' => 0]
                    );
                    $ippi_stock->decrement('quantity', $qtyConfirmed);
                    $ippi_stock->refresh();

                    StockMovement::create([
                        'material_id'         => $item->material_id,
                        'storage_location_id' => $item->storage_location_id,
                        'movement_type'       => 'GI',
                        'quantity'            => -$qtyConfirmed,
                        'quantity_after'      => $ippi_stock->quantity,
                        'reference_document'  => $materialReceipt->vmd_number,
                        'movement_date'       => $materialReceipt->delivery_date,
                        'created_by'          => $user->id,
                    ]);
                }

                // 2. Tambah stok vendor
                $vendor_stock = VendorStock::firstOrCreate(
                    ['vendor_id' => $materialReceipt->vendor_id, 'material_id' => $item->material_id],
                    ['quantity' => 0]
                );
                $vendor_stock->increment('quantity', $qtyConfirmed);
                $vendor_stock->refresh();

                VendorStockMovement::create([
                    'vendor_id'          => $materialReceipt->vendor_id,
                    'material_id'        => $item->material_id,
                    'movement_type'      => 'VMD_IN',
                    'quantity'           => $qtyConfirmed,
                    'quantity_after'     => $vendor_stock->quantity,
                    'reference_document' => $materialReceipt->vmd_number,
                    'movement_date'      => $materialReceipt->delivery_date,
                    'created_by'         => $user->id,
                ]);
            }

            $materialReceipt->update([
                'status'       => 'confirmed',
                'confirmed_at' => now(),
                'confirmed_by' => $user->id,
            ]);
        });

        return redirect()->route('vendor.material-receipts.show', $materialReceipt)
            ->with('success', "Penerimaan {$materialReceipt->vmd_number} berhasil dikonfirmasi. Stok vendor diperbarui.");
    }
}

