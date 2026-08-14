<x-app-layout>
    <x-slot name="title">Detail Material</x-slot>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <div class="text-xs text-gray-400">Kode Material</div>
                    <div class="text-2xl font-bold text-blue-700 font-mono">{{ $material->code }}</div>
                </div>
                <span class="px-3 py-1 text-sm rounded-full {{ $material->is_active?'bg-green-100 text-green-700':'bg-red-100 text-red-700' }}">{{ $material->is_active?'Aktif':'Nonaktif' }}</span>
            </div>
            @php
                $totalStock = $material->stocks->sum('quantity');
                $isLow = $material->min_stock > 0 && $totalStock < $material->min_stock;
            @endphp
            @if($isLow)
            <div class="mb-3 flex items-center gap-2 bg-red-50 border border-red-300 rounded px-3 py-2 text-sm text-red-700">
                <span class="text-base">⚠</span>
                <span><strong>Stok Minim!</strong> Total stok <strong>{{ number_format($totalStock, 3) }}</strong> di bawah minimum <strong>{{ number_format($material->min_stock, 3) }} {{ $material->unit_of_measure }}</strong></span>
            </div>
            @endif
            <div class="space-y-2 text-sm">
                <div><span class="text-gray-500">Nama:</span> <span class="font-medium">{{ $material->name }}</span></div>
                <div><span class="text-gray-500">Tipe:</span> <span class="font-medium">{{ $material->type }}</span></div>
                <div><span class="text-gray-500">UoM:</span> <span class="font-medium">{{ $material->unit_of_measure }}</span></div>
                <div><span class="text-gray-500">Harga Std:</span> <span class="font-medium">{{ number_format($material->standard_price,2) }}</span></div>
                <div><span class="text-gray-500">Qty per Case:</span> <span class="font-medium">{{ $material->qty_per_case > 0 ? number_format($material->qty_per_case, 3).' '.$material->unit_of_measure.' / case' : '-' }}</span></div>
                <div><span class="text-gray-500">Minimal Stok:</span> <span class="font-medium {{ $isLow ? 'text-red-700' : '' }}">{{ $material->min_stock > 0 ? number_format($material->min_stock, 3).' '.$material->unit_of_measure : '-' }}</span></div>
                @if($material->description)
                <div><span class="text-gray-500">Deskripsi:</span><br><span>{{ $material->description }}</span></div>
                @endif
            </div>
            <div class="mt-4 flex gap-2">
                <a href="{{ route('mm.materials.edit', $material) }}" class="bg-yellow-500 text-white px-4 py-2 rounded text-sm hover:bg-yellow-600">Edit</a>
                <form method="POST" action="{{ route('mm.materials.destroy', $material) }}" onsubmit="return confirm('Hapus material ini?')">
                    @csrf @method('DELETE')
                    <button class="bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700">Hapus</button>
                </form>
                <a href="{{ route('mm.materials.index') }}" data-back-key="back_mm_materials" class="bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm">Kembali</a>
            </div>
        </div>

        <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-700 mb-3">Stok Per Gudang</h3>
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left">Storage Location</th>
                        <th class="px-4 py-2 text-right">Qty Tersedia</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($material->stocks as $stock)
                    <tr class="border-b">
                        <td class="px-4 py-2">{{ $stock->storageLocation->name ?? '-' }}</td>
                        <td class="px-4 py-2 text-right font-medium {{ $stock->quantity <= 0 ? 'text-red-600' : 'text-green-700' }}">{{ number_format($stock->quantity, 3) }} {{ $material->unit_of_measure }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="px-4 py-3 text-center text-gray-400">Belum ada stok.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <h3 class="font-semibold text-gray-700 mt-6 mb-3">Riwayat Pergerakan Stok (10 Terakhir)</h3>
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-3 py-2 text-left">Tanggal</th>
                        <th class="px-3 py-2 text-left">Tipe</th>
                        <th class="px-3 py-2 text-left">Referensi</th>
                        <th class="px-3 py-2 text-right">Qty</th>
                        <th class="px-3 py-2 text-right">Stok Akhir</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($material->stockMovements->take(10) as $mv)
                    <tr class="border-b">
                        <td class="px-3 py-2">{{ $mv->movement_date->format('d/m/Y') }}</td>
                        <td class="px-3 py-2"><span class="px-2 py-0.5 rounded text-xs {{ in_array($mv->movement_type,['GR'])? 'bg-green-100 text-green-700':'bg-red-100 text-red-700' }}">{{ $mv->movement_type }}</span></td>
                        <td class="px-3 py-2 font-mono text-xs">{{ $mv->reference_document }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($mv->quantity, 3) }}</td>
                        <td class="px-3 py-2 text-right font-medium">{{ number_format($mv->quantity_after, 3) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-3 py-3 text-center text-gray-400">Belum ada pergerakan stok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
