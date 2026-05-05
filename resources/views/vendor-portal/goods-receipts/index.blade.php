<x-vendor-layout>
    <x-slot name="title">Goods Receipt Saya</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-700">Daftar Goods Receipt</h2>
            <a href="{{ route('vendor.goods-receipts.create') }}" class="bg-blue-700 text-white px-4 py-2 rounded text-sm hover:bg-blue-800">+ Buat GR</a>
        </div>

        <form method="GET" class="flex flex-wrap gap-2 mb-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="No. GR / No. PO..."
                class="border rounded px-3 py-2 text-sm flex-1 min-w-48">
            <input type="date" name="date_from" value="{{ request('date_from') }}" title="Dari tanggal" class="border rounded px-3 py-2 text-sm">
            <input type="date" name="date_to"   value="{{ request('date_to') }}"   title="Sampai tanggal" class="border rounded px-3 py-2 text-sm">
            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded text-sm">Filter</button>
            <a href="{{ route('vendor.goods-receipts.index') }}" class="bg-gray-100 text-gray-600 px-4 py-2 rounded text-sm border hover:bg-gray-200">Reset</a>
        </form>

        <table class="w-full text-sm border-collapse">
            <thead class="bg-teal-800 text-white">
                <tr>
                    <th class="px-4 py-2 text-left">No. GR</th>
                    <th class="px-4 py-2 text-left">No. PO</th>
                    <th class="px-4 py-2 text-left">Tgl Terima</th>
                    <th class="px-4 py-2 text-left">Lokasi</th>
                    <th class="px-4 py-2 text-center">Status</th>
                    <th class="px-4 py-2 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($receipts as $gr)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-teal-700">{{ $gr->gr_number }}</td>
                    <td class="px-4 py-2 font-mono text-gray-600 text-xs">{{ $gr->purchaseOrder?->po_number ?? '-' }}</td>
                    <td class="px-4 py-2">{{ $gr->receipt_date?->format('d/m/Y') ?? '-' }}</td>
                    <td class="px-4 py-2">{{ $gr->storageLocation?->name ?? '-' }}</td>
                    <td class="px-4 py-2 text-center">
                        <span class="px-2 py-0.5 rounded text-xs
                            {{ $gr->status==='draft'?'bg-gray-100 text-gray-600':'' }}
                            {{ $gr->status==='posted'?'bg-green-100 text-green-700':'' }}
                        ">{{ ucfirst($gr->status ?? '-') }}</span>
                    </td>
                    <td class="px-4 py-2 text-center">
                        <a href="{{ route('vendor.goods-receipts.show', $gr) }}" class="text-teal-600 hover:underline text-xs">Detail</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-4 text-center text-gray-400">Belum ada Goods Receipt untuk vendor Anda.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $receipts->links() }}</div>
    </div>
</x-vendor-layout>
