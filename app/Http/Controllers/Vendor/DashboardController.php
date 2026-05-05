<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\DeliveryNote;
use App\Models\Material;
use App\Models\PurchaseOrder;
use App\Models\Stock;
use App\Models\User;
use App\Models\VendorMaterialDelivery;
use App\Models\VendorProductionOrder;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        $vendorId = $user->vendor_id;

        $stats = [
            'po_open'       => PurchaseOrder::where('vendor_id', $vendorId)
                                ->whereIn('status', ['draft', 'approved', 'partially_received'])
                                ->count(),
            'sj_this_month' => DeliveryNote::where('vendor_id', $vendorId)
                                ->whereMonth('created_at', now()->month)
                                ->whereYear('created_at', now()->year)
                                ->count(),
            'vpo_active'    => VendorProductionOrder::where('vendor_id', $vendorId)
                                ->whereIn('status', ['draft', 'released', 'in_progress'])
                                ->count(),
            'kiriman_pending' => VendorMaterialDelivery::where('vendor_id', $vendorId)
                                ->where('status', 'sent')
                                ->count(),
        ];

        $recentPos = PurchaseOrder::where('vendor_id', $vendorId)
            ->with('items')
            ->latest()
            ->take(5)
            ->get();

        // Stock overview: materials processed by this vendor
        $stockSummary = Material::with(['stocks' => fn($q) => $q->with('storageLocation')])
            ->where('process_vendor_id', $vendorId)
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('code')
            ->get()
            ->map(fn($m) => [
                'code'  => $m->code,
                'name'  => $m->name,
                'type'  => $m->type,
                'uom'   => $m->unit_of_measure,
                'total' => (float) $m->stocks->sum('quantity'),
            ])
            ->filter(fn($m) => $m['total'] > 0)
            ->values();

        return view('vendor-portal.dashboard', compact('stats', 'recentPos', 'stockSummary'));
    }
}
