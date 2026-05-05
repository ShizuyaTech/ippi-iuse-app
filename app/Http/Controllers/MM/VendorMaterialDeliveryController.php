<?php

namespace App\Http\Controllers\MM;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StorageLocation;
use App\Models\Vendor;
use App\Models\VendorMaterialDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VendorMaterialDeliveryController extends Controller
{
    public function index(Request $request)
    {
        $query = VendorMaterialDelivery::with('vendor')->withCount('items');

        if ($request->search) {
            $query->where(fn($q) => $q
                ->where('vmd_number', 'like', "%{$request->search}%")
                ->orWhereHas('vendor', fn($v) => $v->where('name', 'like', "%{$request->search}%")));
        }
        if ($request->vendor_id) $query->where('vendor_id', $request->vendor_id);
        if ($request->status)    $query->where('status', $request->status);

        $deliveries = $query->latest()->paginate(25)->withQueryString();
        $vendors    = Vendor::orderBy('name')->get(['id', 'name']);

        return view('mm.vendor-deliveries.index', compact('deliveries', 'vendors'));
    }

    public function create()
    {
        $vendors   = Vendor::orderBy('name')->get();
        $materials = Material::orderBy('name')->get(['id', 'code', 'name', 'unit_of_measure']);
        $locations = StorageLocation::orderBy('name')->get();

        return view('mm.vendor-deliveries.create', compact('vendors', 'materials', 'locations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'vendor_id'           => 'required|exists:vendors,id',
            'delivery_date'       => 'required|date',
            'storage_location_id' => 'required|exists:storage_locations,id',
            'items'               => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.quantity'    => 'required|numeric|min:0.001',
        ]);

        $storageLocationId = $request->storage_location_id;

        DB::transaction(function () use ($request, $storageLocationId) {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            $vmd = VendorMaterialDelivery::create([
                'vmd_number'        => VendorMaterialDelivery::generateNumber(),
                'vendor_id'         => $request->vendor_id,
                'purchase_order_id' => $request->purchase_order_id ?: null,
                'delivery_date'     => $request->delivery_date,
                'vehicle_number'    => $request->vehicle_number,
                'driver_name'       => $request->driver_name,
                'notes'             => $request->notes,
                'status'            => 'sent',
                'created_by'        => $user->id,
            ]);

            foreach ($request->items as $row) {
                if (($row['quantity'] ?? 0) <= 0) continue;

                $vmd->items()->create([
                    'material_id'         => $row['material_id'],
                    'storage_location_id' => $storageLocationId,
                    'quantity'            => $row['quantity'],
                    'notes'               => $row['notes'] ?? null,
                ]);

                // Reduce stock from source storage location
                $stock = Stock::firstOrCreate(
                    ['material_id' => $row['material_id'], 'storage_location_id' => $storageLocationId],
                    ['quantity' => 0]
                );
                $stock->decrement('quantity', $row['quantity']);
                $stock->refresh();

                StockMovement::create([
                    'material_id'         => $row['material_id'],
                    'storage_location_id' => $storageLocationId,
                    'movement_type'       => 'GI',
                    'quantity'            => -$row['quantity'],
                    'quantity_after'      => $stock->quantity,
                    'reference_document'  => $vmd->vmd_number,
                    'movement_date'       => $request->delivery_date,
                    'created_by'          => $user->id,
                ]);
            }
        });

        return redirect()->route('mm.vendor-deliveries.index')
            ->with('success', 'Kiriman bahan ke vendor berhasil dibuat dan stok dikurangi.');
    }

    public function show(VendorMaterialDelivery $vendorDelivery)
    {
        $vendorDelivery->load('vendor', 'items.material', 'items.storageLocation', 'createdBy', 'confirmedBy');

        return view('mm.vendor-deliveries.show', compact('vendorDelivery'));
    }
}
