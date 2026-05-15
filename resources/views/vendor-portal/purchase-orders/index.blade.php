<x-vendor-layout>
    <x-slot name="title">Purchase Order</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-wrap justify-between items-center gap-2 mb-4">
            <h2 class="text-lg font-semibold text-gray-700">Daftar Purchase Order</h2>
            <div class="flex flex-wrap gap-2 items-center">
                <a href="{{ route('vendor.purchase-orders.export-excel', request()->query()) }}"
                   class="bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700">
                    Export Excel
                </a>
                <a href="{{ route('vendor.purchase-orders.export-pdf', request()->query()) }}" target="_blank"
                   class="bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700">
                    Print PDF
                </a>
            </div>
        </div>

        <form method="GET" class="flex flex-wrap gap-2 mb-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="No. PO..."
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm flex-1 min-w-48">
            <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Semua Status</option>
                @foreach(['draft','approved','partially_received','completed','cancelled'] as $st)
                <option value="{{ $st }}" {{ request('status')==$st?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$st)) }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" title="Dari tanggal" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <input type="date" name="date_to"   value="{{ request('date_to') }}"   title="Sampai tanggal" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded text-sm">Filter</button>
            <a href="{{ route('vendor.purchase-orders.index') }}" class="bg-gray-100 text-gray-600 px-4 py-2 rounded text-sm border hover:bg-gray-200">Reset</a>
        </form>

        <table class="w-full text-sm border-collapse">
            <thead class="bg-teal-800 text-white">
                <tr>
                    <th class="px-4 py-2 text-left">No. PO</th>
                    <th class="px-4 py-2 text-left">Tgl Order</th>
                    <th class="px-4 py-2 text-right">Total Item</th>
                    <th class="px-4 py-2 text-center">Status</th>
                    <th class="px-4 py-2 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pos as $po)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-teal-700 font-medium">{{ $po->po_number }}</td>
                    <td class="px-4 py-2">{{ $po->order_date?->format('d/m/Y') ?? '-' }}</td>
                    <td class="px-4 py-2 text-right">{{ $po->items->count() }}</td>
                    <td class="px-4 py-2 text-center">
                        <span class="px-2 py-0.5 rounded text-xs
                            {{ $po->status==='draft'?'bg-gray-100 text-gray-600':'' }}
                            {{ $po->status==='approved'?'bg-blue-100 text-blue-700':'' }}
                            {{ $po->status==='partially_received'?'bg-yellow-100 text-yellow-700':'' }}
                            {{ $po->status==='completed'?'bg-green-100 text-green-700':'' }}
                            {{ $po->status==='cancelled'?'bg-red-100 text-red-700':'' }}
                        ">{{ ucfirst(str_replace('_',' ',$po->status)) }}</span>
                    </td>
                    <td class="px-4 py-2 text-center">
                        <div class="flex gap-2 justify-center">
                            <a href="{{ route('vendor.purchase-orders.show', $po) }}" class="text-teal-600 hover:underline text-xs">Detail</a>
                            @if(in_array($po->status, ['approved','partially_received']))
                                <span class="text-gray-300 text-xs">|</span>
                                <a href="{{ route('vendor.delivery-notes.create', ['po_id' => $po->id]) }}"
                                   class="text-blue-600 hover:underline text-xs">Buat Surat Jalan</a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-4 text-center text-gray-400">Belum ada Purchase Order untuk vendor Anda.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $pos->links() }}</div>
    </div>
</x-vendor-layout>
