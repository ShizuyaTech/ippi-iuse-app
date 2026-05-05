<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\DeliveryNote;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryNoteController extends Controller
{
    private function vendorId(): int
    {
        /** @var User $user */
        $user = Auth::user();
        return $user->vendor_id;
    }

    public function index(Request $request)
    {
        $query = DeliveryNote::with('purchaseOrder')
            ->where('vendor_id', $this->vendorId());

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
        $pos = PurchaseOrder::where('vendor_id', $this->vendorId())
            ->whereIn('status', ['approved', 'partially_received'])
            ->get();

        $selectedPo = $request->po_id
            ? PurchaseOrder::where('vendor_id', $this->vendorId())
                ->with('items.material')
                ->findOrFail($request->po_id)
            : null;

        return view('vendor-portal.delivery-notes.create', compact('pos', 'selectedPo'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'purchase_order_id'        => ['required', 'exists:purchase_orders,id'],
            'estimated_delivery_date'  => ['required', 'date', 'after_or_equal:today'],
            'vehicle_number'           => ['nullable', 'string', 'max:50'],
            'driver_name'              => ['nullable', 'string', 'max:100'],
            'notes'                    => ['nullable', 'string'],
            'items'                    => ['required', 'array', 'min:1'],
            'items.*.po_item_id'       => ['required', 'exists:purchase_order_items,id'],
            'items.*.quantity'         => ['required', 'numeric', 'min:0.001'],
        ]);

        $po = PurchaseOrder::where('vendor_id', $this->vendorId())
            ->whereIn('status', ['approved', 'partially_received'])
            ->findOrFail($request->purchase_order_id);

        DB::transaction(function () use ($request, $po) {
            /** @var User $user */
            $user = Auth::user();

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

            $dn = DeliveryNote::create([
                'dn_number'               => DeliveryNote::generateNumber(),
                'purchase_order_id'       => $po->id,
                'vendor_id'               => $this->vendorId(),
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

        return redirect()->route('vendor.delivery-notes.index')
            ->with('success', 'Surat Jalan berhasil dibuat. Menunggu konfirmasi dari IPPI.');
    }

    public function show(DeliveryNote $deliveryNote)
    {
        abort_if($deliveryNote->vendor_id !== $this->vendorId(), 403);

        $deliveryNote->load('purchaseOrder.vendor', 'items.purchaseOrderItem.material');

        return view('vendor-portal.delivery-notes.show', compact('deliveryNote'));
    }

    public function cancel(DeliveryNote $deliveryNote)
    {
        abort_if($deliveryNote->vendor_id !== $this->vendorId(), 403);
        abort_if($deliveryNote->status !== 'pending', 403, 'Hanya Surat Jalan berstatus pending yang dapat dibatalkan.');

        $deliveryNote->update(['status' => 'cancelled']);

        return back()->with('success', 'Surat Jalan berhasil dibatalkan.');
    }
}
