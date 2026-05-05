<x-vendor-layout>
    <x-slot name="title">Stok Material</x-slot>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-700">Stok Material</h2>
                <p class="text-xs text-gray-400 mt-0.5">Material yang diproses oleh vendor Anda beserta stok saat ini.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded text-sm">{{ session('success') }}</div>
        @endif

        {{-- Filter --}}
        <form method="GET" class="flex flex-wrap gap-2 mb-5">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Kode / Nama Material..."
                   class="border rounded px-3 py-2 text-sm flex-1 min-w-48">
            <select name="type" class="border rounded px-3 py-2 text-sm">
                <option value="">Semua Tipe</option>
                <option value="RM"  {{ request('type')==='RM'  ? 'selected' : '' }}>RM – Bahan Baku</option>
                <option value="WIP" {{ request('type')==='WIP' ? 'selected' : '' }}>WIP – Semi Jadi</option>
                <option value="FP"  {{ request('type')==='FP'  ? 'selected' : '' }}>FP – Produk Jadi</option>
            </select>
            <button type="submit" class="bg-teal-700 text-white px-4 py-2 rounded text-sm hover:bg-teal-800">Filter</button>
            <a href="{{ route('vendor.stocks.index') }}" class="bg-gray-100 text-gray-600 px-4 py-2 rounded text-sm border hover:bg-gray-200">Reset</a>
        </form>

        @if($materials->isEmpty())
            <div class="py-10 text-center text-gray-400 text-sm">
                Tidak ada material yang terkait dengan vendor Anda.
            </div>
        @else
        <div class="space-y-4">
            @foreach($materials as $material)
            @php
                $totalStock = $material->stocks->sum('quantity');
                $hasStock   = $totalStock > 0;
            @endphp
            <div class="border rounded-lg overflow-hidden {{ $hasStock ? '' : 'opacity-60' }}">
                {{-- Material header --}}
                <div class="flex items-center justify-between px-4 py-3
                            {{ $material->type==='RM'  ? 'bg-gray-50'   : '' }}
                            {{ $material->type==='WIP' ? 'bg-yellow-50' : '' }}
                            {{ $material->type==='FP'  ? 'bg-green-50'  : '' }}">
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-0.5 rounded text-xs font-semibold
                            {{ $material->type==='RM'  ? 'bg-gray-200 text-gray-700'    : '' }}
                            {{ $material->type==='WIP' ? 'bg-yellow-200 text-yellow-800': '' }}
                            {{ $material->type==='FP'  ? 'bg-green-200 text-green-800'  : '' }}
                        ">{{ $material->type }}</span>
                        <div>
                            <span class="font-mono text-xs text-gray-500">{{ $material->code }}</span>
                            <span class="ml-2 font-medium text-gray-800 text-sm">{{ $material->name }}</span>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-500">Total Stok</div>
                        <div class="font-bold {{ $hasStock ? 'text-teal-700' : 'text-gray-400' }}">
                            {{ number_format($totalStock, 3) }} <span class="text-xs font-normal text-gray-500">{{ $material->unit_of_measure }}</span>
                        </div>
                    </div>
                </div>

                {{-- Per-location breakdown --}}
                @if($material->stocks->isNotEmpty())
                <table class="w-full text-sm">
                    <thead class="bg-gray-100 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-4 py-2 text-left">Lokasi Penyimpanan</th>
                            <th class="px-4 py-2 text-left">Kode</th>
                            <th class="px-4 py-2 text-right">Qty</th>
                            <th class="px-4 py-2 text-left">Satuan</th>
                            <th class="px-4 py-2 text-center">Lokasi Vendor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($material->stocks->sortByDesc('quantity') as $stock)
                        <tr class="border-t hover:bg-gray-50
                            {{ $vendorLocationIds->contains($stock->storage_location_id) ? 'bg-teal-50/40' : '' }}">
                            <td class="px-4 py-2">{{ $stock->storageLocation?->name ?? '-' }}</td>
                            <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $stock->storageLocation?->code ?? '-' }}</td>
                            <td class="px-4 py-2 text-right font-medium">{{ number_format($stock->quantity, 3) }}</td>
                            <td class="px-4 py-2 text-xs text-gray-500">{{ $material->unit_of_measure }}</td>
                            <td class="px-4 py-2 text-center">
                                @if($vendorLocationIds->contains($stock->storage_location_id))
                                    <span class="px-2 py-0.5 rounded bg-teal-100 text-teal-700 text-xs">Lokasi Anda</span>
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="px-4 py-3 text-xs text-gray-400 italic">Stok kosong di semua lokasi.</div>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
</x-vendor-layout>
