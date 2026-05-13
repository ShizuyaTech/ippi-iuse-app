<x-app-layout>
    <x-slot name="title">Detail GR: {{ $goodsReceipt->gr_number }}</x-slot>
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-start">
                <div>
                    <div class="text-xs text-gray-400">Nomor GR</div>
                    <div class="text-2xl font-bold text-green-700 font-mono">{{ $goodsReceipt->gr_number }}</div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('mm.goods-receipts.edit', $goodsReceipt) }}" class="bg-yellow-500 text-white px-4 py-2 rounded text-sm hover:bg-yellow-600">Edit</a>
                    <form method="POST" action="{{ route('mm.goods-receipts.destroy', $goodsReceipt) }}" onsubmit="return confirm('Hapus GR {{ $goodsReceipt->gr_number }}? Stok akan dibalik.')">
                        @csrf @method('DELETE')
                        <button class="bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700">Hapus</button>
                    </form>
                    <a href="{{ route('mm.goods-receipts.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm">Kembali</a>
                </div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 text-sm">
                <div><span class="text-gray-500">No. PO:</span><br><a href="{{ route('mm.purchase-orders.show', $goodsReceipt->purchaseOrder) }}" class="font-mono text-blue-700 hover:underline">{{ $goodsReceipt->purchaseOrder->po_number }}</a></div>
                <div><span class="text-gray-500">Vendor:</span><br><span class="font-medium">{{ $goodsReceipt->purchaseOrder->vendor->name ?? '-' }}</span></div>
                <div><span class="text-gray-500">Tgl Terima:</span><br><span class="font-medium">{{ $goodsReceipt->receipt_date->format('d M Y') }}</span></div>
                <div><span class="text-gray-500">Lokasi:</span><br><span class="font-medium">{{ $goodsReceipt->storageLocation->name ?? '-' }}</span></div>
                <div><span class="text-gray-500">Dibuat Pada:</span><br><span class="font-medium">{{ $goodsReceipt->created_at->format('d/m/Y H:i') }}</span></div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-700 mb-3">Item Diterima</h3>
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left">Material</th>
                        <th class="px-4 py-2 text-right">Qty Diterima</th>
                        <th class="px-4 py-2 text-left">UoM</th>
                        <th class="px-4 py-2 text-left">ID Packing / Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($goodsReceipt->items as $item)
                    <tr class="border-b">
                        <td class="px-4 py-2">
                            <div class="font-mono text-blue-700 text-xs">{{ $item->material->code }}</div>
                            <div>{{ $item->material->name }}</div>
                        </td>
                        <td class="px-4 py-2 text-right font-medium text-green-700">{{ number_format($item->quantity_received, 3) }}</td>
                        <td class="px-4 py-2">{{ $item->material->unit_of_measure ?? '-' }}</td>
                        <td class="px-4 py-2 font-mono text-xs text-gray-600">{{ $item->packing_note ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
