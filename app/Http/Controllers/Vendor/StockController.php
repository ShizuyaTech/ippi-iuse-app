<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $vendorId = $user->vendor_id;

        $query = VendorStock::with('material')
            ->when($vendorId, fn($q, $v) => $q->where('vendor_id', $v))
            ->where('quantity', '>', 0);

        if ($request->search) {
            $query->whereHas('material', fn($q) => $q
                ->where('code', 'like', "%{$request->search}%")
                ->orWhere('name', 'like', "%{$request->search}%"));
        }
        if ($request->type) {
            $query->whereHas('material', fn($q) => $q->where('type', $request->type));
        }

        $stocks = $query->get()->sortBy(fn($s) => [$s->material?->type, $s->material?->code]);

        return view('vendor-portal.stocks.index', compact('stocks'));
    }
}
