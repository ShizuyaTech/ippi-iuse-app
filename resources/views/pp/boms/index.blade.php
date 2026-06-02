<x-app-layout>
    <x-slot name="title">Bill of Materials</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-wrap justify-between items-center gap-2 mb-4">
            <h2 class="text-lg font-semibold text-gray-700">Daftar Bill of Materials (BOM)</h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('pp.boms.export') }}" class="inline-flex items-center gap-1.5 bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export Excel
                </a>
                <a href="{{ route('pp.boms.export-pdf', request()->query()) }}" target="_blank" class="inline-flex items-center gap-1.5 bg-red-700 text-white px-4 py-2 rounded text-sm hover:bg-red-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Print PDF
                </a>
                <a href="{{ route('pp.boms.import-template') }}" class="inline-flex items-center gap-1.5 bg-blue-100 text-blue-700 px-4 py-2 rounded text-sm border border-blue-300 hover:bg-blue-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download Template
                </a>
                <button onclick="document.getElementById('import-panel').classList.toggle('hidden')" class="inline-flex items-center gap-1.5 bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4 4l4-4m0 0l4-4m-4 4V4"/></svg>
                    Import Excel
                </button>
                <a href="{{ route('pp.boms.create') }}" class="bg-blue-700 text-white px-4 py-2 rounded text-sm hover:bg-blue-800">+ Buat Manual</a>
            </div>
        </div>

        {{-- Import Panel --}}
        <div id="import-panel" class="hidden mb-4 p-4 bg-blue-50 border border-blue-200 rounded">
            <p class="text-sm text-blue-800 font-medium mb-3">Import BOM dari Excel</p>
            <p class="text-xs text-gray-500 mb-3">Setiap BOM terdiri dari satu baris header (isi kolom A–E) diikuti baris komponen (kolom F–I). Baris komponen tambahan cukup isi kolom F–I, kolom A biarkan kosong.</p>
            <form method="POST" action="{{ route('pp.boms.import') }}" enctype="multipart/form-data" class="flex flex-wrap gap-2 items-end">
                @csrf
                <div>
                    <label class="block text-xs text-gray-600 mb-1">Pilih File Excel (.xlsx/.xls)</label>
                    <input type="file" name="file" accept=".xlsx,.xls" required class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm bg-white">
                </div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">Upload &amp; Import</button>
            </form>
        </div>

        {{-- Import Errors --}}
        @if(session('import_errors') && count(session('import_errors')) > 0)
        <div class="mb-4 p-3 bg-orange-50 border border-orange-200 rounded text-sm">
            <p class="font-medium text-orange-800 mb-1">Detail masalah saat import:</p>
            <ul class="list-disc list-inside text-orange-700 space-y-0.5">
                @foreach(session('import_errors') as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
        @endif
        <form method="GET" class="flex flex-wrap gap-2 mb-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="No. BOM / nama material..." class="border border-gray-300 rounded-lg px-3 py-2 text-sm flex-1 min-w-40">
            {{-- <input type="date" name="date_from" value="{{ request('date_from') }}" title="Dari tanggal dibuat" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <input type="date" name="date_to"   value="{{ request('date_to') }}"   title="Sampai tanggal dibuat" class="border border-gray-300 rounded-lg px-3 py-2 text-sm"> --}}
            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded text-sm">Cari</button>
            <a href="{{ route('pp.boms.index') }}" class="bg-gray-100 text-gray-600 px-4 py-2 rounded text-sm border hover:bg-gray-200">Reset</a>
        </form>
        <div class="mobile-cards overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead class="bg-blue-900 text-white">
                <tr>
                    <th class="px-4 py-2 text-left">No. BOM</th>
                    <th class="px-4 py-2 text-left">Material (Hasil)</th>
                    <th class="px-4 py-2 text-right">Qty Base</th>
                    <th class="px-4 py-2 text-right">Jml Komponen</th>
                    <th class="px-4 py-2 text-center">Status</th>
                    <th class="px-4 py-2 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($boms as $bom)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-blue-700 font-medium" data-label="No. BOM">{{ $bom->bom_number }}</td>
                    <td class="px-4 py-2" data-label="Material (Hasil)">
                        <div class="font-mono text-xs text-gray-500">{{ $bom->material->code }}</div>
                        <div>{{ $bom->material->name }}</div>
                    </td>
                    <td class="px-4 py-2 text-right" data-label="Qty Base">{{ fmt_qty($bom->base_quantity) }}</td>
                    <td class="px-4 py-2 text-right" data-label="Jml Komponen">{{ $bom->items->count() }}</td>
                    <td class="px-4 py-2 text-center" data-label="Status">
                        <span class="px-2 py-0.5 rounded text-xs {{ $bom->status==='active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $bom->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-center flex gap-2 justify-center">
                        <a href="{{ route('pp.boms.show', $bom) }}" class="text-blue-600 hover:underline">Detail</a>
                        <a href="{{ route('pp.boms.edit', $bom) }}" class="text-yellow-600 hover:underline">Edit</a>
                        <form method="POST" action="{{ route('pp.boms.destroy', $bom) }}" onsubmit="return confirm('Hapus BOM ini?')">
                            @csrf @method('DELETE')
                            <button class="text-red-500 hover:underline">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-4 text-center text-gray-400">Belum ada BOM.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="mt-4">{{ $boms->links() }}</div>
    </div>
</x-app-layout>
