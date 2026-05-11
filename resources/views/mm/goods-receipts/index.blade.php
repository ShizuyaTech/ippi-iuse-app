<x-app-layout>
    <x-slot name="title">Goods Receipt</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-wrap justify-between items-center gap-2 mb-4">
            <h2 class="text-lg font-semibold text-gray-700">Goods Receipt</h2>
            <div class="flex flex-wrap gap-2 items-center print:hidden">
                <a href="{{ route('mm.goods-receipts.export', request()->query()) }}" class="inline-flex items-center gap-1.5 bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export Excel
                </a>
                <a href="{{ route('mm.goods-receipts.export-pdf', request()->query()) }}" target="_blank" class="inline-flex items-center gap-1.5 bg-red-700 text-white px-4 py-2 rounded text-sm hover:bg-red-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print PDF
                </a>
                <a href="{{ route('mm.goods-receipts.create') }}" class="bg-blue-700 text-white px-4 py-2 rounded text-sm hover:bg-blue-800">+ Buat GR</a>
            </div>
        </div>

        <form method="GET" class="flex flex-wrap gap-2 mb-4 print:hidden">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="No. GR / No. PO..." class="border rounded px-3 py-2 text-sm flex-1 min-w-48">
            <input type="date" name="date_from" value="{{ request('date_from') }}" title="Dari tanggal terima" class="border rounded px-3 py-2 text-sm">
            <input type="date" name="date_to"   value="{{ request('date_to') }}"   title="Sampai tanggal terima" class="border rounded px-3 py-2 text-sm">
            <select name="vendor_id" class="border rounded px-3 py-2 text-sm">
                <option value="">Semua Vendor</option>
                @foreach($vendors as $v)
                <option value="{{ $v->id }}" {{ request('vendor_id')==$v->id?'selected':'' }}>{{ $v->name }}</option>
                @endforeach
            </select>
            <select name="location_id" class="border rounded px-3 py-2 text-sm">
                <option value="">Semua Lokasi</option>
                @foreach($locations as $loc)
                <option value="{{ $loc->id }}" {{ request('location_id')==$loc->id?'selected':'' }}>{{ $loc->code }} - {{ $loc->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded text-sm">Cari</button>
            <a href="{{ route('mm.goods-receipts.index') }}" class="bg-gray-100 text-gray-600 px-4 py-2 rounded text-sm border hover:bg-gray-200">Reset</a>
        </form>

        <table id="data-table" class="w-full text-sm border-collapse">
            <thead class="bg-blue-900 text-white">
                <tr>
                    <th class="px-4 py-2 text-left">No. GR</th>
                    <th class="px-4 py-2 text-left">No. PO</th>
                    <th class="px-4 py-2 text-left">Vendor</th>
                    <th class="px-4 py-2 text-left">Tgl Terima</th>
                    <th class="px-4 py-2 text-left">Lokasi</th>
                    <th class="px-4 py-2 text-center">Status</th>
                    <th class="px-4 py-2 text-center print:hidden">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($receipts as $gr)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-blue-700 font-medium">{{ $gr->gr_number }}</td>
                    <td class="px-4 py-2 font-mono">{{ $gr->purchaseOrder->po_number ?? '-' }}</td>
                    <td class="px-4 py-2">{{ $gr->purchaseOrder->vendor->name ?? '-' }}</td>
                    <td class="px-4 py-2">{{ $gr->receipt_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-2">{{ $gr->storageLocation->name ?? '-' }}</td>
                    <td class="px-4 py-2 text-center"><span class="px-2 py-0.5 rounded text-xs bg-green-100 text-green-700">{{ $gr->status }}</span></td>
                    <td class="px-4 py-2 text-center print:hidden">
                        <div class="flex justify-center gap-2">
                            <a href="{{ route('mm.goods-receipts.show', $gr) }}" class="text-blue-600 hover:underline">Detail</a>
                            <a href="{{ route('mm.goods-receipts.edit', $gr) }}" class="text-yellow-600 hover:underline">Edit</a>
                            <form method="POST" action="{{ route('mm.goods-receipts.destroy', $gr) }}" onsubmit="return confirm('Hapus GR {{ $gr->gr_number }}? Stok akan dibalik.')">
                                @csrf @method('DELETE')
                                <button class="text-red-600 hover:underline">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-4 text-center text-gray-400">Belum ada Goods Receipt.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4 print:hidden">{{ $receipts->links() }}</div>
    </div>
</x-app-layout>
