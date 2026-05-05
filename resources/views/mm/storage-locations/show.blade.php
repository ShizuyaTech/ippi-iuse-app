<x-app-layout>
    <x-slot name="title">Detail Storage Location</x-slot>
    <div class="bg-white rounded-lg shadow p-6 max-w-3xl">
        <div class="flex justify-between items-start mb-4">
            <div>
                <div class="text-xs text-gray-400">Kode</div>
                <div class="text-2xl font-bold text-blue-700 font-mono">{{ $storageLocation->code }}</div>
                <div class="text-gray-600 mt-1">{{ $storageLocation->name }}</div>
                @if($storageLocation->is_scrap)
                    <span class="inline-block mt-1 bg-red-100 text-red-700 text-xs font-semibold px-2 py-0.5 rounded-full">Lokasi Scrap — tidak dihitung di MRP</span>
                @endif
            </div>
            <div class="flex gap-2">
                <a href="{{ route('mm.storage-locations.edit', $storageLocation) }}" class="bg-yellow-500 text-white px-4 py-2 rounded text-sm">Edit</a>
                <a href="{{ route('mm.storage-locations.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm">Kembali</a>
            </div>
        </div>
        <h3 class="font-semibold text-gray-700 mb-3">Stok di Lokasi Ini</h3>
        <table class="w-full text-sm border-collapse">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left">Kode Material</th>
                    <th class="px-4 py-2 text-left">Nama Material</th>
                    <th class="px-4 py-2 text-right">Qty</th>
                    <th class="px-4 py-2 text-left">UoM</th>
                </tr>
            </thead>
            <tbody>
                @forelse($storageLocation->stocks as $stock)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-blue-700">{{ $stock->material->code ?? '-' }}</td>
                    <td class="px-4 py-2">{{ $stock->material->name ?? '-' }}</td>
                    <td class="px-4 py-2 text-right font-medium {{ $stock->quantity <= 0 ? 'text-red-600' : 'text-green-700' }}">{{ number_format($stock->quantity, 3) }}</td>
                    <td class="px-4 py-2">{{ $stock->material->unit_of_measure ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-4 text-center text-gray-400">Belum ada stok di lokasi ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
