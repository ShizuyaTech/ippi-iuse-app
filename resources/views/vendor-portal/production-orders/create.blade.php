<x-vendor-layout>
    <x-slot name="title">Buat Vendor Production Order</x-slot>

    <div class="max-w-3xl bg-white rounded-lg shadow p-6" x-data="{ selectedPoItemId: '' }">
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('vendor.production-orders.index') }}" class="text-teal-700 hover:underline text-sm">Back</a>
            <h2 class="text-lg font-semibold text-gray-700">Buat Vendor Production Order</h2>
        </div>

        @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm">
                <ul class="list-disc ml-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('vendor.production-orders.store') }}" class="space-y-4">
            @csrf

            {{-- PO Item selector --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Referensi PO Item</label>
                <select name="purchase_order_item_id" x-model="selectedPoItemId" class="w-full border rounded px-3 py-2 text-sm" required>
                    <option value="">-- Pilih PO Item --</option>
                    @foreach($poItems as $item)
                    <option value="{{ $item->id }}" {{ old('purchase_order_item_id') == $item->id ? 'selected' : '' }}>
                        {{ $item->purchaseOrder?->po_number }} — [{{ $item->material?->code }}] {{ $item->material?->name }} — Sisa: {{ number_format($item->available_qty, 3) }} {{ $item->material?->unit_of_measure }}
                    </option>
                    @endforeach
                </select>
                @error('purchase_order_item_id')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                @if($poItems->isEmpty())
                    <p class="text-amber-700 text-xs mt-1">Tidak ada PO item aktif dengan sisa qty untuk vendor Anda.</p>
                @endif
            </div>

            {{-- BOM Info per PO item --}}
            @foreach($poItems as $item)
            <div x-show="selectedPoItemId == '{{ $item->id }}'" x-cloak>
                @if($item->bom)
                <div class="border border-teal-200 rounded-lg bg-teal-50 p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xs font-semibold text-teal-700 uppercase tracking-wide">BOM: {{ $item->bom->bom_number }}</span>
                        <span class="text-xs text-gray-500">— Base Qty: {{ number_format($item->bom->base_quantity, 3) }} {{ $item->material?->unit_of_measure }}</span>
                    </div>
                    <p class="text-xs text-gray-500 mb-3">Komponen bahan baku yang akan dikonsumsi saat produksi:</p>
                    <table class="w-full text-xs">
                        <thead class="text-gray-500 uppercase">
                            <tr>
                                <th class="text-left py-1 pr-3">Kode</th>
                                <th class="text-left py-1 pr-3">Material RM</th>
                                <th class="text-right py-1 pr-3">Qty / Base</th>
                                <th class="text-right py-1">Stok Vendor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($item->bom->items as $bomItem)
                            <tr class="border-t border-teal-100">
                                <td class="py-1 pr-3 font-mono text-teal-700">{{ $bomItem->material?->code }}</td>
                                <td class="py-1 pr-3 text-gray-700">{{ $bomItem->material?->name }}</td>
                                <td class="py-1 pr-3 text-right">{{ number_format($bomItem->quantity, 3) }} {{ $bomItem->unit }}</td>
                                <td class="py-1 text-right font-semibold {{ $bomItem->vendor_stock_qty > 0 ? 'text-teal-700' : 'text-red-500' }}">
                                    {{ number_format($bomItem->vendor_stock_qty, 3) }}
                                    <span class="font-normal text-gray-400">{{ $bomItem->material?->unit_of_measure }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="border border-amber-200 rounded bg-amber-50 px-4 py-3 text-xs text-amber-700">
                    Tidak ada BOM aktif untuk material ini. Konsumsi bahan baku tidak akan tercatat otomatis.
                </div>
                @endif
            </div>
            @endforeach

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Qty Planned</label>
                    <input type="number" name="quantity_planned" step="0.001" min="0.001" value="{{ old('quantity_planned') }}" class="w-full border rounded px-3 py-2 text-sm" required>
                    @error('quantity_planned')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Planned Start</label>
                    <input type="date" name="planned_start_date" value="{{ old('planned_start_date') }}" class="w-full border rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Planned End</label>
                    <input type="date" name="planned_end_date" value="{{ old('planned_end_date') }}" class="w-full border rounded px-3 py-2 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="3" class="w-full border rounded px-3 py-2 text-sm">{{ old('notes') }}</textarea>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-teal-700 text-white px-5 py-2 rounded text-sm hover:bg-teal-800">Simpan</button>
                <a href="{{ route('vendor.production-orders.index') }}" class="bg-gray-200 text-gray-700 px-5 py-2 rounded text-sm">Batal</a>
            </div>
        </form>
    </div>
</x-vendor-layout>

