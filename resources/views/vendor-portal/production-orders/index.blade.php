<x-vendor-layout>
    <x-slot name="title">Vendor Production Orders</x-slot>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-700">Vendor Production Orders</h2>
            <a href="{{ route('vendor.production-orders.create') }}" class="bg-teal-700 text-white px-4 py-2 rounded text-sm hover:bg-teal-800">
                + Buat Order
            </a>
        </div>

        <form method="GET" class="flex flex-wrap md:flex-nowrap gap-2 mb-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="No. Order / Material..."
                class="border rounded px-3 py-2 text-sm w-full md:flex-1 md:min-w-[26rem]">
            <select name="status" class="border rounded px-3 py-2 text-sm">
                <option value="">Semua Status</option>
                @foreach(['draft', 'released', 'in_progress', 'completed', 'cancelled'] as $status)
                    <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ strtoupper($status) }}</option>
                @endforeach
            </select>
            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded text-sm">Filter</button>
            <a href="{{ route('vendor.production-orders.index') }}" class="bg-gray-100 text-gray-600 px-4 py-2 rounded text-sm border">Reset</a>
        </form>

        <table class="w-full text-sm border-collapse">
            <thead class="bg-teal-700 text-white">
                <tr>
                    <th class="px-4 py-2 text-left">No. Order</th>
                    <th class="px-4 py-2 text-left">Referensi PO</th>
                    <th class="px-4 py-2 text-left">Material</th>
                    <th class="px-4 py-2 text-right">Planned</th>
                    <th class="px-4 py-2 text-right">OK</th>
                    <th class="px-4 py-2 text-right">NG</th>
                    <th class="px-4 py-2 text-left">Surat Jalan</th>
                    <th class="px-4 py-2 text-center">Status</th>
                    <th class="px-4 py-2 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-teal-700">{{ $order->order_number }}</td>
                    <td class="px-4 py-2 font-mono text-xs text-gray-600">{{ $order->purchaseOrderItem?->purchaseOrder?->po_number ?? '-' }}</td>
                    <td class="px-4 py-2">
                        <div class="font-mono text-xs text-gray-500">{{ $order->material?->code }}</div>
                        <div>{{ $order->material?->name }}</div>
                    </td>
                    <td class="px-4 py-2 text-right">{{ number_format($order->quantity_planned, 3) }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($order->quantity_ok, 3) }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($order->quantity_ng, 3) }}</td>
                    <td class="px-4 py-2 font-mono text-xs text-teal-700">{{ $order->deliveryNote?->dn_number ?? '-' }}</td>
                    <td class="px-4 py-2 text-center">
                        <span class="px-2 py-0.5 rounded text-xs {{ $order->statusColor() }}">{{ $order->statusLabel() }}</span>
                    </td>
                    <td class="px-4 py-2 text-center">
                        <a href="{{ route('vendor.production-orders.show', $order) }}" class="bg-teal-600 text-white px-3 py-1 rounded text-xs hover:bg-teal-700">Detail</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-4 text-center text-gray-400">Belum ada order produksi vendor.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">{{ $orders->links() }}</div>
    </div>
</x-vendor-layout>
