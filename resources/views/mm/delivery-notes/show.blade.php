<x-app-layout>
    <x-slot name="title">Detail Surat Jalan: {{ $deliveryNote->dn_number }}</x-slot>
    <div class="max-w-4xl space-y-4">

        {{-- Header --}}
        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <a href="{{ route('mm.delivery-notes.index') }}" class="text-blue-600 hover:underline text-sm">← Kembali</a>
                    <h2 class="text-lg font-semibold text-gray-700">
                        Surat Jalan: <span class="font-mono text-blue-700">{{ $deliveryNote->dn_number }}</span>
                    </h2>
                    <span class="px-2 py-0.5 rounded text-xs {{ $deliveryNote->statusColor() }}">{{ $deliveryNote->statusLabel() }}</span>
                </div>

                {{-- Action buttons --}}
                <div class="flex gap-2">
                    @if($deliveryNote->status === 'pending')
                    <form method="POST" action="{{ route('mm.delivery-notes.confirm', $deliveryNote) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="bg-blue-700 text-white px-4 py-2 rounded text-sm hover:bg-blue-800">
                            Konfirmasi Surat Jalan
                        </button>
                    </form>
                    @endif
                    @if($deliveryNote->status === 'confirmed')
                    <form method="POST" action="{{ route('mm.delivery-notes.receive', $deliveryNote) }}"
                        onsubmit="return confirm('Tandai barang sudah diterima di gudang?')">
                        @csrf @method('PATCH')
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700">
                            ✓ Tandai Sudah Diterima
                        </button>
                    </form>
                    @endif
                    @if($deliveryNote->status === 'received' && !$deliveryNote->goodsReceipt)
                        <a href="{{ route('mm.goods-receipts.create', ['po_id' => $deliveryNote->purchase_order_id, 'dn_id' => $deliveryNote->id]) }}"
                           class="bg-orange-600 text-white px-4 py-2 rounded text-sm hover:bg-orange-700">
                            + Buat Goods Receipt
                        </a>
                    @endif
                    @if($deliveryNote->goodsReceipt)
                        <a href="{{ route('mm.goods-receipts.show', $deliveryNote->goodsReceipt) }}"
                           class="bg-indigo-600 text-white px-4 py-2 rounded text-sm hover:bg-indigo-700">
                            Lihat Goods Receipt
                        </a>
                    @endif
                </div>
            </div>

            @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded text-sm mb-3">{{ session('success') }}</div>
            @endif

            @if($deliveryNote->source_type === 'vendor_production_order')
            <div class="bg-indigo-50 border border-indigo-200 text-indigo-800 px-4 py-2 rounded text-sm mb-3">
                Surat Jalan ini dibuat otomatis dari Vendor Production Order:
                <span class="font-mono font-semibold">{{ $deliveryNote->sourceVendorProductionOrder?->order_number ?? ('#'.$deliveryNote->source_id) }}</span>
            </div>
            @endif

            {{-- Status flow info --}}
            <div class="flex items-center gap-2 text-xs mb-4">
                <span class="{{ $deliveryNote->status === 'pending' ? 'bg-yellow-400 text-white' : 'bg-gray-200 text-gray-500' }} px-3 py-1 rounded-full font-medium">1. Pending</span>
                <span class="text-gray-300">→</span>
                <span class="{{ $deliveryNote->status === 'confirmed' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-500' }} px-3 py-1 rounded-full font-medium">2. Dikonfirmasi</span>
                <span class="text-gray-300">→</span>
                <span class="{{ $deliveryNote->status === 'received' ? 'bg-green-500 text-white' : 'bg-gray-200 text-gray-500' }} px-3 py-1 rounded-full font-medium">3. Diterima</span>
                @if($deliveryNote->status === 'cancelled')
                <span class="text-gray-300">|</span>
                <span class="bg-red-400 text-white px-3 py-1 rounded-full font-medium">Dibatalkan</span>
                @endif
            </div>

            {{-- Info grid --}}
            <div class="grid grid-cols-3 gap-4 text-sm">
                <div>
                    <div class="text-gray-500 text-xs">Vendor</div>
                    <div class="font-medium">{{ $deliveryNote->vendor?->name ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs">No. Purchase Order</div>
                    <div class="font-mono font-medium text-blue-700">{{ $deliveryNote->purchaseOrder?->po_number ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs">Est. Pengiriman</div>
                    <div class="font-medium">{{ $deliveryNote->estimated_delivery_date?->format('d/m/Y') ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs">No. Kendaraan</div>
                    <div class="font-medium">{{ $deliveryNote->vehicle_number ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs">Nama Driver</div>
                    <div class="font-medium">{{ $deliveryNote->driver_name ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs">Dibuat Oleh</div>
                    <div class="font-medium">{{ $deliveryNote->createdBy?->name ?? '-' }}</div>
                </div>
                @if($deliveryNote->notes)
                <div class="col-span-3">
                    <div class="text-gray-500 text-xs">Catatan Vendor</div>
                    <div>{{ $deliveryNote->notes }}</div>
                </div>
                @endif
            </div>
        </div>

        {{-- Items: editable jika belum received/cancelled --}}
        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex justify-between items-center mb-3">
                <h3 class="font-semibold text-gray-700">Item Surat Jalan</h3>
                @if(!in_array($deliveryNote->status, ['received','cancelled']))
                <p class="text-xs text-gray-400">Periksa qty aktual barang. Edit jika ada perbedaan dengan yang tertera di surat jalan vendor.</p>
                @endif
            </div>

            @if(!in_array($deliveryNote->status, ['received','cancelled']))
            {{-- Editable form --}}
            <form method="POST" action="{{ route('mm.delivery-notes.update-qty', $deliveryNote) }}">
                @csrf @method('PUT')
                <table class="w-full text-sm border-collapse">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2 text-left">Material</th>
                            <th class="px-4 py-2 text-right">Qty Surat Jalan</th>
                            <th class="px-4 py-2 text-right w-40">Qty Aktual (Edit)</th>
                            <th class="px-4 py-2 text-left">Satuan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($deliveryNote->items as $idx => $item)
                        <input type="hidden" name="items[{{ $idx }}][id]" value="{{ $item->id }}">
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-2">
                                <div class="font-mono text-xs text-gray-400">{{ $item->purchaseOrderItem?->material?->code }}</div>
                                <div class="font-medium">{{ $item->purchaseOrderItem?->material?->name }}</div>
                            </td>
                            <td class="px-4 py-2 text-right text-gray-500">{{ number_format($item->quantity, 3) }}</td>
                            <td class="px-4 py-2 text-right">
                                <input type="number"
                                    name="items[{{ $idx }}][quantity]"
                                    value="{{ old("items.{$idx}.quantity", $item->quantity) }}"
                                    step="0.001" min="0"
                                    class="w-full border rounded px-2 py-1 text-sm text-right focus:ring-1 focus:ring-blue-500">
                            </td>
                            <td class="px-4 py-2 text-gray-500">{{ $item->purchaseOrderItem?->material?->unit_of_measure ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="flex justify-end mt-3">
                    <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded text-sm hover:bg-gray-700">Simpan Perubahan Qty</button>
                </div>
            </form>
            @else
            {{-- Read-only --}}
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left">Material</th>
                        <th class="px-4 py-2 text-right">Qty</th>
                        <th class="px-4 py-2 text-left">Satuan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($deliveryNote->items as $item)
                    <tr class="border-b">
                        <td class="px-4 py-2">
                            <div class="font-mono text-xs text-gray-400">{{ $item->purchaseOrderItem?->material?->code }}</div>
                            <div class="font-medium">{{ $item->purchaseOrderItem?->material?->name }}</div>
                        </td>
                        <td class="px-4 py-2 text-right font-medium">{{ number_format($item->quantity, 3) }}</td>
                        <td class="px-4 py-2 text-gray-500">{{ $item->purchaseOrderItem?->material?->unit_of_measure ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>
</x-app-layout>
