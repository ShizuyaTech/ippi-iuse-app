<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\StorageLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $vendorId = $user->vendor_id;

        // Storage locations assigned to this vendor
        $vendorLocationIds = StorageLocation::where('vendor_id', $vendorId)->pluck('id');

        $query = Material::with(['stocks' => function ($q) {
                $q->with('storageLocation')->where('quantity', '>', 0);
            }])
            ->where('process_vendor_id', $vendorId)
            ->where('is_active', true);

        if ($request->search) {
            $query->where(fn($q) => $q
                ->where('code', 'like', "%{$request->search}%")
                ->orWhere('name', 'like', "%{$request->search}%"));
        }
        if ($request->type) {
            $query->where('type', $request->type);
        }

        $materials = $query->orderBy('type')->orderBy('code')->get();

        return view('vendor-portal.stocks.index', compact('materials', 'vendorLocationIds'));
    }
}
