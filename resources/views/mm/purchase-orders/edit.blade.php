<x-app-layout>
    <x-slot name="title">Edit Purchase Order</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Edit PO: {{ $purchaseOrder->po_number }}</h2>
        <form method="POST" action="{{ route('mm.purchase-orders.update', $purchaseOrder) }}" id="po-form" class="space-y-6">
            @csrf @method('PATCH')

            {{-- Lokasi Gudang --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi Gudang *</label>
                    <select name="storage_location_id" id="location-select" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required onchange="onLocationChange(this)">
                        <option value="">-- Pilih Lokasi Gudang --</option>
                        @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" data-code="{{ $loc->code }}"
                            {{ old('storage_location_id', $purchaseOrder->storage_location_id)==$loc->id?'selected':'' }}>
                            {{ $loc->code }} - {{ $loc->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2 flex items-end">
                    <p id="location-hint" class="text-xs text-gray-500 italic">Pilih lokasi gudang untuk menampilkan material yang sesuai.</p>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vendor *</label>
                    <select name="vendor_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                        <option value="">-- Pilih Vendor --</option>
                        @foreach($vendors as $v)
                        <option value="{{ $v->id }}" {{ old('vendor_id',$purchaseOrder->vendor_id)==$v->id?'selected':'' }}>{{ $v->code }} - {{ $v->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Order *</label>
                    <input type="date" name="order_date" value="{{ old('order_date', $purchaseOrder->order_date->format('Y-m-d')) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Est. Pengiriman</label>
                    <input type="date" name="expected_delivery_date" value="{{ old('expected_delivery_date', $purchaseOrder->expected_delivery_date?->format('Y-m-d')) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('notes', $purchaseOrder->notes) }}</textarea>
            </div>
            <div>
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-semibold text-gray-700">Item PO</h3>
                    <button type="button" onclick="addItem()" id="add-item-btn" class="bg-green-600 text-white px-3 py-1 rounded text-sm">+ Tambah Item</button>
                </div>
                <table class="w-full text-sm border-collapse">
                    <thead class="bg-gray-100"><tr>
                        <th class="px-3 py-2 text-left">Material</th>
                        <th class="px-3 py-2 text-right w-28">Qty</th>
                        <th class="px-3 py-2 text-right w-36">Harga Satuan</th>
                        <th class="px-3 py-2 text-right w-36">Total</th>
                        <th class="px-3 py-2 w-12"></th>
                    </tr></thead>
                    <tbody id="items-body"></tbody>
                    <tfoot><tr class="bg-gray-50 font-semibold">
                        <td colspan="3" class="px-3 py-2 text-right">Total PO:</td>
                        <td class="px-3 py-2 text-right" id="grand-total">0</td>
                        <td></td>
                    </tr></tfoot>
                </table>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="bg-blue-700 text-white px-6 py-2 rounded text-sm">Perbarui PO</button>
                <a href="{{ route('mm.purchase-orders.show', $purchaseOrder) }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded text-sm">Batal</a>
            </div>
        </form>
    </div>

    <script>
        @php
            $materialJson = $materials->map(fn($m) => ['id'=>$m->id,'code'=>$m->code,'name'=>$m->name,'price'=>$m->standard_price,'type'=>$m->type]);
            $locationMapJson = $locations->mapWithKeys(fn($l) => [$l->code => match($l->code) {
                'WH-01' => 'RM',
                'WH-02' => 'WIP',
                'WH-03' => 'FP',
                default  => null,
            }]);
            $existingJson = $purchaseOrder->items->map(fn($i) => ['material_id'=>$i->material_id,'quantity'=>$i->quantity,'unit_price'=>$i->unit_price]);
        @endphp
        const allMaterials = @json($materialJson);
        const locationCodeTypeMap = @json($locationMapJson);
        const existingItems = @json($existingJson);
        let rowIndex = 0;
        let filteredMaterials = [];

        function onLocationChange(sel, keepRows = false) {
            const code = sel.options[sel.selectedIndex]?.dataset?.code;
            const materialType = locationCodeTypeMap[code] || null;
            filteredMaterials = materialType ? allMaterials.filter(m => m.type === materialType) : [];
            const hint = document.getElementById('location-hint');
            if (materialType && filteredMaterials.length) {
                hint.textContent = `Menampilkan material tipe ${materialType} (${filteredMaterials.length} item).`;
            } else {
                hint.textContent = 'Pilih lokasi gudang untuk menampilkan material yang sesuai.';
            }
            if (!keepRows) {
                document.getElementById('items-body').innerHTML = '';
                rowIndex = 0;
                calcGrand();
            }
        }

        function addItem(mid=null,qty=1,price=0){
            if (!filteredMaterials.length) return;
            const tbody = document.getElementById('items-body');
            const opts = filteredMaterials.map(m=>`<option value="${m.id}" data-price="${m.price}" ${mid==m.id?'selected':''}>${m.code} - ${m.name}</option>`).join('');
            const tr = document.createElement('tr');
            tr.className='border-b';
            tr.innerHTML=`
                <td class="px-2 py-1"><select name="items[${rowIndex}][material_id]" class="w-full border rounded px-2 py-1 text-sm material-sel" required onchange="updatePrice(this)"><option value="">-- Pilih --</option>${opts}</select></td>
                <td class="px-2 py-1"><input type="number" name="items[${rowIndex}][quantity]" class="w-full border rounded px-2 py-1 text-sm qty" min="0.001" step="0.001" value="${qty}" onchange="calcRow(this)" required></td>
                <td class="px-2 py-1"><input type="number" name="items[${rowIndex}][unit_price]" class="w-full border rounded px-2 py-1 text-sm price" min="0" step="0.01" value="${price}" onchange="calcRow(this)" required></td>
                <td class="px-2 py-1 text-right font-medium row-total">${(qty*price).toLocaleString('id-ID')}</td>
                <td class="px-2 py-1 text-center"><button type="button" onclick="this.closest('tr').remove();calcGrand()" class="text-red-500">&#10005;</button></td>
            `;
            tbody.appendChild(tr); rowIndex++; calcGrand();
        }
        function updatePrice(sel){const opt=sel.options[sel.selectedIndex];const row=sel.closest('tr');row.querySelector('.price').value=opt.dataset.price||0;calcRow(sel);}
        function calcRow(el){const r=el.closest('tr');const t=(parseFloat(r.querySelector('.qty').value)||0)*(parseFloat(r.querySelector('.price').value)||0);r.querySelector('.row-total').textContent=t.toLocaleString('id-ID',{minimumFractionDigits:0});calcGrand();}
        function calcGrand(){let g=0;document.querySelectorAll('.row-total').forEach(e=>g+=parseFloat(e.textContent.replace(/\./g,''))||0);document.getElementById('grand-total').textContent=g.toLocaleString('id-ID',{minimumFractionDigits:0});}

        // Init: set location, load existing items
        document.addEventListener('DOMContentLoaded', () => {
            const sel = document.getElementById('location-select');
            if (sel.value) {
                onLocationChange(sel, true);
                existingItems.forEach(i => addItem(i.material_id, i.quantity, i.unit_price));
            }
        });
    </script>
</x-app-layout>
