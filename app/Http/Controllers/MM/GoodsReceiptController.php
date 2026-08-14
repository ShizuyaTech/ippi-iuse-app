<?php

namespace App\Http\Controllers\MM;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StorageLocation;
use App\Models\VendorStock;
use App\Models\VendorStockMovement;
use App\Services\ExcelService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class GoodsReceiptController extends Controller
{
    public function index(Request $request)
    {
        $query = GoodsReceipt::with('purchaseOrder.vendor', 'vendor', 'storageLocation');
        if ($request->search)      $query->where(fn($q) => $q->where('gr_number', 'like', "%{$request->search}%")->orWhereHas('purchaseOrder', fn($p) => $p->where('po_number', 'like', "%{$request->search}%")));
        if ($request->date_from)   $query->whereDate('receipt_date', '>=', $request->date_from);
        if ($request->date_to)     $query->whereDate('receipt_date', '<=', $request->date_to);
        if ($request->f_gr_number) $query->where('gr_number', 'like', "%{$request->f_gr_number}%");
        if ($request->f_po_number) $query->whereHas('purchaseOrder', fn($q) => $q->where('po_number', 'like', "%{$request->f_po_number}%"));
        if ($request->f_vendor)    $query->whereHas('purchaseOrder.vendor', fn($q) => $q->where('name', 'like', "%{$request->f_vendor}%"));
        if ($request->vendor_id)   $query->whereHas('purchaseOrder', fn($q) => $q->where('vendor_id', $request->vendor_id));
        if ($request->f_location)  $query->whereHas('storageLocation', fn($q) => $q->where('name', 'like', "%{$request->f_location}%")->orWhere('code', 'like', "%{$request->f_location}%"));
        if ($request->location_id)  $query->where('storage_location_id', $request->location_id);
        if ($request->f_status)    $query->where('status', $request->f_status);
        $receipts  = $query->latest()->paginate(20)->withQueryString();
        $locations = StorageLocation::orderBy('name')->get();
        $vendors   = \App\Models\Vendor::orderBy('name')->get();

        return view('mm.goods-receipts.index', compact('receipts', 'locations', 'vendors'));
    }

    public function create(Request $request)
    {
        // Support coming from Surat Jalan (DN) page
        $deliveryNote = null;
        $dnQtyMap     = [];
        if ($request->dn_id) {
            $deliveryNote = \App\Models\DeliveryNote::with('items')->findOrFail($request->dn_id);
            abort_if($deliveryNote->status !== 'received', 422, 'Surat Jalan harus berstatus Diterima untuk dibuat GR.');

            $existingGr = GoodsReceipt::where('delivery_note_id', $deliveryNote->id)->first();
            if ($existingGr) {
                return redirect()->route('mm.goods-receipts.show', $existingGr)
                    ->with('success', 'Surat Jalan ini sudah memiliki Goods Receipt.');
            }

            // If po_id not explicitly set, use DN's PO
            if (!$request->po_id) {
                $request->merge(['po_id' => $deliveryNote->purchase_order_id]);
            }
            $dnQtyMap = $deliveryNote->items->pluck('quantity', 'purchase_order_item_id')->toArray();
        }

        $pos = PurchaseOrder::with('vendor')
            ->whereIn('status', ['approved', 'partially_received'])
            ->orderBy('order_date')
            ->get();
        $locations  = StorageLocation::all();
        $selectedPo = $request->po_id ? PurchaseOrder::with('items.material', 'storageLocation')->find($request->po_id) : null;
        return view('mm.goods-receipts.create', compact('pos', 'locations', 'selectedPo', 'deliveryNote', 'dnQtyMap'));
    }

    public function createNonPo()
    {
        $materials = \App\Models\Material::where('is_active', true)->orderBy('type')->orderBy('code')->get();
        $locations = StorageLocation::orderBy('code')->get();
        $vendors   = \App\Models\Vendor::where('is_active', true)->orderBy('name')->get();
        return view('mm.goods-receipts.create-non-po', compact('materials', 'locations', 'vendors'));
    }

    public function storeNonPo(Request $request)
    {
        $request->validate([
            'vendor_id'             => 'required|exists:vendors,id',
            'receipt_date'          => 'required|date',
            'storage_location_id'   => 'required|exists:storage_locations,id',
            'items'                 => 'required|array|min:1',
            'items.*.material_id'   => 'required|exists:materials,id',
            'items.*.quantity'      => 'required|numeric|min:0.001',
            'items.*.packing_note'  => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($request) {
            $gr = GoodsReceipt::create([
                'gr_number'           => GoodsReceipt::generateNumber(),
                'purchase_order_id'   => null,
                'vendor_id'           => $request->vendor_id,
                'receipt_date'        => $request->receipt_date,
                'storage_location_id' => $request->storage_location_id,
                'status'              => 'posted',
                'notes'               => $request->notes,
                'created_by'          => Auth::id(),
            ]);

            foreach ($request->items as $row) {
                if (($row['quantity'] ?? 0) <= 0) continue;

                $gr->items()->create([
                    'purchase_order_item_id' => null,
                    'material_id'            => $row['material_id'],
                    'quantity_received'      => $row['quantity'],
                    'packing_note'           => $row['packing_note'] ?? null,
                ]);

                $stock = Stock::firstOrCreate(
                    ['material_id' => $row['material_id'], 'storage_location_id' => $request->storage_location_id],
                    ['quantity' => 0]
                );
                $stock->increment('quantity', $row['quantity']);
                $stock->refresh();

                StockMovement::create([
                    'material_id'         => $row['material_id'],
                    'storage_location_id' => $request->storage_location_id,
                    'movement_type'       => 'GR',
                    'quantity'            => $row['quantity'],
                    'quantity_after'      => $stock->quantity,
                    'reference_document'  => $gr->gr_number,
                    'movement_date'       => $request->receipt_date,
                    'created_by'          => Auth::id(),
                ]);
            }
        });

        return redirect()->route('mm.goods-receipts.create-non-po')->with('success', 'GR Non-PO berhasil diposting.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'purchase_order_id'    => 'required|exists:purchase_orders,id',
            'dn_id'                => 'nullable|exists:delivery_notes,id',
            'receipt_date'         => 'required|date',
            'storage_location_id'  => 'required|exists:storage_locations,id',
            'items'                => 'required|array|min:1',
            'items.*.po_item_id'   => 'required|exists:purchase_order_items,id',
            'items.*.quantity'     => 'required|numeric|min:0',
            'items.*.packing_note' => 'nullable|string|max:100',
        ]);

        $hasQty = collect($request->items)->filter(fn($i) => ($i['quantity'] ?? 0) > 0)->isNotEmpty();
        if (!$hasQty) {
            return back()->withErrors(['items' => 'Minimal satu item harus memiliki quantity yang diterima (> 0).'])->withInput();
        }

        $grId = null;
        DB::transaction(function () use ($request, &$grId) {
            $deliveryNote = null;
            $dnQtyMap = [];

            if ($request->dn_id) {
                $deliveryNote = \App\Models\DeliveryNote::with('items')
                    ->lockForUpdate()
                    ->findOrFail($request->dn_id);

                if ($deliveryNote->status !== 'received') {
                    throw ValidationException::withMessages([
                        'dn_id' => 'Surat Jalan harus berstatus Diterima untuk dibuat GR.',
                    ]);
                }

                if ((int) $deliveryNote->purchase_order_id !== (int) $request->purchase_order_id) {
                    throw ValidationException::withMessages([
                        'dn_id' => 'Surat Jalan tidak sesuai dengan Purchase Order yang dipilih.',
                    ]);
                }

                $alreadyHasGr = GoodsReceipt::where('delivery_note_id', $deliveryNote->id)
                    ->lockForUpdate()
                    ->exists();

                if ($alreadyHasGr) {
                    throw ValidationException::withMessages([
                        'dn_id' => 'Surat Jalan ini sudah memiliki Goods Receipt.',
                    ]);
                }

                $dnQtyMap = $deliveryNote->items
                    ->groupBy('purchase_order_item_id')
                    ->map(fn($rows) => (float) $rows->sum('quantity'))
                    ->toArray();
            }

            $gr = GoodsReceipt::create([
                'gr_number'           => GoodsReceipt::generateNumber(),
                'purchase_order_id'   => $request->purchase_order_id,
                'delivery_note_id'    => $deliveryNote?->id,
                'receipt_date'        => $request->receipt_date,
                'storage_location_id' => $request->storage_location_id,
                'status'              => 'posted',
                'notes'               => $request->notes,
                'created_by'          => Auth::id(),
            ]);
            $grId = $gr->id;

            $po = PurchaseOrder::with('items')->find($request->purchase_order_id);

            // Group quantities per po_item for stock aggregation
            $qtyPerPoItem = [];
            foreach ($request->items as $row) {
                if (($row['quantity'] ?? 0) <= 0) continue;
                $qtyPerPoItem[$row['po_item_id']] = ($qtyPerPoItem[$row['po_item_id']] ?? 0) + $row['quantity'];
            }

            if ($deliveryNote) {
                $dnItemIds = array_keys($dnQtyMap);
                $postedItemIds = array_keys($qtyPerPoItem);

                sort($dnItemIds);
                sort($postedItemIds);

                if ($dnItemIds !== $postedItemIds) {
                    throw ValidationException::withMessages([
                        'items' => 'Item Goods Receipt harus sama persis dengan item pada Surat Jalan.',
                    ]);
                }

                foreach ($dnQtyMap as $poItemId => $dnQty) {
                    $postedQty = (float) ($qtyPerPoItem[$poItemId] ?? 0);
                    if ($postedQty <= 0) {
                        throw ValidationException::withMessages([
                            'items' => 'Qty Goods Receipt harus diisi untuk semua item pada Surat Jalan.',
                        ]);
                    }

                    if (abs($postedQty - (float) $dnQty) > 0.001) {
                        throw ValidationException::withMessages([
                            'items' => 'Qty Goods Receipt harus sama dengan qty pada Surat Jalan untuk setiap item.',
                        ]);
                    }
                }
            }

            // Create one GR item per case row (preserving packing_note per row)
            foreach ($request->items as $row) {
                if (($row['quantity'] ?? 0) <= 0) continue;

                $poItem = $po->items->find($row['po_item_id']);
                $gr->items()->create([
                    'purchase_order_item_id' => $poItem->id,
                    'material_id'            => $poItem->material_id,
                    'quantity_received'      => $row['quantity'],
                    'packing_note'           => $row['packing_note'] ?? null,
                ]);
            }

            // Update PO items + stock once per po_item (aggregated)
            foreach ($qtyPerPoItem as $poItemId => $totalQty) {
                $poItem = $po->items->find($poItemId);
                $poItem->increment('quantity_received', $totalQty);

                $stock = Stock::firstOrCreate(
                    ['material_id' => $poItem->material_id, 'storage_location_id' => $request->storage_location_id],
                    ['quantity' => 0]
                );
                $stock->increment('quantity', $totalQty);
                $stock->refresh();

                StockMovement::create([
                    'material_id'          => $poItem->material_id,
                    'storage_location_id'  => $request->storage_location_id,
                    'movement_type'        => 'GR',
                    'quantity'             => $totalQty,
                    'quantity_after'       => $stock->quantity,
                    'reference_document'   => $gr->gr_number,
                    'movement_date'        => $request->receipt_date,
                    'created_by'           => Auth::id(),
                ]);
            }

            // Update PO status
            $po->refresh();
            $allReceived = $po->items->every(fn($i) => $i->quantity_received >= $i->quantity);
            $anyReceived = $po->items->some(fn($i) => $i->quantity_received > 0);
            if ($allReceived) {
                $po->update(['status' => 'received']);
            } elseif ($anyReceived) {
                $po->update(['status' => 'partially_received']);
            }

            // Sync SKM status if this PO was generated from a SKM
            if ($po->skm_order_id) {
                $po->skmOrder->syncReceivingStatus();
            }

            // Auto-update status Surat Jalan (DeliveryNote) terkait PO ini
            // Jika GR dibuat tanpa dn_id: update semua SJ pending/confirmed milik PO ini → received
            // Jika GR dibuat dengan dn_id: pastikan SJ tersebut juga ter-mark received
            \App\Models\DeliveryNote::where('purchase_order_id', $po->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->update(['status' => 'received']);

            // For process vendor POs: decrement vendor stock (GI_OUT) per material received
            $po->loadMissing('vendor');
            if ($po->vendor?->isProcessVendor()) {
                foreach ($qtyPerPoItem as $poItemId => $totalQty) {
                    $poItem = $po->items->find($poItemId);
                    if (!$poItem) continue;

                    $vendorStock = VendorStock::firstOrCreate(
                        ['vendor_id' => $po->vendor_id, 'material_id' => $poItem->material_id],
                        ['quantity' => 0]
                    );
                    $vendorStock->decrement('quantity', $totalQty);
                    $vendorStock->refresh();

                    VendorStockMovement::create([
                        'vendor_id'          => $po->vendor_id,
                        'material_id'        => $poItem->material_id,
                        'movement_type'      => 'GI_OUT',
                        'quantity'           => -$totalQty,
                        'quantity_after'     => $vendorStock->quantity,
                        'reference_document' => $gr->gr_number,
                        'movement_date'      => $request->receipt_date,
                        'created_by'         => Auth::id(),
                    ]);
                }
            }
        });

        return redirect()->route('mm.goods-receipts.create')->with('success', 'Goods Receipt ' . GoodsReceipt::find($grId)?->gr_number . ' berhasil diposting.');
    }

    public function show(GoodsReceipt $goodsReceipt)
    {
        $goodsReceipt->load('purchaseOrder.vendor', 'vendor', 'deliveryNote', 'items.material', 'storageLocation', 'createdBy');
        return view('mm.goods-receipts.show', compact('goodsReceipt'));
    }

    public function destroy(GoodsReceipt $goodsReceipt)
    {
        DB::transaction(function () use ($goodsReceipt) {
            $goodsReceipt->load('items.purchaseOrderItem');

            // Group total qty per po_item for stock reversal
            $qtyPerPoItem = [];
            foreach ($goodsReceipt->items as $item) {
                $qtyPerPoItem[$item->purchase_order_item_id] = ($qtyPerPoItem[$item->purchase_order_item_id] ?? 0) + $item->quantity_received;
            }

            foreach ($qtyPerPoItem as $poItemId => $totalQty) {
                $poItem = $goodsReceipt->items->where('purchase_order_item_id', $poItemId)->first()?->purchaseOrderItem;
                if (!$poItem) continue;

                // Reverse stock
                $stock = Stock::where('material_id', $poItem->material_id)
                    ->where('storage_location_id', $goodsReceipt->storage_location_id)
                    ->first();
                if ($stock) {
                    $stock->decrement('quantity', $totalQty);
                    $stock->refresh();
                    StockMovement::create([
                        'material_id'         => $poItem->material_id,
                        'storage_location_id' => $goodsReceipt->storage_location_id,
                        'movement_type'       => 'GR_REV',
                        'quantity'            => -$totalQty,
                        'quantity_after'      => $stock->quantity,
                        'reference_document'  => $goodsReceipt->gr_number,
                        'movement_date'       => now()->toDateString(),
                        'created_by'          => Auth::id(),
                    ]);
                }

                // Restore PO item received qty
                $poItem->decrement('quantity_received', $totalQty);
            }

            // Restore PO status
            $po = $goodsReceipt->purchaseOrder()->with('items')->first();
            if ($po) {
                $po->refresh();
                $anyReceived = $po->items->some(fn($i) => $i->quantity_received > 0);
                $po->update(['status' => $anyReceived ? 'partially_received' : 'approved']);

                // Sync SKM status if this PO came from a SKM
                if ($po->skm_order_id) {
                    $po->skmOrder->syncReceivingStatus();
                }
            }

            $goodsReceipt->items()->delete();
            $goodsReceipt->delete();
        });

        return redirect()->route('mm.goods-receipts.index')->with('success', 'Goods Receipt berhasil dihapus dan stok dibalik.');
    }

    public function edit(GoodsReceipt $goodsReceipt)
    {
        $goodsReceipt->load('items.purchaseOrderItem.material', 'items.material', 'storageLocation', 'purchaseOrder.vendor');
        return view('mm.goods-receipts.edit', compact('goodsReceipt'));
    }

    public function update(Request $request, GoodsReceipt $goodsReceipt)
    {
        $request->validate([
            'receipt_date'         => 'required|date',
            'notes'                => 'nullable|string',
            'items'                => 'required|array',
            'items.*.packing_note' => 'nullable|string|max:100',
        ]);

        DB::transaction(function () use ($request, $goodsReceipt) {
            $goodsReceipt->update([
                'receipt_date' => $request->receipt_date,
                'notes'        => $request->notes,
            ]);

            foreach ($request->items as $itemId => $data) {
                $goodsReceipt->items()->where('id', $itemId)->update([
                    'packing_note' => $data['packing_note'] ?? null,
                ]);
            }
        });

        return redirect()->route('mm.goods-receipts.show', $goodsReceipt)
            ->with('success', 'Goods Receipt berhasil diperbarui.');
    }

    public function exportExcel(Request $request)
    {
        $query = GoodsReceipt::with('purchaseOrder.vendor', 'storageLocation', 'items.material');
        if ($request->search)      $query->where('gr_number', 'like', "%{$request->search}%");
        if ($request->date_from)   $query->whereDate('receipt_date', '>=', $request->date_from);
        if ($request->date_to)     $query->whereDate('receipt_date', '<=', $request->date_to);
        $receipts = $query->orderBy('id', 'desc')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Goods Receipts');

        $headers = ['No. GR','No. PO','Vendor','Tgl Terima','Lokasi','Status','Kode Material','Nama Material','Total Qty Diterima','UoM','Deskripsi','Catatan GR'];
        foreach ($headers as $i => $h) $sheet->setCellValue(chr(65+$i).'1', $h);
        ExcelService::applyHeaderStyle($spreadsheet, 'A1:L1');
        $sheet->getRowDimension(1)->setRowHeight(20);

        $r = 2;
        foreach ($receipts as $gr) {
            // Aggregate total qty per material (collapse per-case rows)
            $grouped = $gr->items->groupBy('material_id');
            if ($grouped->isEmpty()) {
                $sheet->setCellValue("A{$r}", $gr->gr_number);
                $sheet->setCellValue("D{$r}", $gr->receipt_date->format('d/m/Y'));
                $sheet->setCellValue("F{$r}", $gr->status);
                $sheet->setCellValue("L{$r}", $gr->notes ?? '');
                ExcelService::applyDataStyle($spreadsheet, "A{$r}:L{$r}", $r % 2 === 0);
                $r++;
                continue;
            }
            foreach ($grouped as $matId => $rows) {
                $material = $rows->first()->material;
                $totalQty = $rows->sum(fn($i) => (float) $i->quantity_received);
                $sheet->setCellValue("A{$r}", $gr->gr_number);
                $sheet->setCellValue("B{$r}", $gr->purchaseOrder->po_number ?? '-');
                $sheet->setCellValue("C{$r}", $gr->purchaseOrder->vendor->name ?? $gr->vendor->name ?? '-');
                $sheet->setCellValue("D{$r}", $gr->receipt_date->format('d/m/Y'));
                $sheet->setCellValue("E{$r}", $gr->storageLocation->code ?? '-');
                $sheet->setCellValue("F{$r}", $gr->status);
                $sheet->setCellValue("G{$r}", $material->code ?? '');
                $sheet->setCellValue("H{$r}", $material->name ?? '');
                $sheet->setCellValue("I{$r}", $totalQty);
                $sheet->setCellValue("J{$r}", $material->unit_of_measure ?? '');
                $sheet->setCellValue("K{$r}", $material->description ?? '');
                $sheet->setCellValue("L{$r}", $gr->notes ?? '');
                ExcelService::applyDataStyle($spreadsheet, "A{$r}:L{$r}", $r % 2 === 0);
                $r++;
            }
        }
        foreach (range('A','L') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        return ExcelService::download($spreadsheet, 'goods_receipts_'.date('Ymd').'.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $query = GoodsReceipt::with('purchaseOrder.vendor', 'storageLocation');
        if ($request->search)    $query->where('gr_number', 'like', "%{$request->search}%");
        if ($request->date_from) $query->whereDate('receipt_date', '>=', $request->date_from);
        if ($request->date_to)   $query->whereDate('receipt_date', '<=', $request->date_to);
        $receipts = $query->orderBy('id', 'desc')->get();
        $filters  = $request->only(['search', 'date_from', 'date_to']);
        $pdf = Pdf::loadView('mm.goods-receipts.pdf-list', compact('receipts', 'filters'))
            ->setPaper('a4', 'landscape');
        return $pdf->stream('goods_receipts_'.date('Ymd').'.pdf');
    }
}
