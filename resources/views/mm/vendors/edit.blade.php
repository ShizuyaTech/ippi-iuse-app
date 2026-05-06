<x-app-layout>
    <x-slot name="title">Edit Vendor</x-slot>
    <div class="bg-white rounded-lg shadow p-6 max-w-2xl">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Edit Vendor: {{ $vendor->code }}</h2>
        <form method="POST" action="{{ route('mm.vendors.update', $vendor) }}" class="space-y-4">
            @csrf @method('PATCH')
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kode Vendor *</label>
                    <input type="text" name="code" value="{{ old('code', $vendor->code) }}" class="w-full border rounded px-3 py-2 text-sm" required maxlength="20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Vendor *</label>
                    <input type="text" name="name" value="{{ old('name', $vendor->name) }}" class="w-full border rounded px-3 py-2 text-sm" required>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Vendor *</label>
                <select name="vendor_type" class="w-full border rounded px-3 py-2 text-sm" required>
                    @foreach(\App\Models\Vendor::TYPES as $value => $label)
                        <option value="{{ $value }}" {{ old('vendor_type', $vendor->vendor_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Coil Center = supplier bahan baku &bull; Process = vendor makloon/subkon &bull; Umum = lainnya</p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kontak</label>
                    <input type="text" name="contact_person" value="{{ old('contact_person', $vendor->contact_person) }}" class="w-full border rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $vendor->email) }}" class="w-full border rounded px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Telepon</label>
                <input type="text" name="phone" value="{{ old('phone', $vendor->phone) }}" class="w-full border rounded px-3 py-2 text-sm" maxlength="20">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                <textarea name="address" rows="3" class="w-full border rounded px-3 py-2 text-sm">{{ old('address', $vendor->address) }}</textarea>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-blue-700 text-white px-6 py-2 rounded hover:bg-blue-800 text-sm">Perbarui</button>
                <a href="{{ route('mm.vendors.index') }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded text-sm">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
