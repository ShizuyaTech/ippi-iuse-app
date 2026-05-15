<x-app-layout>
    <x-slot name="title">Master Material</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-wrap justify-between items-center gap-2 mb-4">
            <h2 class="text-lg font-semibold text-gray-700">Daftar Material</h2>
            <div class="flex flex-wrap gap-2 items-center print:hidden">
                <a href="{{ route('mm.materials.export', request()->query()) }}" class="inline-flex items-center gap-1.5 bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export Excel
                </a>
                <a href="{{ route('mm.materials.template') }}" class="inline-flex items-center gap-1.5 bg-blue-100 text-blue-700 px-4 py-2 rounded text-sm border border-blue-300 hover:bg-blue-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Template
                </a>
                <button onclick="document.getElementById('import-modal').classList.remove('hidden')" class="inline-flex items-center gap-1.5 bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12"/></svg>
                    Import Excel
                </button>
                <a href="{{ route('mm.materials.export-pdf', request()->query()) }}" target="_blank" class="inline-flex items-center gap-1.5 bg-red-700 text-white px-4 py-2 rounded text-sm hover:bg-red-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print PDF
                </a>
                <a href="{{ route('mm.materials.create') }}" class="bg-blue-700 text-white px-4 py-2 rounded text-sm hover:bg-blue-800">+ Tambah Material</a>
            </div>
        </div>
        <form method="GET" class="flex gap-2 mb-4 flex-wrap print:hidden">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode / nama..." class="border border-gray-300 rounded-lg px-3 py-2 text-sm flex-1 min-w-40">
            {{-- <input type="date" name="date_from" value="{{ request('date_from') }}" title="Dari tanggal dibuat" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <input type="date" name="date_to"   value="{{ request('date_to') }}"   title="Sampai tanggal dibuat" class="border border-gray-300 rounded-lg px-3 py-2 text-sm"> --}}
            <select name="type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Semua Tipe</option>
                <option value="RM" {{ request('type')=='RM'?'selected':'' }}>RM - Bahan Baku</option>
                <option value="WIP" {{ request('type')=='WIP'?'selected':'' }}>WIP - Semi Jadi</option>
                <option value="FP" {{ request('type')=='FP'?'selected':'' }}>FP - Produk Jadi</option>
            </select>
            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded text-sm">Cari</button>
            <a href="{{ route('mm.materials.index') }}" class="bg-gray-100 text-gray-600 px-4 py-2 rounded text-sm border hover:bg-gray-200">Reset</a>
        </form>
        {{-- Action Toolbar removed - merged into header above --}}
        <div class="no-mobile-cards overflow-x-auto">
        <table id="data-table" class="w-full text-sm border-collapse">
            <thead class="bg-blue-900 text-white">
                <tr>
                    <th class="px-3 py-2 text-left hidden sm:table-cell">Kode</th>
                    <th class="px-3 py-2 text-left">Nama</th>
                    <th class="px-3 py-2 text-left">Tipe</th>
                    <th class="px-3 py-2 text-left hidden md:table-cell">UoM</th>
                    <th class="px-3 py-2 text-right hidden md:table-cell">Qty/Case</th>
                    <th class="px-3 py-2 text-right hidden sm:table-cell">Stok</th>
                    <th class="px-3 py-2 text-center hidden md:table-cell">Status</th>
                    <th class="px-3 py-2 text-center print:hidden">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($materials as $m)
                @php
                    $totalStock = (float) $m->stocks_sum_quantity;
                @endphp
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-3 py-2 font-mono text-blue-700 text-xs whitespace-nowrap hidden sm:table-cell">{{ $m->code }}</td>
                    <td class="px-3 py-2 max-w-[180px] truncate">{{ $m->name }}</td>
                    <td class="px-3 py-2"><span class="px-2 py-0.5 rounded text-xs {{ $m->type==='RM'?'bg-gray-100 text-gray-700':($m->type==='WIP'?'bg-yellow-100 text-yellow-700':'bg-green-100 text-green-700') }}">{{ $m->type }}</span></td>
                    <td class="px-3 py-2 hidden md:table-cell">{{ $m->unit_of_measure }}</td>
                    <td class="px-3 py-2 text-right hidden md:table-cell">{{ $m->qty_per_case > 0 ? number_format($m->qty_per_case, 0) : '-' }}</td>
                    <td class="px-3 py-2 text-right font-medium hidden sm:table-cell {{ $totalStock > 0 ? 'text-green-700' : 'text-gray-400' }}">
                        {{ fmt_qty($totalStock) }}
                    </td>
                    <td class="px-3 py-2 text-center hidden md:table-cell">
                        <span class="px-2 py-0.5 rounded text-xs {{ $m->is_active?'bg-green-100 text-green-700':'bg-red-100 text-red-700' }}">{{ $m->is_active?'Aktif':'Nonaktif' }}</span>
                    </td>
                    <td class="px-3 py-2 text-center print:hidden">
                        <div class="flex justify-center gap-2">
                            <a href="{{ route('mm.materials.show', $m) }}" class="text-blue-600 hover:underline">Detail</a>
                            <a href="{{ route('mm.materials.edit', $m) }}" class="text-yellow-600 hover:underline hidden sm:inline">Edit</a>
                            <form method="POST" action="{{ route('mm.materials.destroy', $m) }}" onsubmit="return confirm('Hapus material ini?')">
                                @csrf @method('DELETE')
                                <button class="text-red-600 hover:underline hidden sm:inline">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-4 text-center text-gray-400">Belum ada data material.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="mt-4 print:hidden">{{ $materials->links() }}</div>
    </div>

    {{-- Import Modal --}}
    <div id="import-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 w-96">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-base font-semibold text-gray-700">Import Excel - Material</h3>
                <button onclick="document.getElementById('import-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>
            <p class="text-sm text-gray-500 mb-3">Upload file Excel (.xlsx). Download <a href="{{ route('mm.materials.template') }}" class="text-blue-600 hover:underline">template</a> terlebih dahulu.</p>
            <form method="POST" action="{{ route('mm.materials.import') }}" enctype="multipart/form-data">
                @csrf
                <input type="file" name="file" accept=".xlsx,.xls" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-gray-300 rounded-lg px-3 py-2 mb-4">
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('import-modal').classList.add('hidden')" class="px-4 py-2 text-sm rounded border text-gray-600 hover:bg-gray-50">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm rounded bg-blue-600 text-white hover:bg-blue-700">Upload</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
