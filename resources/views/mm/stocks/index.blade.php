<x-app-layout>
    <x-slot name="title">Stock Overview</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-wrap justify-between items-center gap-2 mb-4">
            <h2 class="text-lg font-semibold text-gray-700">Stock Overview</h2>
            <div class="flex flex-wrap gap-2 items-center print:hidden">
                <a href="{{ route('mm.stocks.export', request()->query()) }}" class="inline-flex items-center gap-1.5 bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export Excel
                </a>
                <a href="{{ route('mm.stocks.export-pdf', request()->query()) }}" target="_blank" class="inline-flex items-center gap-1.5 bg-red-700 text-white px-4 py-2 rounded text-sm hover:bg-red-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print PDF
                </a>
            </div>
        </div>
        <form method="GET" class="flex flex-wrap gap-2 mb-4 print:hidden">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Kode/nama material..." class="border border-gray-300 rounded-lg px-3 py-2 text-sm flex-1 min-w-[180px]">
            <select name="location" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Semua Lokasi</option>
                @foreach($locations as $loc)
                <option value="{{ $loc->id }}" {{ request('location')==$loc->id?'selected':'' }}>{{ $loc->code }} - {{ $loc->name }}</option>
                @endforeach
            </select>
            <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Semua Status</option>
                <option value="normal"  {{ request('status')==='normal'  ? 'selected' : '' }}>Normal</option>
                <option value="rendah"  {{ request('status')==='rendah'  ? 'selected' : '' }}>Rendah</option>
                <option value="habis"   {{ request('status')==='habis'   ? 'selected' : '' }}>Habis</option>
            </select>
            <label class="flex items-center gap-1.5 border border-gray-300 rounded-lg px-3 py-2 text-sm cursor-pointer {{ request('low_stock') ? 'bg-red-50 border-red-400 text-red-700 font-medium' : 'text-gray-600' }}">
                <input type="checkbox" name="low_stock" value="1" {{ request('low_stock') ? 'checked' : '' }} onchange="this.form.submit()" class="accent-red-600">
                Stok Minim
            </label>
            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded text-sm">Filter</button>
            <a href="{{ route('mm.stocks.index') }}" class="bg-gray-100 text-gray-600 px-4 py-2 rounded text-sm border hover:bg-gray-200">Reset</a>
            <a href="{{ route('mm.stocks.movements') }}" class="bg-gray-600 text-white px-4 py-2 rounded text-sm">Riwayat Mutasi</a>
        </form>
        {{-- Action Toolbar removed - merged into header above --}}
        <div class="no-mobile-cards overflow-x-auto">
        <table id="data-table" class="w-full text-sm border-collapse">
            <thead class="bg-blue-900 text-white">
                <tr>
                    <th class="px-3 py-2 text-left hidden sm:table-cell">Kode</th>
                    <th class="px-3 py-2 text-left">Nama Material</th>
                    <th class="px-3 py-2 text-left hidden md:table-cell">Tipe</th>
                    <th class="px-3 py-2 text-left hidden md:table-cell">Lokasi</th>
                    <th class="px-3 py-2 text-right">Qty Stok</th>
                    <th class="px-3 py-2 text-right hidden md:table-cell">Stok di Vendor</th>
                    <th class="px-3 py-2 text-left hidden md:table-cell">UoM</th>
                    <th class="px-3 py-2 text-right hidden md:table-cell">Min. Stok</th>
                    <th class="px-3 py-2 text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($stocks as $s)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-3 py-2 font-mono text-blue-700 text-xs hidden sm:table-cell">{{ $s->material->code }}</td>
                    <td class="px-3 py-2 max-w-[160px] truncate">{{ $s->material->name }}</td>
                    <td class="px-3 py-2 text-xs hidden md:table-cell"><span class="px-2 py-0.5 rounded bg-gray-100">{{ $s->material->type }}</span></td>
                    <td class="px-3 py-2 hidden md:table-cell">{{ $s->storageLocation->name }}</td>
                    @php
                        $minStock = (float)($s->material->min_stock ?? 0);
                        $qty      = (float)$s->quantity;
                        $stockStatus = $qty <= 0 ? 'habis' : ($minStock > 0 && $qty <= $minStock ? 'rendah' : 'normal');
                    @endphp
                    <td class="px-3 py-2 text-right font-medium {{ $stockStatus === 'habis' ? 'text-red-500' : ($stockStatus === 'rendah' ? 'text-yellow-600' : 'text-green-700') }}">
                        {{ fmt_qty($qty) }}
                    </td>
                    @php $vendorQty = (float)($vendorStockMap[$s->material_id] ?? 0); @endphp
                    <td class="px-3 py-2 text-right hidden md:table-cell {{ $vendorQty > 0 ? 'text-indigo-700 font-medium' : 'text-gray-300' }}">
                        {{ $vendorQty > 0 ? fmt_qty($vendorQty) : '—' }}
                    </td>
                    <td class="px-3 py-2 text-gray-500 hidden md:table-cell">{{ $s->material->unit_of_measure ?? '-' }}</td>
                    <td class="px-3 py-2 text-right text-gray-500 hidden md:table-cell">{{ $minStock > 0 ? fmt_qty($minStock) : '-' }}</td>
                    <td class="px-3 py-2 text-center">
                        @if($stockStatus === 'habis')
                        <span class="px-2 py-0.5 rounded text-xs bg-red-100 text-red-700">Habis</span>
                        @elseif($stockStatus === 'rendah')
                        <span class="px-2 py-0.5 rounded text-xs bg-yellow-100 text-yellow-700">Rendah</span>
                        @else
                        <span class="px-2 py-0.5 rounded text-xs bg-green-100 text-green-700">Normal</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="px-4 py-4 text-center text-gray-400">Tidak ada data stok.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="mt-4 print:hidden">{{ $stocks->links() }}</div>

        {{-- Stok WIP/FP yang ada di vendor (belum diterima IPPI) --}}
        @if($vendorOnlyStocks->isNotEmpty())
        <div class="mt-8">
            <h3 class="text-base font-semibold text-indigo-700 mb-2">Stok di Vendor (Belum Diterima IPPI)</h3>
            <p class="text-xs text-gray-500 mb-3">Material WIP/FP yang sudah diproduksi vendor namun belum ada di gudang IPPI.</p>            <div class="mobile-cards overflow-x-auto">            <table class="w-full text-sm border-collapse">
                <thead class="bg-indigo-700 text-white">
                    <tr>
                        <th class="px-4 py-2 text-left">Tipe</th>
                        <th class="px-4 py-2 text-left">Kode Material</th>
                        <th class="px-4 py-2 text-left">Nama Material</th>
                        <th class="px-4 py-2 text-left">Vendor</th>
                        <th class="px-4 py-2 text-right">Qty di Vendor</th>
                        <th class="px-4 py-2 text-left">UoM</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vendorOnlyStocks as $materialId => $entries)
                    @php $material = $entries->first()->material; @endphp
                    @foreach($entries as $vs)
                    <tr class="border-b hover:bg-indigo-50">
                        <td class="px-4 py-2" data-label="Tipe">
                            <span class="px-2 py-0.5 rounded text-xs font-semibold
                                {{ $material?->type==='WIP' ? 'bg-yellow-200 text-yellow-800' : 'bg-green-200 text-green-800' }}">
                                {{ $material?->type ?? '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 font-mono text-indigo-700" data-label="Kode Material">{{ $material?->code }}</td>
                        <td class="px-4 py-2" data-label="Nama Material">{{ $material?->name }}</td>
                        <td class="px-4 py-2 text-gray-600" data-label="Vendor">{{ $vs->vendor?->name ?? '—' }}</td>
                        <td class="px-4 py-2 text-right font-bold text-indigo-700" data-label="Qty di Vendor">{{ fmt_qty($vs->quantity) }}</td>
                        <td class="px-4 py-2 text-gray-500" data-label="UoM">{{ $material?->unit_of_measure }}</td>
                    </tr>
                    @endforeach
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
        @endif
    </div>
</x-app-layout>
