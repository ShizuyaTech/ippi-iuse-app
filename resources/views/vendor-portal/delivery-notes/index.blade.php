<x-vendor-layout>
    <x-slot name="title">Surat Jalan Saya</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-700">Daftar Surat Jalan</h2>
            <a href="{{ route('vendor.delivery-notes.create') }}" class="bg-blue-700 text-white px-4 py-2 rounded text-sm hover:bg-blue-800">+ Buat Surat Jalan</a>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded text-sm">{{ session('success') }}</div>
        @endif

        <form method="GET" class="flex flex-wrap gap-2 mb-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="No. SJ / No. PO..."
                class="border rounded px-3 py-2 text-sm flex-1 min-w-48">
            <select name="status" class="border rounded px-3 py-2 text-sm">
                <option value="">Semua Status</option>
                <option value="pending"   {{ request('status')=='pending'   ? 'selected' : '' }}>Menunggu Konfirmasi</option>
                <option value="confirmed" {{ request('status')=='confirmed' ? 'selected' : '' }}>Dikonfirmasi</option>
                <option value="received"  {{ request('status')=='received'  ? 'selected' : '' }}>Sudah Diterima</option>
                <option value="cancelled" {{ request('status')=='cancelled' ? 'selected' : '' }}>Dibatalkan</option>
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" title="Estimasi dari" class="border rounded px-3 py-2 text-sm">
            <input type="date" name="date_to"   value="{{ request('date_to') }}"   title="Estimasi sampai" class="border rounded px-3 py-2 text-sm">
            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded text-sm">Filter</button>
            <a href="{{ route('vendor.delivery-notes.index') }}" class="bg-gray-100 text-gray-600 px-4 py-2 rounded text-sm border hover:bg-gray-200">Reset</a>
        </form>

        <table class="w-full text-sm border-collapse">
            <thead class="bg-teal-800 text-white">
                <tr>
                    <th class="px-4 py-2 text-left">No. Surat Jalan</th>
                    <th class="px-4 py-2 text-left">No. PO</th>
                    <th class="px-4 py-2 text-left">Est. Pengiriman</th>
                    <th class="px-4 py-2 text-left">Kendaraan</th>
                    <th class="px-4 py-2 text-center">Status</th>
                    <th class="px-4 py-2 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deliveryNotes as $dn)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-teal-700">
                        {{ $dn->dn_number }}
                        @if($dn->source_type === 'vendor_production_order' && $dn->source_id)
                            <span class="ml-2 px-2 py-0.5 rounded bg-indigo-100 text-indigo-700 text-[10px]">AUTO VPO</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 font-mono text-gray-600 text-xs">{{ $dn->purchaseOrder?->po_number ?? '-' }}</td>
                    <td class="px-4 py-2">{{ $dn->estimated_delivery_date?->format('d/m/Y') ?? '-' }}</td>
                    <td class="px-4 py-2 text-xs text-gray-600">{{ $dn->vehicle_number ?? '-' }}</td>
                    <td class="px-4 py-2 text-center">
                        <span class="px-2 py-0.5 rounded text-xs {{ $dn->statusColor() }}">{{ $dn->statusLabel() }}</span>
                    </td>
                    <td class="px-4 py-2 text-center space-x-2">
                        <a href="{{ route('vendor.delivery-notes.show', $dn) }}" class="text-teal-600 hover:underline text-xs">Detail</a>
                        @if($dn->status === 'pending')
                        <form method="POST" action="{{ route('vendor.delivery-notes.cancel', $dn) }}" class="inline" onsubmit="return confirm('Batalkan surat jalan ini?')">
                            @csrf @method('PATCH')
                            <button type="submit" class="text-red-500 hover:underline text-xs">Batalkan</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-4 text-center text-gray-400">Belum ada Surat Jalan.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $deliveryNotes->links() }}</div>
    </div>
</x-vendor-layout>
