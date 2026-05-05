<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorMaterialDelivery;
use Illuminate\Support\Facades\Auth;

class MaterialReceiptController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $receipts = VendorMaterialDelivery::with('items.material')
            ->where('vendor_id', $user->vendor_id)
            ->latest()
            ->paginate(20);

        return view('vendor-portal.material-receipts.index', compact('receipts'));
    }

    public function show(VendorMaterialDelivery $materialReceipt)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_if($materialReceipt->vendor_id !== $user->vendor_id, 403);

        $materialReceipt->load('items.material', 'items.storageLocation', 'createdBy');

        return view('vendor-portal.material-receipts.show', compact('materialReceipt'));
    }

    public function confirm(VendorMaterialDelivery $materialReceipt)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_if($materialReceipt->vendor_id !== $user->vendor_id, 403);
        abort_if($materialReceipt->status !== 'sent', 422, 'Kiriman ini sudah dikonfirmasi.');

        $materialReceipt->update([
            'status'       => 'confirmed',
            'confirmed_at' => now(),
            'confirmed_by' => $user->id,
        ]);

        return redirect()->route('vendor.material-receipts.show', $materialReceipt)
            ->with('success', "Penerimaan {$materialReceipt->vmd_number} berhasil dikonfirmasi.");
    }
}
