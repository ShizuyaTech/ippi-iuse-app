<x-app-layout>
    <x-slot name="title">Edit Goods Issue {{ $goodsIssue->gi_number }}</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-1">Edit Goods Issue</h2>
        <p class="text-sm text-gray-500 mb-4">{{ $goodsIssue->gi_number }}</p>

        @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <form method="POST" action="{{ route('mm.goods-issues.update', $goodsIssue) }}" class="space-y-6">
            @csrf @method('PUT')

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Issue *</label>
                    <input type="date" name="issue_date"
                        value="{{ old('issue_date', $goodsIssue->issue_date->format('Y-m-d')) }}"
                        class="w-full border rounded px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dari Storage Location *</label>
                    <select name="storage_location_id" class="w-full border rounded px-3 py-2 text-sm" required>
                        <option value="">-- Pilih Lokasi --</option>
                        @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ old('storage_location_id', $goodsIssue->storage_location_id) == $loc->id ? 'selected' : '' }}>
                            {{ $loc->code }} - {{ $loc->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                <textarea name="notes" rows="2" class="w-full border rounded px-3 py-2 text-sm">{{ old('notes', $goodsIssue->notes) }}</textarea>
            </div>

            <div>
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-semibold text-gray-700">Material yang Dikeluarkan</h3>
                    <button type="button" onclick="addRow()" class="bg-green-600 text-white px-3 py-1 rounded text-sm">+ Tambah</button>
                </div>
                <table class="w-full text-sm border-collapse">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-3 py-2 text-left">Material</th>
                            <th class="px-3 py-2 text-right w-36">Qty</th>
                            <th class="px-3 py-2 w-12"></th>
                        </tr>
                    </thead>
                    <tbody id="items-body">
                        @foreach($goodsIssue->items as $idx => $item)
                        <tr class="border-b">
                            <td class="px-2 py-1">
                                <select name="items[{{ $idx }}][material_id]" class="w-full border rounded px-2 py-1 text-sm" required>
                                    <option value="">-- Pilih Material --</option>
                                    @foreach($materials as $mat)
                                    <option value="{{ $mat->id }}" {{ $item->material_id == $mat->id ? 'selected' : '' }}>
                                        {{ $mat->code }} - {{ $mat->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-2 py-1">
                                <input type="number" name="items[{{ $idx }}][quantity]"
                                    value="{{ old('items.'.$idx.'.quantity', $item->quantity_issued) }}"
                                    class="w-full border rounded px-2 py-1 text-sm text-right"
                                    min="0.001" step="0.001" required>
                            </td>
                            <td class="px-2 py-1 text-center">
                                <button type="button" onclick="this.closest('tr').remove()" class="text-red-500">&#10005;</button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-blue-700 text-white px-6 py-2 rounded text-sm hover:bg-blue-800">Simpan Perubahan</button>
                <a href="{{ route('mm.goods-issues.show', $goodsIssue) }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded text-sm">Batal</a>
            </div>
        </form>
    </div>

    <script>
        @php
            $materialJson = $materials->map(fn($m) => ['id'=>$m->id,'code'=>$m->code,'name'=>$m->name]);
        @endphp
        const materials = @json($materialJson);
        let r = {{ $goodsIssue->items->count() }};
        function addRow(){
            const opts = materials.map(m=>`<option value="${m.id}">${m.code} - ${m.name}</option>`).join('');
            const tr = document.createElement('tr');
            tr.className = 'border-b';
            tr.innerHTML = `
                <td class="px-2 py-1"><select name="items[${r}][material_id]" class="w-full border rounded px-2 py-1 text-sm" required><option value="">-- Pilih Material --</option>${opts}</select></td>
                <td class="px-2 py-1"><input type="number" name="items[${r}][quantity]" class="w-full border rounded px-2 py-1 text-sm text-right" min="0.001" step="0.001" value="1" required></td>
                <td class="px-2 py-1 text-center"><button type="button" onclick="this.closest('tr').remove()" class="text-red-500">&#10005;</button></td>
            `;
            document.getElementById('items-body').appendChild(tr); r++;
        }
    </script>
</x-app-layout>
