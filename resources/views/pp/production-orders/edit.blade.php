<x-app-layout>
    <x-slot name="title">Edit Production Order</x-slot>
    <div class="bg-white rounded-lg shadow p-6 max-w-2xl">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Edit Production Order: {{ $productionOrder->order_number }}</h2>
        <form method="POST" action="{{ route('pp.production-orders.update', $productionOrder) }}" class="space-y-4">
            @csrf @method('PATCH')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Material *</label>
                <select name="material_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                    @foreach($materials as $m)
                    <option value="{{ $m->id }}" {{ old('material_id', $productionOrder->material_id)==$m->id?'selected':'' }}>{{ $m->code }} - {{ $m->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">BOM *</label>
                    <select name="bom_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                        @foreach($boms as $bom)
                        <option value="{{ $bom->id }}" {{ old('bom_id', $productionOrder->bom_id)==$bom->id?'selected':'' }}>{{ $bom->bom_number }} ({{ $bom->material->name }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Routing</label>
                    <select name="routing_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">-- Opsional --</option>
                        @foreach($routings as $rtg)
                        <option value="{{ $rtg->id }}" {{ old('routing_id', $productionOrder->routing_id)==$rtg->id?'selected':'' }}>{{ $rtg->routing_number }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Qty Planned *</label>
                    <input type="number" name="quantity_planned" value="{{ old('quantity_planned', $productionOrder->quantity_planned) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" min="0.001" step="0.001" required>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tgl Mulai Rencana</label>
                    <input type="date" name="planned_start_date" value="{{ old('planned_start_date', $productionOrder->planned_start_date?->format('Y-m-d')) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tgl Selesai Rencana</label>
                    <input type="date" name="planned_end_date" value="{{ old('planned_end_date', $productionOrder->planned_end_date?->format('Y-m-d')) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('notes', $productionOrder->notes) }}</textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="bg-blue-700 text-white px-6 py-2 rounded text-sm">Perbarui</button>
                <a href="{{ route('pp.production-orders.show', $productionOrder) }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded text-sm">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
