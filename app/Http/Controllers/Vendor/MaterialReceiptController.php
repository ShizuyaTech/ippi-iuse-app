<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\VendorMaterialDelivery;
use App\Models\VendorStock;
use App\Models\VendorStockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MaterialReceiptController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $receipts = VendorMaterialDelivery::with('items.material')
            ->when($user->vendor_id, fn($q, $v) => $q->where('vendor_id', $v))
            ->latest()
            ->paginate(20);

        return view('vendor-portal.material-receipts.index', compact('receipts'));
    }

    public function show(VendorMaterialDelivery $materialReceipt)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        // Vendor users: only own receipts; internal users: any receipt
        if ($user->vendor_id !== null) {
            abort_if($materialReceipt->vendor_id !== $user->vendor_id, 403);
        }

        $materialReceipt->load('items.material', 'items.storageLocation', 'createdBy');

        return view('vendor-portal.material-receipts.show', compact('materialReceipt'));
    }

    public function confirm(Request $request, VendorMaterialDelivery $materialReceipt)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        // Vendor users: only confirm own receipts; internal users: any receipt
        if ($user->vendor_id !== null) {
            abort_if($materialReceipt->vendor_id !== $user->vendor_id, 403);
        }
        abort_if($materialReceipt->status !== 'sent', 422, 'Kiriman ini sudah dikonfirmasi.');

        $request->validate([
            'items'                       => 'required|array',
            'items.*.quantity_confirmed'  => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $materialReceipt, $user) {
            $materialReceipt->load('items.material');

            // VMD created from a GI: IPPI stock was already deducted at GI time
            $skipIppiDeduction = $materialReceipt->goods_issue_id !== null;

            foreach ($materialReceipt->items as $item) {
                $qtyConfirmed = (float) ($request->input("items.{$item->id}.quantity_confirmed") ?? 0);

                // Simpan qty aktual di item
                $item->update(['quantity_confirmed' => $qtyConfirmed]);

                if ($qtyConfirmed <= 0) continue;

                if ($skipIppiDeduction) {
                    // VMD from GI: full qty was deducted at GI time.
                    // Return the difference (qty_sent - qty_confirmed) back to IPPI stock.
                    $qtySent = (float) $item->quantity;
                    $qtyReturn = $qtySent - $qtyConfirmed;
                    if ($qtyReturn > 0) {
                        $ippi_stock = Stock::firstOrCreate(
                            ['material_id' => $item->material_id, 'storage_location_id' => $item->storage_location_id],
                            ['quantity' => 0]
                        );
                        $ippi_stock->increment('quantity', $qtyReturn);
                        $ippi_stock->refresh();

                        StockMovement::create([
                            'material_id'         => $item->material_id,
                            'storage_location_id' => $item->storage_location_id,
                            'movement_type'       => 'GI_REV',
                            'quantity'            => $qtyReturn,
                            'quantity_after'      => $ippi_stock->quantity,
                            'reference_document'  => $materialReceipt->vmd_number,
                            'movement_date'       => $materialReceipt->delivery_date,
                            'created_by'          => $user->id,
                        ]);
                    }
                } else {
                    // Manual VMD (not from GI): deduct IPPI stock now based on confirmed qty
                    $ippi_stock = Stock::firstOrCreate(
                        ['material_id' => $item->material_id, 'storage_location_id' => $item->storage_location_id],
                        ['quantity' => 0]
                    );
                    $ippi_stock->decrement('quantity', $qtyConfirmed);
                    $ippi_stock->refresh();

                    StockMovement::create([
                        'material_id'         => $item->material_id,
                        'storage_location_id' => $item->storage_location_id,
                        'movement_type'       => 'GI',
                        'quantity'            => -$qtyConfirmed,
                        'quantity_after'      => $ippi_stock->quantity,
                        'reference_document'  => $materialReceipt->vmd_number,
                        'movement_date'       => $materialReceipt->delivery_date,
                        'created_by'          => $user->id,
                    ]);
                }

                // 2. Tambah stok vendor
                $vendor_stock = VendorStock::firstOrCreate(
                    ['vendor_id' => $materialReceipt->vendor_id, 'material_id' => $item->material_id],
                    ['quantity' => 0]
                );
                $vendor_stock->increment('quantity', $qtyConfirmed);
                $vendor_stock->refresh();

                VendorStockMovement::create([
                    'vendor_id'          => $materialReceipt->vendor_id,
                    'material_id'        => $item->material_id,
                    'movement_type'      => 'VMD_IN',
                    'quantity'           => $qtyConfirmed,
                    'quantity_after'     => $vendor_stock->quantity,
                    'reference_document' => $materialReceipt->vmd_number,
                    'movement_date'      => $materialReceipt->delivery_date,
                    'created_by'         => $user->id,
                ]);
            }

            $materialReceipt->update([
                'status'       => 'confirmed',
                'confirmed_at' => now(),
                'confirmed_by' => $user->id,
            ]);
        });

        return redirect()->route('vendor.material-receipts.show', $materialReceipt)
            ->with('success', "Penerimaan {$materialReceipt->vmd_number} berhasil dikonfirmasi. Stok vendor diperbarui.");
    }
}

