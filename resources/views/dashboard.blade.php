<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-3 md:p-5 border-l-4 border-blue-600">
            <div class="text-xs md:text-sm text-gray-500">Total Material</div>
            <div class="text-2xl md:text-3xl font-bold text-blue-700">{{ $total_materials }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-3 md:p-5 border-l-4 border-green-600">
            <div class="text-xs md:text-sm text-gray-500">Total Vendor</div>
            <div class="text-2xl md:text-3xl font-bold text-green-700">{{ $total_vendors }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-3 md:p-5 border-l-4 border-yellow-500">
            <div class="text-xs md:text-sm text-gray-500">PO Open</div>
            <div class="text-2xl md:text-3xl font-bold text-yellow-600">{{ $open_pos }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-3 md:p-5 border-l-4 border-red-500">
            <div class="text-xs md:text-sm text-gray-500">Produksi Aktif</div>
            <div class="text-2xl md:text-3xl font-bold text-red-600">{{ $active_production }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Recent PO --}}
        <div class="lg:col-span-1 bg-white rounded-lg shadow p-5">
            <h3 class="font-semibold text-gray-700 mb-3">Purchase Order Terbaru</h3>
            @forelse($recent_pos as $po)
            <div class="flex justify-between items-center py-2 border-b text-sm">
                <div>
                    <div class="font-medium text-blue-700">{{ $po->po_number }}</div>
                    <div class="text-gray-500 text-xs">{{ $po->vendor->name ?? '-' }}</div>
                </div>
                <span class="px-2 py-1 text-xs rounded-full
                    {{ $po->status === 'draft' ? 'bg-gray-100 text-gray-600' : '' }}
                    {{ $po->status === 'approved' ? 'bg-blue-100 text-blue-700' : '' }}
                    {{ $po->status === 'received' ? 'bg-green-100 text-green-700' : '' }}
                    {{ $po->status === 'cancelled' ? 'bg-red-100 text-red-700' : '' }}
                    {{ $po->status === 'partially_received' ? 'bg-yellow-100 text-yellow-700' : '' }}
                ">{{ ucfirst(str_replace('_', ' ', $po->status)) }}</span>
            </div>
            @empty
            <p class="text-sm text-gray-400">Belum ada Purchase Order.</p>
            @endforelse
        </div>

        {{-- Recent Production --}}
        <div class="lg:col-span-1 bg-white rounded-lg shadow p-5">
            <h3 class="font-semibold text-gray-700 mb-3">Production Order Terbaru</h3>
            @forelse($recent_production as $order)
            <div class="flex justify-between items-center py-2 border-b text-sm">
                <div>
                    <div class="font-medium text-blue-700">{{ $order->order_number }}</div>
                    <div class="text-gray-500 text-xs">{{ $order->material->name ?? '-' }}</div>
                </div>
                <span class="px-2 py-1 text-xs rounded-full
                    {{ in_array($order->status, ['created']) ? 'bg-gray-100 text-gray-600' : '' }}
                    {{ in_array($order->status, ['released', 'in_progress']) ? 'bg-yellow-100 text-yellow-700' : '' }}
                    {{ in_array($order->status, ['completed']) ? 'bg-green-100 text-green-700' : '' }}
                    {{ in_array($order->status, ['cancelled']) ? 'bg-red-100 text-red-700' : '' }}
                ">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span>
            </div>
            @empty
            <p class="text-sm text-gray-400">Belum ada Production Order.</p>
            @endforelse
        </div>

        {{-- Low Stock Alert --}}
        <div class="lg:col-span-1 bg-white rounded-lg shadow p-5">
            <h3 class="font-semibold text-gray-700 mb-3 text-red-600">&#9888; Low Stock Alert</h3>
            @forelse($low_stock_materials as $stock)
            <div class="flex justify-between items-center py-2 border-b text-sm">
                <div>
                    <div class="font-medium">{{ $stock->material->code ?? '-' }}</div>
                    <div class="text-gray-500 text-xs">{{ $stock->material->name ?? '-' }}</div>
                    <div class="text-xs text-gray-400">{{ $stock->storageLocation->name ?? '-' }}</div>
                </div>
                <span class="font-bold text-red-600">{{ number_format($stock->quantity, 2) }}</span>
            </div>
            @empty
            <p class="text-sm text-gray-400">Semua stok dalam batas aman.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
