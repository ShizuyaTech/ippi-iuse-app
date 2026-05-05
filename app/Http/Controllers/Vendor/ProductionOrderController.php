<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\BusinessEventLog;
use App\Models\DeliveryNote;
use App\Models\Material;
use App\Models\PurchaseOrderItem;
use App\Models\VendorProductionOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductionOrderController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = VendorProductionOrder::with('material', 'purchaseOrderItem.purchaseOrder', 'deliveryNote')
            ->where('vendor_id', $user->vendor_id);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('order_number', 'like', "%{$request->search}%")
                    ->orWhereHas('material', fn($m) => $m->where('name', 'like', "%{$request->search}%"));
            });
        }

        $orders = $query->latest()->paginate(20)->withQueryString();

        return view('vendor-portal.production-orders.index', compact('orders'));
    }

    public function create()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $poItems = PurchaseOrderItem::with('purchaseOrder', 'material')
            ->whereHas('purchaseOrder', function ($q) use ($user) {
                $q->where('vendor_id', $user->vendor_id)
                    ->whereIn('status', ['approved', 'partially_received']);
            })
            ->whereHas('material', fn($q) => $q
                ->whereIn('type', ['WIP', 'FP'])
                ->where('process_vendor_id', $user->vendor_id))
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($item) {
                $item->available_qty = $this->remainingPlannableForPoItem($item);
                return $item;
            })
            ->filter(fn($i) => (float) $i->available_qty > 0.001)
            ->values();

        return view('vendor-portal.production-orders.create', compact('poItems'));
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $request->validate([
            'purchase_order_item_id' => 'required|exists:purchase_order_items,id',
            'quantity_planned' => 'required|numeric|min:0.001',
            'planned_start_date' => 'nullable|date',
            'planned_end_date' => 'nullable|date|after_or_equal:planned_start_date',
            'notes' => 'nullable|string',
        ]);

        try {
            $order = DB::transaction(function () use ($request, $user) {
                $poItem = PurchaseOrderItem::with('purchaseOrder', 'material')
                    ->whereKey($request->purchase_order_item_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                abort_if($poItem->purchaseOrder->vendor_id !== $user->vendor_id, 403, 'PO item bukan milik vendor Anda.');
                abort_if(!in_array($poItem->purchaseOrder->status, ['approved', 'partially_received']), 422, 'PO harus Approved/Partially Received.');

                if ((int) ($poItem->material?->process_vendor_id ?? 0) !== (int) $user->vendor_id) {
                    throw ValidationException::withMessages([
                        'purchase_order_item_id' => 'Material pada PO item ini tidak ditetapkan ke vendor proses Anda.',
                    ]);
                }

                $poRemaining = max(0, (float) $poItem->quantity - (float) $poItem->quantity_received);
                $allocated = (float) VendorProductionOrder::where('purchase_order_item_id', $poItem->id)
                    ->where('status', '!=', 'cancelled')
                    ->lockForUpdate()
                    ->sum('quantity_planned');
                $availableQty = max(0, $poRemaining - $allocated);

                if ((float) $request->quantity_planned > $availableQty + 0.001) {
                    throw new \RuntimeException('Qty planned melebihi sisa qty PO item yang masih bisa dialokasikan.');
                }

                $order = VendorProductionOrder::create([
                    'order_number' => VendorProductionOrder::generateNumber(),
                    'vendor_id' => $user->vendor_id,
                    'material_id' => $poItem->material_id,
                    'purchase_order_item_id' => $poItem->id,
                    'quantity_planned' => $request->quantity_planned,
                    'quantity_ok' => 0,
                    'quantity_ng' => 0,
                    'planned_start_date' => $request->planned_start_date,
                    'planned_end_date' => $request->planned_end_date,
                    'status' => 'draft',
                    'notes' => $request->notes,
                    'created_by' => $user->id,
                ]);

                $this->logEvent('vendor_production_order.created', 'VendorProductionOrder', $order->id, $user->id, [
                    'order_number' => $order->order_number,
                    'purchase_order_item_id' => $order->purchase_order_item_id,
                    'quantity_planned' => $order->quantity_planned,
                ]);

                return $order;
            }, 3);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['quantity_planned' => $e->getMessage()])->withInput();
        }

        return redirect()->route('vendor.production-orders.show', $order)
            ->with('success', 'Vendor Production Order berhasil dibuat.');
    }

    public function show(VendorProductionOrder $productionOrder)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_if($productionOrder->vendor_id !== $user->vendor_id, 403);

        $productionOrder->load('material', 'purchaseOrderItem.purchaseOrder', 'deliveryNote', 'reports.createdBy', 'createdBy');

        return view('vendor-portal.production-orders.show', compact('productionOrder'));
    }

    public function release(VendorProductionOrder $productionOrder)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_if($productionOrder->vendor_id !== $user->vendor_id, 403);
        abort_if($productionOrder->status !== 'draft', 422, 'Hanya order draft yang dapat di-release.');

        $productionOrder->update([
            'status' => 'released',
            'actual_start_date' => now()->toDateString(),
        ]);

        $this->logEvent('vendor_production_order.released', 'VendorProductionOrder', $productionOrder->id, $user->id, [
            'order_number' => $productionOrder->order_number,
        ]);

        return back()->with('success', 'Order berhasil di-release.');
    }

    public function report(Request $request, VendorProductionOrder $productionOrder)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_if($productionOrder->vendor_id !== $user->vendor_id, 403);
        abort_if(!in_array($productionOrder->status, ['released', 'in_progress']), 422, 'Order harus Released atau In Progress.');

        $request->validate([
            'report_date' => 'required|date',
            'quantity_ok' => 'required|numeric|min:0',
            'quantity_ng' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ((float) $request->quantity_ng > 0 && blank($request->notes)) {
            return back()->withErrors([
                'notes' => 'Catatan wajib diisi jika terdapat qty NG.',
            ])->withInput();
        }

        $delta = (float) $request->quantity_ok + (float) $request->quantity_ng;
        if ($delta <= 0) {
            return back()->withErrors(['quantity_ok' => 'Isi qty OK atau qty NG lebih dari 0.'])->withInput();
        }

        if ($delta > $productionOrder->remainingQty() + 0.001) {
            return back()->withErrors(['quantity_ok' => 'Total qty melebihi sisa quantity planned.'])->withInput();
        }

        DB::transaction(function () use ($request, $productionOrder, $user) {
            $productionOrder->reports()->create([
                'report_date' => $request->report_date,
                'quantity_ok' => $request->quantity_ok,
                'quantity_ng' => $request->quantity_ng,
                'notes' => $request->notes,
                'created_by' => $user->id,
            ]);

            $productionOrder->increment('quantity_ok', (float) $request->quantity_ok);
            $productionOrder->increment('quantity_ng', (float) $request->quantity_ng);

            if ($productionOrder->status === 'released') {
                $productionOrder->update(['status' => 'in_progress']);
            }

            $this->logEvent('vendor_production_order.reported', 'VendorProductionOrder', $productionOrder->id, $user->id, [
                'report_date' => $request->report_date,
                'quantity_ok' => (float) $request->quantity_ok,
                'quantity_ng' => (float) $request->quantity_ng,
            ]);

            $productionOrder->refresh();
            if ($productionOrder->remainingQty() <= 0.001) {
                $productionOrder->update([
                    'status' => 'completed',
                    'actual_end_date' => now()->toDateString(),
                ]);

                $this->ensureDeliveryNoteForCompletedOrder($productionOrder, $user->id);
            }
        });

        return back()->with('success', 'Laporan output berhasil disimpan.');
    }

    public function complete(VendorProductionOrder $productionOrder)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_if($productionOrder->vendor_id !== $user->vendor_id, 403);
        abort_if(!in_array($productionOrder->status, ['released', 'in_progress']), 422, 'Order tidak dapat diselesaikan.');

        $productionOrder->refresh();
        abort_if($productionOrder->reports()->count() === 0, 422, 'Belum ada report output untuk order ini.');
        abort_if($productionOrder->remainingQty() > 0.001, 422, 'Sisa quantity masih ada. Lengkapi laporan hingga quantity planned terpenuhi.');

        $productionOrder->update([
            'status' => 'completed',
            'actual_end_date' => now()->toDateString(),
        ]);

        $this->logEvent('vendor_production_order.completed', 'VendorProductionOrder', $productionOrder->id, $user->id, [
            'order_number' => $productionOrder->order_number,
            'quantity_ok' => (float) $productionOrder->quantity_ok,
            'quantity_ng' => (float) $productionOrder->quantity_ng,
        ]);

        $this->ensureDeliveryNoteForCompletedOrder($productionOrder, $user->id);

        return back()->with('success', 'Order selesai.');
    }

    public function cancel(VendorProductionOrder $productionOrder)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_if($productionOrder->vendor_id !== $user->vendor_id, 403);
        abort_if($productionOrder->status === 'completed', 422, 'Order completed tidak bisa dibatalkan.');

        $productionOrder->update(['status' => 'cancelled']);

        $this->logEvent('vendor_production_order.cancelled', 'VendorProductionOrder', $productionOrder->id, $user->id, [
            'order_number' => $productionOrder->order_number,
        ]);

        return redirect()->route('vendor.production-orders.index')->with('success', 'Order dibatalkan.');
    }

    private function ensureDeliveryNoteForCompletedOrder(VendorProductionOrder $productionOrder, int $userId): void
    {
        $productionOrder->refresh();

        if ($productionOrder->delivery_note_id || (float) $productionOrder->quantity_ok <= 0.001) {
            return;
        }

        $existingDeliveryNote = DeliveryNote::where('source_type', 'vendor_production_order')
            ->where('source_id', $productionOrder->id)
            ->first();

        if ($existingDeliveryNote) {
            $productionOrder->update(['delivery_note_id' => $existingDeliveryNote->id]);
            return;
        }

        $productionOrder->loadMissing('purchaseOrderItem.purchaseOrder');
        $poItem = $productionOrder->purchaseOrderItem;

        if (! $poItem || ! $poItem->purchaseOrder) {
            return;
        }

        $deliveryNote = DeliveryNote::create([
            'dn_number' => DeliveryNote::generateNumber(),
            'purchase_order_id' => $poItem->purchase_order_id,
            'vendor_id' => $productionOrder->vendor_id,
            'estimated_delivery_date' => now()->toDateString(),
            'notes' => 'Auto-generated from Vendor Production Order ' . $productionOrder->order_number,
            'status' => 'pending',
            'source_type' => 'vendor_production_order',
            'source_id' => $productionOrder->id,
            'created_by' => $userId,
        ]);

        $deliveryNote->items()->create([
            'purchase_order_item_id' => $poItem->id,
            'quantity' => $productionOrder->quantity_ok,
            'notes' => 'Generated from completed vendor production order',
        ]);

        $productionOrder->update(['delivery_note_id' => $deliveryNote->id]);

        $this->logEvent('delivery_note.auto_generated_from_vpo', 'DeliveryNote', $deliveryNote->id, $userId, [
            'dn_number' => $deliveryNote->dn_number,
            'vendor_production_order_id' => $productionOrder->id,
            'purchase_order_item_id' => $poItem->id,
            'quantity' => (float) $productionOrder->quantity_ok,
        ]);
    }

    private function remainingPlannableForPoItem(PurchaseOrderItem $poItem, ?int $excludeOrderId = null): float
    {
        $poRemaining = max(0, (float) $poItem->quantity - (float) $poItem->quantity_received);

        $allocatedQuery = VendorProductionOrder::where('purchase_order_item_id', $poItem->id)
            ->where('status', '!=', 'cancelled');

        if ($excludeOrderId) {
            $allocatedQuery->where('id', '!=', $excludeOrderId);
        }

        $allocated = (float) $allocatedQuery->sum('quantity_planned');

        return max(0, $poRemaining - $allocated);
    }

    private function logEvent(string $eventType, string $entityType, ?int $entityId, ?int $userId, array $payload = []): void
    {
        BusinessEventLog::create([
            'event_type' => $eventType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'user_id' => $userId,
            'payload' => $payload,
        ]);
    }
}
