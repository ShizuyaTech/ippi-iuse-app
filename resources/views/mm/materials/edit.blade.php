<x-app-layout>
    <x-slot name="title">Edit Material</x-slot>
    <div class="bg-white rounded-lg shadow p-6 max-w-2xl">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Edit Material: {{ $material->code }}</h2>
        <form method="POST" action="{{ route('mm.materials.update', $material) }}" class="space-y-4">
            @csrf @method('PATCH')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kode Material *</label>
                    <input type="text" name="code" value="{{ old('code', $material->code) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required maxlength="20">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipe *</label>
                    <select name="type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                        <option value="RM" {{ old('type',$material->type)=='RM'?'selected':'' }}>RM - Bahan Baku</option>
                        <option value="WIP" {{ old('type',$material->type)=='WIP'?'selected':'' }}>WIP - Semi Jadi</option>
                        <option value="FP" {{ old('type',$material->type)=='FP'?'selected':'' }}>FP - Produk Jadi</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Material *</label>
                <input type="text" name="name" value="{{ old('name', $material->name) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('description', $material->description) }}</textarea>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Unit of Measure *</label>
                    <input type="text" name="unit_of_measure" value="{{ old('unit_of_measure', $material->unit_of_measure) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Harga Standard *</label>
                    <input type="number" name="standard_price" value="{{ old('standard_price', $material->standard_price) }}" step="0.01" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Qty per Case / Karton</label>
                    <input type="number" name="qty_per_case" value="{{ old('qty_per_case', $material->qty_per_case) }}" step="0.001" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <p class="text-xs text-gray-400 mt-1">Isi 0 jika tidak digunakan</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Minimal Stok</label>
                    <input type="number" name="min_stock" value="{{ old('min_stock', $material->min_stock) }}" step="0.001" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <p class="text-xs text-gray-400 mt-1">Alert jika stok total di bawah nilai ini</p>
                </div>
            </div>
            {{-- Order Method --}}
            <div class="border rounded p-4 bg-gray-50 space-y-3">
                <div class="text-sm font-semibold text-gray-700">Metode Order</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sistem Order *</label>
                        <select name="order_method" id="order_method" onchange="toggleVendor(this.value)"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="mrp" {{ old('order_method',$material->order_method)=='mrp'?'selected':'' }}>MRP (Perencanaan Bulanan)</option>
                            <option value="skm" {{ old('order_method',$material->order_method)=='skm'?'selected':'' }}>SKM (Summary Kanban Material)</option>
                        </select>
                    </div>
                    <div id="vendor_field">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vendor Planning (MRP/SKM)</label>
                        <select name="vendor_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">-- Pilih Vendor --</option>
                            @foreach($vendors as $v)
                            <option value="{{ $v->id }}" {{ old('vendor_id',$material->vendor_id)==$v->id?'selected':'' }}>{{ $v->code }} — {{ $v->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Wajib diisi jika metode SKM</p>
                    </div>
                </div>
                <div>
                    <label class="flex items-center gap-2 text-sm font-medium text-gray-700 mb-2 cursor-pointer">
                        <input type="checkbox" id="is_vendor_process" onchange="toggleProcessVendor(this.checked)"
                               {{ (old('process_vendor_id', $material->process_vendor_id)) ? 'checked' : '' }}
                               class="w-4 h-4 rounded border-gray-300 text-blue-600">
                        Diproses di Vendor (WIP / FP)
                    </label>
                    <div id="process_vendor_field" class="{{ (old('process_vendor_id', $material->process_vendor_id)) ? '' : 'hidden' }}">
                        <select name="process_vendor_id" id="process_vendor_select" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                                {{ (old('process_vendor_id', $material->process_vendor_id)) ? 'required' : '' }}>
                            <option value="">-- Pilih Vendor Proses --</option>
                            @foreach($vendors as $v)
                            <option value="{{ $v->id }}" {{ old('process_vendor_id',$material->process_vendor_id)==$v->id?'selected':'' }}>{{ $v->code }} — {{ $v->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Pilih vendor yang memproses material ini. Akan tampil di portal vendor.</p>
                    </div>
                    @error('process_vendor_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-blue-700 text-white px-6 py-2 rounded hover:bg-blue-800 text-sm">Perbarui</button>
                <a href="{{ route('mm.materials.index') }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded hover:bg-gray-300 text-sm">Batal</a>
            </div>
        </form>
    </div>
    <script>
    function toggleVendor(val) {
        // vendor planning selalu bisa diedit
    }
    function toggleProcessVendor(checked) {
        const field = document.getElementById('process_vendor_field');
        const select = document.getElementById('process_vendor_select');
        if (checked) {
            field.classList.remove('hidden');
            select.required = true;
        } else {
            field.classList.add('hidden');
            select.required = false;
            select.value = '';
        }
    }
    </script>
</x-app-layout>
