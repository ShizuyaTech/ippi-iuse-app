<x-app-layout>
    <x-slot name="title">Tambah Work Center</x-slot>
    <div class="bg-white rounded-lg shadow p-6 max-w-xl">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Tambah Work Center Baru</h2>
        <form method="POST" action="{{ route('pp.work-centers.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Kode *</label>
                <input type="text" name="code" value="{{ old('code') }}" class="w-full border rounded px-3 py-2 text-sm" required placeholder="WC001">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama *</label>
                <input type="text" name="name" value="{{ old('name') }}" class="w-full border rounded px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                <textarea name="description" rows="2" class="w-full border rounded px-3 py-2 text-sm">{{ old('description') }}</textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kapasitas per Jam</label>
                    <input type="number" name="capacity_per_hour" value="{{ old('capacity_per_hour') }}" class="w-full border rounded px-3 py-2 text-sm" min="0" step="0.01">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Biaya per Jam (Rp)</label>
                    <input type="number" name="cost_per_hour" value="{{ old('cost_per_hour') }}" class="w-full border rounded px-3 py-2 text-sm" min="0" step="0.01">
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }} class="rounded">
                <label for="is_active" class="text-sm text-gray-700">Aktif</label>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-blue-700 text-white px-6 py-2 rounded text-sm">Simpan</button>
                <a href="{{ route('pp.work-centers.index') }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded text-sm">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
