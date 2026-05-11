<x-vendor-layout>
    <x-slot name="title">Stok Material</x-slot>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-700">Stok Material di Vendor</h2>
                <p class="text-xs text-gray-400 mt-0.5">Stok aktual material yang ada di vendor Anda saat ini.</p>
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

        @if($stocks->isEmpty())
            <div class="py-10 text-center text-gray-400 text-sm">
                Belum ada stok material di vendor Anda. Konfirmasi kiriman bahan dari IPPI terlebih dahulu.
            </div>
        @else
        <table class="w-full text-sm border-collapse">
            <thead class="bg-teal-700 text-white">
                <tr>
                    <th class="px-4 py-2 text-left">Tipe</th>
                    <th class="px-4 py-2 text-left">Kode</th>
                    <th class="px-4 py-2 text-left">Nama Material</th>
                    <th class="px-4 py-2 text-right">Stok</th>
                    <th class="px-4 py-2 text-left">Satuan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stocks as $s)
                @php $m = $s->material; @endphp
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-2">
                        <span class="px-2 py-0.5 rounded text-xs font-semibold
                            {{ $m?->type==='RM'  ? 'bg-gray-200 text-gray-700'     : '' }}
                            {{ $m?->type==='WIP' ? 'bg-yellow-200 text-yellow-800' : '' }}
                            {{ $m?->type==='FP'  ? 'bg-green-200 text-green-800'   : '' }}">
                            {{ $m?->type ?? '—' }}
                        </span>
                    </td>
                    <td class="px-4 py-2 font-mono text-xs text-teal-700">{{ $m?->code }}</td>
                    <td class="px-4 py-2">{{ $m?->name }}</td>
                    <td class="px-4 py-2 text-right font-bold text-teal-700">{{ number_format($s->quantity, 3) }}</td>
                    <td class="px-4 py-2 text-xs text-gray-500">{{ $m?->unit_of_measure }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</x-vendor-layout>

