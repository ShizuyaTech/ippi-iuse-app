<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\StorageLocation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GoodsReceiptController extends Controller
{
    private function vendorId(): int
    {
        /** @var User $user */
        $user = Auth::user();
        return $user->vendor_id;
    }

    public function index(Request $request)
    {
        // Ambil semua user_id yang berasal dari vendor yang sama
        $vendorUserIds = \App\Models\User::where('vendor_id', $this->vendorId())->pluck('id');

        $query = GoodsReceipt::with('purchaseOrder', 'storageLocation')
            ->whereIn('created_by', $vendorUserIds);

        if ($request->search) {
            $query->where(fn($q) => $q
                ->where('gr_number', 'like', "%{$request->search}%")
                ->orWhereHas('purchaseOrder', fn($p) => $p->where('po_number', 'like', "%{$request->search}%")));
        }
        if ($request->date_from) {
            $query->whereDate('receipt_date', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('receipt_date', '<=', $request->date_to);
        }

        $receipts = $query->latest()->paginate(20)->withQueryString();

        return view('vendor-portal.goods-receipts.index', compact('receipts'));
    }

    public function create(Request $request)
    {
        $pos = PurchaseOrder::where('vendor_id', $this->vendorId())
            ->whereIn('status', ['approved', 'partially_received'])
            ->with('vendor')
            ->get();

        $locations  = StorageLocation::orderBy('code')->get();
        $selectedPo = $request->po_id
            ? PurchaseOrder::where('vendor_id', $this->vendorId())
                ->with('items.material', 'storageLocation')
                ->findOrFail($request->po_id)
            : null;

        return view('vendor-portal.goods-receipts.create', compact('pos', 'locations', 'selectedPo'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'purchase_order_id'   => ['required', 'exists:purchase_orders,id'],
            'receipt_date'        => ['required', 'date'],
            'storage_location_id' => ['required', 'exists:storage_locations,id'],
            'items'               => ['required', 'array', 'min:1'],
            'items.*.po_item_id'  => ['required', 'exists:purchase_order_items,id'],
            'items.*.quantity_received' => ['required', 'numeric', 'min:0.001'],
        ]);

        // Guard: PO must belong to this vendor
        $po = PurchaseOrder::where('vendor_id', $this->vendorId())
            ->findOrFail($request->purchase_order_id);

        // Delegate to MM GoodsReceiptController store (full business logic lives there)
        $mmRequest = $request->merge(['purchase_order_id' => $po->id]);
        return app(\App\Http\Controllers\MM\GoodsReceiptController::class)->store($mmRequest);
    }

    public function show(GoodsReceipt $goodsReceipt)
    {
        abort_if(
            $goodsReceipt->purchaseOrder?->vendor_id !== $this->vendorId(),
            403
        );

        $goodsReceipt->load('purchaseOrder.vendor', 'items.material', 'storageLocation');

        return view('vendor-portal.goods-receipts.show', compact('goodsReceipt'));
    }
}
