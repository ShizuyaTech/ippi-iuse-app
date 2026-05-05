<x-app-layout>
    <x-slot name="title">Kiriman Bahan ke Vendor</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-700">Kiriman Bahan ke Vendor</h2>
            <a href="{{ route('mm.vendor-deliveries.create') }}" class="bg-blue-700 text-white px-4 py-2 rounded text-sm hover:bg-blue-800">+ Buat Kiriman</a>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded text-sm">{{ session('success') }}</div>
        @endif

        <form method="GET" class="flex flex-wrap gap-2 mb-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="No. VMD / Vendor..."
                class="border rounded px-3 py-2 text-sm flex-1 min-w-48">
            <select name="vendor_id" class="border rounded px-3 py-2 text-sm">
                <option value="">Semua Vendor</option>
                @foreach($vendors as $v)
                    <option value="{{ $v->id }}" {{ request('vendor_id')==$v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                @endforeach
            </select>
            <select name="status" class="border rounded px-3 py-2 text-sm">
                <option value="">Semua Status</option>
                <option value="sent"      {{ request('status')=='sent'      ? 'selected' : '' }}>Dikirim</option>
                <option value="confirmed" {{ request('status')=='confirmed' ? 'selected' : '' }}>Dikonfirmasi</option>
            </select>
            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded text-sm">Filter</button>
            <a href="{{ route('mm.vendor-deliveries.index') }}" class="bg-gray-100 text-gray-600 px-4 py-2 rounded text-sm border">Reset</a>
        </form>

        <table class="w-full text-sm border-collapse">
            <thead class="bg-blue-800 text-white">
                <tr>
                    <th class="px-4 py-2 text-left">No. VMD</th>
                    <th class="px-4 py-2 text-left">Vendor</th>
                    <th class="px-4 py-2 text-left">Tanggal Kirim</th>
                    <th class="px-4 py-2 text-center">Item</th>
                    <th class="px-4 py-2 text-center">Status</th>
                    <th class="px-4 py-2 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deliveries as $d)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-blue-700">{{ $d->vmd_number }}</td>
                    <td class="px-4 py-2">{{ $d->vendor?->name ?? '-' }}</td>
                    <td class="px-4 py-2">{{ $d->delivery_date?->format('d/m/Y') }}</td>
                    <td class="px-4 py-2 text-center text-gray-500">{{ $d->items_count }} item</td>
                    <td class="px-4 py-2 text-center">
                        <span class="px-2 py-0.5 rounded text-xs {{ $d->statusColor() }}">{{ $d->statusLabel() }}</span>
                    </td>
                    <td class="px-4 py-2 text-center">
                        <a href="{{ route('mm.vendor-deliveries.show', $d) }}"
                           class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700">Detail</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-4 text-center text-gray-400">Belum ada kiriman bahan ke vendor.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $deliveries->links() }}</div>
    </div>
</x-app-layout>
