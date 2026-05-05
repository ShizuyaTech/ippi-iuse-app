<x-app-layout>
    <x-slot name="title">Tambah Storage Location</x-slot>
    <div class="bg-white rounded-lg shadow p-6 max-w-xl">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Tambah Storage Location</h2>
        <form method="POST" action="{{ route('mm.storage-locations.store') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kode *</label>
                    <input type="text" name="code" value="{{ old('code') }}" class="w-full border rounded px-3 py-2 text-sm" required maxlength="10">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama *</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="w-full border rounded px-3 py-2 text-sm" required>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                <textarea name="description" rows="3" class="w-full border rounded px-3 py-2 text-sm">{{ old('description') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Material</label>
                <select name="material_type" class="w-full border rounded px-3 py-2 text-sm">
                    <option value="">Semua Tipe (RM / WIP / FP)</option>
                    <option value="RM"  {{ old('material_type')==='RM'  ? 'selected':'' }}>RM — Bahan Baku</option>
                    <option value="WIP" {{ old('material_type')==='WIP' ? 'selected':'' }}>WIP — Semi Jadi</option>
                    <option value="FP"  {{ old('material_type')==='FP'  ? 'selected':'' }}>FP — Produk Jadi</option>
                </select>
                <p class="text-xs text-gray-400 mt-1">Material baru hanya akan otomatis muncul di lokasi yang tipenya cocok (atau lokasi tanpa tipe).</p>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_scrap" id="is_scrap" value="1" {{ old('is_scrap') ? 'checked' : '' }} class="w-4 h-4 rounded border-gray-300 text-red-600">
                <label for="is_scrap" class="text-sm font-medium text-gray-700">Lokasi Scrap (stok di sini <span class="text-red-600 font-semibold">tidak</span> dihitung dalam MRP)</label>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="bg-blue-700 text-white px-6 py-2 rounded text-sm">Simpan</button>
                <a href="{{ route('mm.storage-locations.index') }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded text-sm">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
