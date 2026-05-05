<x-vendor-layout>
    <x-slot name="title">Kiriman Bahan dari IPPI</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Kiriman Bahan dari IPPI</h2>

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
