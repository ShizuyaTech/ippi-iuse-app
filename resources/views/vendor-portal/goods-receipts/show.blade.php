<x-vendor-layout>
    <x-slot name="title">Detail GR: {{ $goodsReceipt->gr_number }}</x-slot>
    <div class="bg-white rounded-lg shadow p-6 max-w-4xl">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('vendor.goods-receipts.index') }}" class="text-teal-600 hover:underline text-sm">← Kembali</a>
            <h2 class="text-lg font-semibold text-gray-700">
                GR: <span class="font-mono text-teal-700">{{ $goodsReceipt->gr_number }}</span>
            </h2>
            <span class="px-2 py-0.5 rounded text-xs
                {{ $goodsReceipt->status==='draft'?'bg-gray-100 text-gray-600':'' }}
                {{ $goodsReceipt->status==='posted'?'bg-green-100 text-green-700':'' }}
            ">{{ ucfirst($goodsReceipt->status ?? '-') }}</span>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-6 text-sm">
            <div>
                <div class="text-gray-500">No. Purchase Order</div>
                <div class="font-mono font-medium">{{ $goodsReceipt->purchaseOrder?->po_number ?? '-' }}</div>
            </div>
            <div>
                <div class="text-gray-500">Vendor</div>
                <div class="font-medium">{{ $goodsReceipt->purchaseOrder?->vendor?->name ?? '-' }}</div>
            </div>
            <div>
                <div class="text-gray-500">Tanggal Terima</div>
                <div class="font-medium">{{ $goodsReceipt->receipt_date?->format('d/m/Y') ?? '-' }}</div>
            </div>
            <div>
                <div class="text-gray-500">Storage Location</div>
                <div class="font-medium">{{ $goodsReceipt->storageLocation?->name ?? '-' }}</div>
            </div>
            @if($goodsReceipt->notes)
            <div class="col-span-2">
                <div class="text-gray-500">Catatan</div>
                <div>{{ $goodsReceipt->notes }}</div>
            </div>
            @endif
        </div>

        <h3 class="font-semibold text-gray-600 mb-2 text-sm">Item Diterima</h3>
        <table class="w-full text-sm border-collapse">
            <thead class="bg-teal-800 text-white">
                <tr>
                    <th class="px-4 py-2 text-left">Material</th>
                    <th class="px-4 py-2 text-right">Qty Diterima</th>
                    <th class="px-4 py-2 text-left">Satuan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($goodsReceipt->items as $item)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-2">
                        <div class="font-mono text-xs text-gray-500">{{ $item->material?->code }}</div>
                        <div>{{ $item->material?->name }}</div>
                    </td>
                    <td class="px-4 py-2 text-right">{{ number_format($item->quantity_received, 3) }}</td>
                    <td class="px-4 py-2">{{ $item->material?->unit_of_measure ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-vendor-layout>
