<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\PurchaseOrder;
use App\Models\ProductionOrder;
use App\Models\Stock;
use App\Models\Vendor;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $data = [
            'total_materials'        => Material::count(),
            'total_vendors'          => Vendor::count(),
            'open_pos'               => PurchaseOrder::whereIn('status', ['draft', 'approved'])->count(),
            'active_production'      => ProductionOrder::whereIn('status', ['released', 'in_progress'])->count(),
            'low_stock_materials'    => Stock::with('material', 'storageLocation')
                ->whereHas('material', fn($q) => $q->whereColumn('stocks.quantity', '<=', 'materials.min_stock')->where('min_stock', '>', 0))
                ->where('quantity', '>', 0)
                ->get(),
            'recent_pos'             => PurchaseOrder::with('vendor')->latest()->take(5)->get(),
            'recent_production'      => ProductionOrder::with('material')->latest()->take(5)->get(),
        ];

        return view('dashboard', $data);
    }
}
