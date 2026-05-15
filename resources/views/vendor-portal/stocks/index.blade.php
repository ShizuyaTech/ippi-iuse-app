<x-vendor-layout>
    <x-slot name="title">Stock Overview</x-slot>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-wrap justify-between items-center gap-2 mb-4">
            <h2 class="text-lg font-semibold text-gray-700">Stok Material di Vendor</h2>
            <div class="flex flex-wrap gap-2 items-center">
                <a href="{{ route('vendor.stocks.export-excel', request()->query()) }}"
                   class="inline-flex items-center gap-1.5 bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export Excel
                </a>
                <a href="{{ route('vendor.stocks.print-pdf', request()->query()) }}" target="_blank"
                   class="inline-flex items-center gap-1.5 bg-red-700 text-white px-4 py-2 rounded text-sm hover:bg-red-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print PDF
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded text-sm">{{ session('success') }}</div>
        @endif

        {{-- Filter --}}
        <form method="GET" class="flex flex-wrap gap-2 mb-5">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Kode / Nama Material..."
                   class="border border-gray-300 rounded-lg px-3 py-2 text-sm flex-1 min-w-48">
            <select name="type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
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
                    <th class="px-4 py-2 text-left">Kode</th>
                    <th class="px-4 py-2 text-left">Nama Material</th>
                    <th class="px-4 py-2 text-left">Tipe</th>
                    <th class="px-4 py-2 text-right">Stok</th>
                    <th class="px-4 py-2 text-left">Satuan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stocks as $s)
                @php $m = $s->material; @endphp
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-xs text-teal-700">{{ $m?->code }}</td>
                    <td class="px-4 py-2">{{ $m?->name }}</td>
                    <td class="px-4 py-2">
                        <span class="px-2 py-0.5 rounded text-xs font-semibold
                            {{ $m?->type==='RM'  ? 'bg-gray-200 text-gray-700'     : '' }}
                            {{ $m?->type==='WIP' ? 'bg-yellow-200 text-yellow-800' : '' }}
                            {{ $m?->type==='FP'  ? 'bg-green-200 text-green-800'   : '' }}">
                            {{ $m?->type ?? '—' }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-right font-bold text-teal-700">{{ fmt_qty($s->quantity) }}</td>
                    <td class="px-4 py-2 text-xs text-gray-500">{{ $m?->unit_of_measure }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</x-vendor-layout>

