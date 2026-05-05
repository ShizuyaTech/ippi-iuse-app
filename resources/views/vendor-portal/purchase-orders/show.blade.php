<x-vendor-layout>
    <x-slot name="title">Detail PO: {{ $purchaseOrder->po_number }}</x-slot>
    <div class="bg-white rounded-lg shadow p-6 max-w-4xl">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('vendor.purchase-orders.index') }}" class="text-teal-600 hover:underline text-sm">← Kembali</a>
            <h2 class="text-lg font-semibold text-gray-700">
                PO: <span class="font-mono text-teal-700">{{ $purchaseOrder->po_number }}</span>
            </h2>
            <span class="px-2 py-0.5 rounded text-xs
                {{ $purchaseOrder->status==='draft'?'bg-gray-100 text-gray-600':'' }}
                {{ $purchaseOrder->status==='approved'?'bg-blue-100 text-blue-700':'' }}
                {{ $purchaseOrder->status==='partially_received'?'bg-yellow-100 text-yellow-700':'' }}
                {{ $purchaseOrder->status==='completed'?'bg-green-100 text-green-700':'' }}
                {{ $purchaseOrder->status==='cancelled'?'bg-red-100 text-red-700':'' }}
            ">{{ ucfirst(str_replace('_',' ',$purchaseOrder->status)) }}</span>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
            <div>
                <div class="text-gray-500">Vendor</div>
                <div class="font-medium">{{ $purchaseOrder->vendor?->name ?? '-' }}</div>
            </div>
            <div>
                <div class="text-gray-500">Tanggal Order</div>
                <div class="font-medium">{{ $purchaseOrder->order_date?->format('d/m/Y') ?? '-' }}</div>
            </div>
            <div>
                <div class="text-gray-500">Lokasi Penerimaan</div>
                <div class="font-medium">{{ $purchaseOrder->storageLocation?->name ?? '-' }}</div>
            </div>
            <div>
                <div class="text-gray-500">Catatan</div>
                <div>{{ $purchaseOrder->notes ?? '-' }}</div>
            </div>
        </div>

        <h3 class="font-semibold text-gray-600 mb-2 text-sm">Item Purchase Order</h3>
        <table class="w-full text-sm border-collapse mb-6">
            <thead class="bg-teal-800 text-white">
                <tr>
                    <th class="px-4 py-2 text-left">Material</th>
                    <th class="px-4 py-2 text-right">Qty PO</th>
                    <th class="px-4 py-2 text-right">Qty Diterima</th>
                    <th class="px-4 py-2 text-right">Sisa</th>
                    <th class="px-4 py-2 text-right">Harga Satuan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchaseOrder->items as $item)
                @php $sisa = $item->quantity_ordered - ($item->quantity_received ?? 0); @endphp
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-2">
                        <div class="font-mono text-xs text-gray-500">{{ $item->material?->code }}</div>
                        <div>{{ $item->material?->name }}</div>
                    </td>
                    <td class="px-4 py-2 text-right">{{ number_format($item->quantity_ordered, 3) }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($item->quantity_received ?? 0, 3) }}</td>
                    <td class="px-4 py-2 text-right {{ $sisa > 0 ? 'text-orange-600 font-medium' : 'text-green-600' }}">
                        {{ number_format($sisa, 3) }}
                    </td>
                    <td class="px-4 py-2 text-right">{{ number_format($item->unit_price ?? 0, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        @if(in_array($purchaseOrder->status, ['approved','partially_received']))
        <div class="flex gap-3">
            <a href="{{ route('vendor.delivery-notes.create', ['po_id' => $purchaseOrder->id]) }}"
               class="bg-blue-700 text-white px-4 py-2 rounded text-sm hover:bg-blue-800">
                + Buat Surat Jalan
            </a>
        </div>
        @endif
    </div>
</x-vendor-layout>
