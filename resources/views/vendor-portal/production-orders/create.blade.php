<x-vendor-layout>
    <x-slot name="title">Buat Vendor Production Order</x-slot>

    <div class="max-w-3xl bg-white rounded-lg shadow p-6">
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('vendor.production-orders.index') }}" class="text-teal-700 hover:underline text-sm">Back</a>
            <h2 class="text-lg font-semibold text-gray-700">Buat Vendor Production Order</h2>
        </div>

        <form method="POST" action="{{ route('vendor.production-orders.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Referensi PO Item</label>
                <select name="purchase_order_item_id" class="w-full border rounded px-3 py-2 text-sm" required>
                    <option value="">-- Pilih PO Item --</option>
                    @foreach($poItems as $item)
                    <option value="{{ $item->id }}" {{ old('purchase_order_item_id') == $item->id ? 'selected' : '' }}>
                        {{ $item->purchaseOrder?->po_number }} - [{{ $item->material?->code }}] {{ $item->material?->name }} - Available: {{ number_format($item->available_qty, 3) }} {{ $item->material?->unit_of_measure }}
                    </option>
                    @endforeach
                </select>
                @error('purchase_order_item_id')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                @if($poItems->isEmpty())
                    <p class="text-amber-700 text-xs mt-1">Tidak ada PO item aktif (Approved/Partially Received) dengan sisa qty untuk vendor Anda.</p>
                @endif
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Qty Planned</label>
                    <input type="number" name="quantity_planned" step="0.001" min="0.001" value="{{ old('quantity_planned') }}" class="w-full border rounded px-3 py-2 text-sm" required>
                    @error('quantity_planned')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Planned Start</label>
                    <input type="date" name="planned_start_date" value="{{ old('planned_start_date') }}" class="w-full border rounded px-3 py-2 text-sm">
                    @error('planned_start_date')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Planned End</label>
                    <input type="date" name="planned_end_date" value="{{ old('planned_end_date') }}" class="w-full border rounded px-3 py-2 text-sm">
                    @error('planned_end_date')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
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
