<x-app-layout>
    <x-slot name="title">Vendor</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-wrap justify-between items-center gap-2 mb-4">
            <h2 class="text-lg font-semibold text-gray-700">Daftar Vendor</h2>
            <div class="flex flex-wrap gap-2 items-center print:hidden">
                <a href="{{ route('mm.vendors.export', request()->query()) }}" class="inline-flex items-center gap-1.5 bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export Excel
                </a>
                <a href="{{ route('mm.vendors.template') }}" class="inline-flex items-center gap-1.5 bg-blue-100 text-blue-700 px-4 py-2 rounded text-sm border border-blue-300 hover:bg-blue-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Template
                </a>
                <button onclick="document.getElementById('import-modal').classList.remove('hidden')" class="inline-flex items-center gap-1.5 bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12"/></svg>
                    Import Excel
                </button>
                <a href="{{ route('mm.vendors.export-pdf', request()->query()) }}" target="_blank" class="inline-flex items-center gap-1.5 bg-red-700 text-white px-4 py-2 rounded text-sm hover:bg-red-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print PDF
                </a>
                <a href="{{ route('mm.vendors.create') }}" class="bg-blue-700 text-white px-4 py-2 rounded text-sm hover:bg-blue-800">+ Tambah Vendor</a>
            </div>
        </div>
        <form method="GET" class="flex flex-wrap gap-2 mb-4 print:hidden">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode / nama..." class="border rounded px-3 py-2 text-sm flex-1 min-w-40">
            {{-- <input type="date" name="date_from" value="{{ request('date_from') }}" title="Dari tanggal dibuat" class="border rounded px-3 py-2 text-sm"> --}}
            {{-- <input type="date" name="date_to"   value="{{ request('date_to') }}"   title="Sampai tanggal dibuat" class="border rounded px-3 py-2 text-sm"> --}}
            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded text-sm">Cari</button>
            <a href="{{ route('mm.vendors.index') }}" class="bg-gray-100 text-gray-600 px-4 py-2 rounded text-sm border hover:bg-gray-200">Reset</a>
        </form>
        {{-- Action Toolbar removed - merged into header above --}}
        <table id="data-table" class="w-full text-sm border-collapse">
            <thead class="bg-blue-900 text-white">
                <tr>
                    <th class="px-4 py-2 text-left">Kode</th>
                    <th class="px-4 py-2 text-left">Nama</th>
                    <th class="px-4 py-2 text-left">Tipe</th>
                    <th class="px-4 py-2 text-left">Kontak</th>
                    <th class="px-4 py-2 text-left">Email</th>
                    <th class="px-4 py-2 text-left">Telepon</th>
                    <th class="px-4 py-2 text-center">Status</th>
                    <th class="px-4 py-2 text-center print:hidden">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vendors as $v)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-blue-700" data-label="Kode">{{ $v->code }}</td>
                    <td class="px-4 py-2 font-medium" data-label="Nama">{{ $v->name }}</td>
                    <td class="px-4 py-2" data-label="Tipe">
                        @php
                            $typeColors = ['coil_center'=>'bg-blue-100 text-blue-700','process'=>'bg-purple-100 text-purple-700','general'=>'bg-gray-100 text-gray-600'];
                            $typeColor  = $typeColors[$v->vendor_type ?? 'general'] ?? 'bg-gray-100 text-gray-600';
                        @endphp
                        <span class="px-2 py-0.5 rounded text-xs {{ $typeColor }}">{{ $v->getTypeLabel() }}</span>
                    </td>
                    <td class="px-4 py-2" data-label="Kontak">{{ $v->contact_person ?? '-' }}</td>
                    <td class="px-4 py-2" data-label="Email">{{ $v->email ?? '-' }}</td>
                    <td class="px-4 py-2" data-label="Telepon">{{ $v->phone ?? '-' }}</td>
                    <td class="px-4 py-2 text-center" data-label="Status"><span class="px-2 py-0.5 rounded text-xs {{ $v->is_active?'bg-green-100 text-green-700':'bg-red-100 text-red-700' }}">{{ $v->is_active?'Aktif':'Nonaktif' }}</span></td>
                    <td class="px-4 py-2 text-center flex justify-center gap-2 print:hidden">
                        <a href="{{ route('mm.vendors.show', $v) }}" class="text-blue-600 hover:underline">Detail</a>
                        <a href="{{ route('mm.vendors.edit', $v) }}" class="text-yellow-600 hover:underline">Edit</a>
                        <form method="POST" action="{{ route('mm.vendors.destroy', $v) }}" onsubmit="return confirm('Hapus vendor ini?')">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:underline">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-4 text-center text-gray-400">Belum ada data vendor.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4 print:hidden">{{ $vendors->links() }}</div>
    </div>

    {{-- Import Modal --}}
    <div id="import-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 w-96">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-base font-semibold text-gray-700">Import Excel - Vendor</h3>
                <button onclick="document.getElementById('import-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>
            <p class="text-sm text-gray-500 mb-3">Upload file Excel (.xlsx). Download <a href="{{ route('mm.vendors.template') }}" class="text-blue-600 hover:underline">template</a> terlebih dahulu.</p>
            <form method="POST" action="{{ route('mm.vendors.import') }}" enctype="multipart/form-data">
                @csrf
                <input type="file" name="file" accept=".xlsx,.xls" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border rounded px-3 py-2 mb-4">
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('import-modal').classList.add('hidden')" class="px-4 py-2 text-sm rounded border text-gray-600 hover:bg-gray-50">Batal</button>
                    <button type="submit" class="px-4 py-2 text-sm rounded bg-blue-600 text-white hover:bg-blue-700">Upload</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
