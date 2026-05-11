<x-app-layout>
    <x-slot name="title">Edit Goods Receipt {{ $goodsReceipt->gr_number }}</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-1">Edit Goods Receipt</h2>
        <p class="text-sm text-gray-500 mb-4">
            {{ $goodsReceipt->gr_number }} &bull;
            PO: {{ $goodsReceipt->purchaseOrder->po_number }} &bull;
            Vendor: {{ $goodsReceipt->purchaseOrder->vendor->name }}
        </p>

        @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <form method="POST" action="{{ route('mm.goods-receipts.update', $goodsReceipt) }}" class="space-y-4">
            @csrf @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Penerimaan *</label>
                    <input type="date" name="receipt_date"
                        value="{{ old('receipt_date', $goodsReceipt->receipt_date->format('Y-m-d')) }}"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Storage Location</label>
                    <div class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50 text-gray-600">
                        {{ $goodsReceipt->storageLocation?->code }} - {{ $goodsReceipt->storageLocation?->name }}
                        <span class="text-xs text-gray-400">(tidak dapat diubah)</span>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan GR</label>
                <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('notes', $goodsReceipt->notes) }}</textarea>
            </div>

            <div>
                <h3 class="font-semibold text-gray-700 mb-2">Item yang Diterima
                    <span class="text-xs text-gray-400 font-normal">(qty tidak dapat diubah)</span>
                </h3>
                <table class="w-full text-sm border-collapse">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-3 py-2 text-left">Material</th>
                            <th class="px-3 py-2 text-right w-28">Qty Diterima</th>
                            <th class="px-3 py-2 text-left">ID Packing / Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($goodsReceipt->items as $item)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-3 py-2">
                                <div class="font-mono text-blue-700 text-xs">{{ $item->purchaseOrderItem->material->code }}</div>
                                <div class="text-gray-700">{{ $item->purchaseOrderItem->material->name }}</div>
                            </td>
                            <td class="px-3 py-2 text-right font-medium">
                                {{ number_format($item->quantity_received, 3) }}
                                <span class="text-gray-400 text-xs">{{ $item->purchaseOrderItem->material->unit_of_measure }}</span>
                            </td>
                            <td class="px-3 py-2">
                                <input type="text"
                                    name="items[{{ $item->id }}][packing_note]"
                                    value="{{ old('items.'.$item->id.'.packing_note', $item->packing_note) }}"
                                    placeholder="ID Packing / Keterangan"
                                    maxlength="100"
                                    class="w-full border rounded px-2 py-1 text-sm">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-blue-700 text-white px-6 py-2 rounded text-sm hover:bg-blue-800">Simpan Perubahan</button>
                <a href="{{ route('mm.goods-receipts.show', $goodsReceipt) }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded text-sm">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
