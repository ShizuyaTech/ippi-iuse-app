<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
}
