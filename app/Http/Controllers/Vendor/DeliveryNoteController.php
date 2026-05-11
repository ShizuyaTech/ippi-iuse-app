<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\DeliveryNote;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use App\Models\VendorStock;
use App\Models\VendorStockMovement;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use App\Services\ExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryNoteController extends Controller
{
    private function vendorScopeId(): ?int
    {
        /** @var User $user */
        $user = Auth::user();
        return $user->vendor_id;
    }

    public function index(Request $request)
    {
        $query = DeliveryNote::with('purchaseOrder')
            ->when($this->vendorScopeId(), fn($q, $v) => $q->where('vendor_id', $v));

        if ($request->search) {
            $query->where(fn($q) => $q
                ->where('dn_number', 'like', "%{$request->search}%")
                ->orWhereHas('purchaseOrder', fn($p) => $p->where('po_number', 'like', "%{$request->search}%")));
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->date_from) {
            $query->whereDate('estimated_delivery_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('estimated_delivery_date', '<=', $request->date_to);
        }

        $deliveryNotes = $query->latest()->paginate(20)->withQueryString();

        return view('vendor-portal.delivery-notes.index', compact('deliveryNotes'));
    }

    public function create(Request $request)
    {
        // Only actual vendors can create SJ — internal users are read-only
        abort_unless(Auth::user()->isVendor(), 403, 'Hanya vendor yang dapat membuat Surat Jalan.');

        $pos = PurchaseOrder::where('vendor_id', $this->vendorScopeId())
            ->whereIn('status', ['approved', 'partially_received'])
            ->get();

        $selectedPo = $request->po_id
            ? PurchaseOrder::where('vendor_id', $this->vendorScopeId())
                ->with('items.material')
                ->findOrFail($request->po_id)
            : null;

        return view('vendor-portal.delivery-notes.create', compact('pos', 'selectedPo'));
    }

    public function store(Request $request)
    {
        // Only actual vendors can create SJ
        abort_unless(Auth::user()->isVendor(), 403, 'Hanya vendor yang dapat membuat Surat Jalan.');

        $request->validate([
            'purchase_order_id'        => ['required', 'exists:purchase_orders,id'],
            'estimated_delivery_date'  => ['required', 'date', 'after_or_equal:today'],
            'vehicle_number'           => ['nullable', 'string', 'max:50'],
            'driver_name'              => ['nullable', 'string', 'max:100'],
            'notes'                    => ['nullable', 'string'],
            'items'                    => ['required', 'array', 'min:1'],
            'items.*.po_item_id'       => ['required', 'exists:purchase_order_items,id'],
            'items.*.quantity'         => ['required', 'numeric', 'min:0.001'],
        ], [
            'items.required' => 'Pilih minimal satu item yang akan dikirim (centang checkbox).',
            'items.min'      => 'Pilih minimal satu item yang akan dikirim (centang checkbox).',
            'items.*.quantity.min' => 'Qty harus lebih dari 0 untuk setiap item yang dipilih.',
        ]);

        $po = PurchaseOrder::where('vendor_id', $this->vendorScopeId())
            ->whereIn('status', ['approved', 'partially_received'])
            ->findOrFail($request->purchase_order_id);

        /** @var User $user */
        $user = Auth::user();
        $isProcessVendor = $user->vendor?->vendor_type === 'process';

        DB::transaction(function () use ($request, $po, $user, $isProcessVendor) {
            $poItems = PurchaseOrderItem::where('purchase_order_id', $po->id)
                ->get()
                ->keyBy('id');

            $requestedQtyByItem = [];
            foreach ($request->items as $item) {
                $poItemId = (int) $item['po_item_id'];
                $qty = (float) ($item['quantity'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                if (!isset($poItems[$poItemId])) {
                    throw ValidationException::withMessages([
                        'items' => 'Terdapat item yang tidak sesuai dengan Purchase Order terpilih.',
                    ]);
                }

                $requestedQtyByItem[$poItemId] = ($requestedQtyByItem[$poItemId] ?? 0) + $qty;
            }

            foreach ($requestedQtyByItem as $poItemId => $requestedQty) {
                $poItem = $poItems[$poItemId];
                $remainingPoQty = max(0, (float) $poItem->quantity - (float) $poItem->quantity_received);

                $alreadyPlannedInSj = (float) DB::table('delivery_note_items as dni')
                    ->join('delivery_notes as dn', 'dn.id', '=', 'dni.delivery_note_id')
                    ->where('dni.purchase_order_item_id', $poItemId)
                    ->where('dn.status', '!=', 'cancelled')
                    ->sum('dni.quantity');

                $availableQty = max(0, $remainingPoQty - $alreadyPlannedInSj);
                if ($requestedQty > $availableQty + 0.001) {
                    throw ValidationException::withMessages([
                        'items' => "Qty untuk item PO #{$poItemId} melebihi sisa alokasi yang tersedia.",
                    ]);
                }
            }

            // Validasi stok vendor untuk vendor process
            if ($isProcessVendor) {
                $requestedByMaterial = [];
                foreach ($request->items as $item) {
                    $qty = (float) ($item['quantity'] ?? 0);
                    if ($qty <= 0) continue;
                    $poItem = $poItems[(int) $item['po_item_id']] ?? null;
                    if (!$poItem) continue;
                    $requestedByMaterial[$poItem->material_id] =
                        ($requestedByMaterial[$poItem->material_id] ?? 0) + $qty;
                }
                foreach ($requestedByMaterial as $materialId => $totalQty) {
                    $stock = VendorStock::where('vendor_id', $this->vendorScopeId())
                        ->where('material_id', $materialId)
                        ->first();
                    if (!$stock || $stock->quantity < $totalQty - 0.001) {
                        throw ValidationException::withMessages([
                            'items' => 'Stok vendor tidak mencukupi untuk salah satu material yang diminta.',
                        ]);
                    }
                }
            }

            $dn = DeliveryNote::create([
                'dn_number'               => DeliveryNote::generateNumber(),
                'purchase_order_id'       => $po->id,
                'vendor_id'               => $this->vendorScopeId(),
                'estimated_delivery_date' => $request->estimated_delivery_date,
                'vehicle_number'          => $request->vehicle_number,
                'driver_name'             => $request->driver_name,
                'notes'                   => $request->notes,
                'status'                  => 'pending',
                'source_type'             => null,
                'source_id'               => null,
                'created_by'              => $user->id,
            ]);

            foreach ($request->items as $item) {
                if (($item['quantity'] ?? 0) > 0) {
                    $dn->items()->create([
                        'purchase_order_item_id' => $item['po_item_id'],
                        'quantity'               => $item['quantity'],
                        'notes'                  => $item['notes'] ?? null,
                    ]);
                }
            }

        });

        $label = $isProcessVendor ? 'Good Issue' : 'Surat Jalan';
        return redirect()->route('vendor.delivery-notes.index')
            ->with('success', "{$label} berhasil dibuat. Menunggu konfirmasi dari IPPI.");
    }

    public function show(DeliveryNote $deliveryNote)
    {
        // Vendor users: only own SJ; internal users: any SJ
        if ($this->vendorScopeId() !== null) {
            abort_if($deliveryNote->vendor_id !== $this->vendorScopeId(), 403);
        }

        $deliveryNote->load('purchaseOrder.vendor', 'items.purchaseOrderItem.material');

        return view('vendor-portal.delivery-notes.show', compact('deliveryNote'));
    }

    public function printPdf(DeliveryNote $deliveryNote)
    {
        if ($this->vendorScopeId() !== null) {
            abort_if($deliveryNote->vendor_id !== $this->vendorScopeId(), 403);
        }

        $deliveryNote->load('purchaseOrder.vendor', 'vendor', 'items.purchaseOrderItem.material');

        $pdf = Pdf::loadView('vendor-portal.delivery-notes.pdf', compact('deliveryNote'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream("SJ-{$deliveryNote->dn_number}.pdf");
    }

    public function exportExcel(DeliveryNote $deliveryNote)
    {
        if ($this->vendorScopeId() !== null) {
            abort_if($deliveryNote->vendor_id !== $this->vendorScopeId(), 403);
        }

        $deliveryNote->load('purchaseOrder.vendor', 'vendor', 'items.purchaseOrderItem.material');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Surat Jalan');

        // Header info
        $sheet->setCellValue('A1', 'SURAT JALAN');
        $sheet->setCellValue('A2', 'No. SJ');
        $sheet->setCellValue('B2', $deliveryNote->dn_number);
        $sheet->setCellValue('A3', 'No. PO');
        $sheet->setCellValue('B3', $deliveryNote->purchaseOrder?->po_number ?? '-');
        $sheet->setCellValue('A4', 'Vendor');
        $sheet->setCellValue('B4', $deliveryNote->vendor?->name ?? '-');
        $sheet->setCellValue('A5', 'Est. Tgl Pengiriman');
        $sheet->setCellValue('B5', $deliveryNote->estimated_delivery_date?->format('d/m/Y') ?? '-');
        $sheet->setCellValue('A6', 'No. Kendaraan');
        $sheet->setCellValue('B6', $deliveryNote->vehicle_number ?? '-');
        $sheet->setCellValue('A7', 'Nama Driver');
        $sheet->setCellValue('B7', $deliveryNote->driver_name ?? '-');
        $sheet->setCellValue('A8', 'Status');
        $sheet->setCellValue('B8', $deliveryNote->statusLabel());
        $sheet->setCellValue('A9', 'Catatan');
        $sheet->setCellValue('B9', $deliveryNote->notes ?? '-');

        // Items table header
        $headers = ['No', 'Kode Material', 'Nama Material', 'Qty Dikirim', 'Satuan', 'Catatan'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . '11', $h);
        }
        ExcelService::applyHeaderStyle($spreadsheet, 'A11:F11');

        $row = 12;
        foreach ($deliveryNote->items as $idx => $item) {
            $material = $item->purchaseOrderItem?->material;
            $sheet->setCellValue("A{$row}", $idx + 1);
            $sheet->setCellValue("B{$row}", $material?->code ?? '-');
            $sheet->setCellValue("C{$row}", $material?->name ?? '-');
            $sheet->setCellValue("D{$row}", (float) $item->quantity);
            $sheet->setCellValue("E{$row}", $material?->unit_of_measure ?? '-');
            $sheet->setCellValue("F{$row}", $item->notes ?? '-');
            ExcelService::applyDataStyle($spreadsheet, "A{$row}:F{$row}", $row % 2 !== 0);
            $row++;
        }

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return ExcelService::download($spreadsheet, "SJ-{$deliveryNote->dn_number}.xlsx");
    }

    public function cancel(DeliveryNote $deliveryNote)
    {
        // Vendor users: only own SJ; internal users: any SJ
        if ($this->vendorScopeId() !== null) {
            abort_if($deliveryNote->vendor_id !== $this->vendorScopeId(), 403);
        }
        abort_if($deliveryNote->status !== 'pending', 403, 'Hanya Surat Jalan berstatus pending yang dapat dibatalkan.');

        /** @var User $user */
        $user = Auth::user();
        $isProcessVendor = $user->vendor?->vendor_type === 'process';

        DB::transaction(function () use ($deliveryNote, $user, $isProcessVendor) {
            $deliveryNote->update(['status' => 'cancelled']);

            // Stok vendor tidak dikurangi saat SJ dibuat (hanya dikurangi saat IPPI konfirmasi GR),
            // sehingga tidak perlu dikembalikan saat SJ dibatalkan.
        });

        return back()->with('success', 'Dokumen berhasil dibatalkan. Stok vendor telah dikembalikan.');
    }
}
