<x-vendor-layout>
    <x-slot name="title">Incoming</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-wrap justify-between items-center gap-2 mb-4">
            <h2 class="text-lg font-semibold text-gray-700">Kiriman Bahan dari IPPI</h2>
            <div class="flex flex-wrap gap-2 items-center">
                <a href="{{ route('vendor.material-receipts.export-excel', request()->query()) }}"
                   class="bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700">
                    Export Excel
                </a>
                <a href="{{ route('vendor.material-receipts.export-pdf', request()->query()) }}" target="_blank"
                   class="bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700">
                    Print PDF
                </a>
            </div>
        </div>

        <form method="GET" class="flex flex-wrap gap-2 mb-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="No. VMD..."
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm flex-1 min-w-48">
            <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Semua Status</option>
                <option value="draft"     {{ request('status')==='draft'     ? 'selected' : '' }}>Draft</option>
                <option value="sent"      {{ request('status')==='sent'      ? 'selected' : '' }}>Terkirim</option>
                <option value="confirmed" {{ request('status')==='confirmed' ? 'selected' : '' }}>Dikonfirmasi</option>
                <option value="cancelled" {{ request('status')==='cancelled' ? 'selected' : '' }}>Dibatalkan</option>
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" title="Dari tanggal" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <input type="date" name="date_to"   value="{{ request('date_to') }}"   title="Sampai tanggal" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded text-sm">Filter</button>
            <a href="{{ route('vendor.material-receipts.index') }}" class="bg-gray-100 text-gray-600 px-4 py-2 rounded text-sm border hover:bg-gray-200">Reset</a>
        </form>

        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded text-sm">{{ session('success') }}</div>
        @endif

        <table class="w-full text-sm border-collapse">
            <thead class="bg-teal-700 text-white">
                <tr>
                    <th class="px-4 py-2 text-left">No. VMD</th>
                    <th class="px-4 py-2 text-left">Tanggal Kirim</th>
                    <th class="px-4 py-2 text-center">Item</th>
                    <th class="px-4 py-2 text-center">Status</th>
                    <th class="px-4 py-2 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($receipts as $r)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-teal-700">{{ $r->vmd_number }}</td>
                    <td class="px-4 py-2">{{ $r->delivery_date?->format('d/m/Y') }}</td>
                    <td class="px-4 py-2 text-center text-gray-500">{{ $r->items->count() }} item</td>
                    <td class="px-4 py-2 text-center">
                        <span class="px-2 py-0.5 rounded text-xs {{ $r->statusColor() }}">{{ $r->statusLabel() }}</span>
                    </td>
                    <td class="px-4 py-2 text-center">
                        <a href="{{ route('vendor.material-receipts.show', $r) }}"
                           class="bg-teal-600 text-white px-3 py-1 rounded text-xs hover:bg-teal-700">Detail</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-4 text-center text-gray-400">Belum ada kiriman bahan dari IPPI.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $receipts->links() }}</div>
    </div>
</x-vendor-layout>
