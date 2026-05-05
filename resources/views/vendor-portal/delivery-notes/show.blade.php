<x-vendor-layout>
    <x-slot name="title">Detail Surat Jalan: {{ $deliveryNote->dn_number }}</x-slot>
    <div class="bg-white rounded-lg shadow p-6 max-w-4xl">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('vendor.delivery-notes.index') }}" class="text-teal-600 hover:underline text-sm">← Kembali</a>
            <h2 class="text-lg font-semibold text-gray-700">
                Surat Jalan: <span class="font-mono text-teal-700">{{ $deliveryNote->dn_number }}</span>
            </h2>
            <span class="px-2 py-0.5 rounded text-xs {{ $deliveryNote->statusColor() }}">{{ $deliveryNote->statusLabel() }}</span>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded text-sm">{{ session('success') }}</div>
        @endif

        @if($deliveryNote->source_type === 'vendor_production_order' && $deliveryNote->source_id)
        <div class="mb-4 bg-indigo-50 border border-indigo-200 text-indigo-800 px-4 py-3 rounded text-sm">
            Surat Jalan ini dibuat otomatis dari penyelesaian Vendor Production Order.
            <a href="{{ route('vendor.production-orders.show', $deliveryNote->source_id) }}" class="underline font-medium ml-1">Lihat Production Order</a>
        </div>
        @endif

        {{-- Status info banner --}}
        @if($deliveryNote->status === 'pending')
        <div class="mb-4 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded text-sm">
            Surat Jalan ini sedang menunggu konfirmasi dari tim IPPI. Anda dapat membatalkan selama masih berstatus ini.
        </div>
        @elseif($deliveryNote->status === 'confirmed')
        <div class="mb-4 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded text-sm">
            Surat Jalan telah dikonfirmasi oleh IPPI. Silakan kirim barang sesuai jadwal.
        </div>
        @elseif($deliveryNote->status === 'received')
        <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded text-sm">
            Barang telah diterima oleh gudang IPPI. Transaksi selesai.
        </div>
        @endif

        <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
            <div>
                <div class="text-gray-500">No. Purchase Order</div>
                <div class="font-mono font-medium">{{ $deliveryNote->purchaseOrder?->po_number ?? '-' }}</div>
            </div>
            <div>
                <div class="text-gray-500">Estimasi Tgl Pengiriman</div>
                <div class="font-medium">{{ $deliveryNote->estimated_delivery_date?->format('d/m/Y') ?? '-' }}</div>
            </div>
            <div>
                <div class="text-gray-500">No. Kendaraan</div>
                <div class="font-medium">{{ $deliveryNote->vehicle_number ?? '-' }}</div>
            </div>
            <div>
                <div class="text-gray-500">Nama Driver</div>
                <div class="font-medium">{{ $deliveryNote->driver_name ?? '-' }}</div>
            </div>
            @if($deliveryNote->notes)
            <div class="col-span-2">
                <div class="text-gray-500">Catatan</div>
                <div>{{ $deliveryNote->notes }}</div>
            </div>
            @endif
        </div>

        <h3 class="font-semibold text-gray-600 mb-2 text-sm">Item yang Dikirim</h3>
        <table class="w-full text-sm border-collapse">
            <thead class="bg-teal-800 text-white">
                <tr>
                    <th class="px-4 py-2 text-left">Material</th>
                    <th class="px-4 py-2 text-right">Qty Dikirim</th>
                    <th class="px-4 py-2 text-left">Satuan</th>
                    <th class="px-4 py-2 text-left">Catatan Item</th>
                </tr>
            </thead>
            <tbody>
                @foreach($deliveryNote->items as $item)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-2">
                        <div class="font-mono text-xs text-gray-500">{{ $item->purchaseOrderItem?->material?->code }}</div>
                        <div>{{ $item->purchaseOrderItem?->material?->name }}</div>
                    </td>
                    <td class="px-4 py-2 text-right font-medium">{{ number_format($item->quantity, 3) }}</td>
                    <td class="px-4 py-2">{{ $item->purchaseOrderItem?->material?->unit_of_measure ?? '-' }}</td>
                    <td class="px-4 py-2 text-gray-500 text-xs">{{ $item->notes ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if($deliveryNote->status === 'pending')
        <div class="mt-4">
            <form method="POST" action="{{ route('vendor.delivery-notes.cancel', $deliveryNote) }}"
                onsubmit="return confirm('Yakin ingin membatalkan surat jalan ini?')">
                @csrf @method('PATCH')
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700">Batalkan Surat Jalan</button>
            </form>
        </div>
        @endif
    </div>
</x-vendor-layout>
