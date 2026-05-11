<x-app-layout>
    <x-slot name="title">Edit BOM</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Edit BOM: {{ $bom->bom_number }}</h2>
        <form method="POST" action="{{ route('pp.boms.update', $bom) }}" class="space-y-6">
            @csrf @method('PATCH')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Material Hasil *</label>
                    <select name="material_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                        @foreach($materials as $m)
                        <option value="{{ $m->id }}" {{ old('material_id', $bom->material_id)==$m->id?'selected':'' }}>{{ $m->code }} - {{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Qty Base *</label>
                    <input type="number" name="base_quantity" value="{{ old('base_quantity', $bom->base_quantity) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" min="0.001" step="0.001" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Berlaku Mulai *</label>
                    <input type="date" name="valid_from" value="{{ old('valid_from', $bom->valid_from?->format('Y-m-d')) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Berlaku Hingga</label>
                    <input type="date" name="valid_to" value="{{ old('valid_to', $bom->valid_to?->format('Y-m-d')) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="status" value="inactive">
                <input type="checkbox" name="status" id="status" value="active" {{ old('status', $bom->status)==='active' ? 'checked' : '' }} class="rounded">
                <label for="status" class="text-sm text-gray-700">Aktif</label>
            </div>
            <div>
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-semibold text-gray-700">Komponen BOM</h3>
                    <button type="button" onclick="addRow()" class="bg-green-600 text-white px-3 py-1 rounded text-sm">+ Tambah</button>
                </div>
                <table class="w-full text-sm border-collapse">
                    <thead class="bg-gray-100"><tr>
                        <th class="px-3 py-2 text-left">Material Komponen</th>
                        <th class="px-3 py-2 text-right w-32">Qty</th>
                        <th class="px-3 py-2 text-left w-24">UoM</th>
                        <th class="px-3 py-2 w-12"></th>
                    </tr></thead>
                    <tbody id="items-body"></tbody>
                </table>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="bg-blue-700 text-white px-6 py-2 rounded text-sm">Perbarui BOM</button>
                <a href="{{ route('pp.boms.show', $bom) }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded text-sm">Batal</a>
            </div>
        </form>
    </div>
    <script>
        @php
            $materialJson = $materials->map(fn($m) => ['id'=>$m->id,'code'=>$m->code,'name'=>$m->name,'uom'=>$m->unit_of_measure]);
            $existingJson = $bom->items->map(fn($i) => ['material_id'=>$i->material_id,'quantity'=>$i->quantity,'uom'=>$i->unit_of_measure]);
        @endphp
        const materials = @json($materialJson);
        const existing = @json($existingJson);
        let r = 0;
        function addRow(mid=null, qty=1, uom=''){
            const opts = materials.map(m=>`<option value="${m.id}" data-uom="${m.uom}" ${mid==m.id?'selected':''}>${m.code} - ${m.name}</option>`).join('');
            const tr = document.createElement('tr');
            tr.className='border-b';
            tr.innerHTML=`
                <td class="px-2 py-1"><select name="items[${r}][material_id]" class="w-full border rounded px-2 py-1 text-sm" required onchange="fillUom(this)"><option value="">-- Pilih --</option>${opts}</select></td>
                <td class="px-2 py-1"><input type="number" name="items[${r}][quantity]" value="${qty}" class="w-full border rounded px-2 py-1 text-sm text-right" min="0.001" step="0.001" required></td>
                <td class="px-2 py-1"><input type="text" name="items[${r}][unit]" value="${uom}" class="w-full border rounded px-2 py-1 text-sm uom-field"></td>
                <td class="px-2 py-1 text-center"><button type="button" onclick="this.closest('tr').remove()" class="text-red-500">&#10005;</button></td>
            `;
            document.getElementById('items-body').appendChild(tr); r++;
        }
        function fillUom(sel){const opt=sel.options[sel.selectedIndex];sel.closest('tr').querySelector('.uom-field').value=opt.dataset.uom||'';}
        existing.forEach(i=>addRow(i.material_id, i.quantity, i.uom));
        if(!existing.length) addRow();
    </script>
</x-app-layout>
