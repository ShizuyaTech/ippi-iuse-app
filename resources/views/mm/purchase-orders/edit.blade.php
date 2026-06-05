<x-app-layout>
    <x-slot name="title">Edit Purchase Order</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Edit PO: {{ $purchaseOrder->po_number }}</h2>

        @if($purchaseOrder->skm_order_id && $purchaseOrder->status === 'approved')
        <div class="mb-4 flex items-start gap-2 bg-amber-50 border border-amber-300 text-amber-800 px-4 py-3 rounded text-sm">
            <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/></svg>
            <span>PO ini digenerate dari <strong>SKM</strong> dan berstatus <strong>Approved</strong>. Perubahan yang disimpan tidak mengubah status PO.</span>
        </div>
        @endif
        <form method="POST" action="{{ route('mm.purchase-orders.update', $purchaseOrder) }}" id="po-form" class="space-y-6">
            @csrf @method('PATCH')

            {{-- Nomor EDN --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomor EDN *</label>
                    <input type="text" name="po_number" value="{{ old('po_number', $purchaseOrder->po_number) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono @error('po_number') border-red-400 @enderror" required>
                    @error('po_number')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

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
            $locationMapJson = $locations->mapWithKeys(fn($l) => [$l->code => $l->material_type]);
            $existingJson = $purchaseOrder->items->map(fn($i) => ['material_id'=>$i->material_id,'material_code'=>$i->material->code,'material_name'=>$i->material->name,'quantity'=>$i->quantity,'unit_price'=>$i->unit_price]);
        @endphp
        const allMaterials = @json($materialJson);
        const locationCodeTypeMap = @json($locationMapJson);
        const existingItems = @json($existingJson);
        let rowIndex = 0;
        let filteredMaterials = [];

        function onLocationChange(sel, keepRows = false) {
            const code = sel.options[sel.selectedIndex]?.dataset?.code;
            const materialType = code ? (locationCodeTypeMap[code] ?? null) : null;

            if (!code) {
                filteredMaterials = [];
            } else if (materialType) {
                filteredMaterials = allMaterials.filter(m => m.type === materialType);
            } else {
                filteredMaterials = allMaterials;
            }

            const hint = document.getElementById('location-hint');
            if (!code) {
                hint.textContent = 'Pilih lokasi gudang untuk menampilkan material yang sesuai.';
            } else if (filteredMaterials.length) {
                hint.textContent = materialType
                    ? `Menampilkan material tipe ${materialType} (${filteredMaterials.length} item).`
                    : `Menampilkan semua material (${filteredMaterials.length} item).`;
            } else {
                hint.textContent = 'Tidak ada material untuk lokasi ini.';
            }

            if (!keepRows) {
                document.getElementById('items-body').innerHTML = '';
                rowIndex = 0;
                calcGrand();
            }
        }

        function addItem(mid=null, qty=1, price=0, mcode='', mname='') {
            const materials = filteredMaterials.length ? filteredMaterials : allMaterials;
            const tbody = document.getElementById('items-body');
            const r = rowIndex;
            const labelVal = (mid && mcode) ? `${mcode} - ${mname}` : '';
            const tr = document.createElement('tr');
            tr.className = 'border-b';
            tr.innerHTML = `
                <td class="px-2 py-1" style="position:relative;overflow:visible;min-width:200px;">
                    <input type="text" id="mat-text-${r}" autocomplete="off" placeholder="Ketik kode/nama..." value="${labelVal}"
                        style="width:100%;border:1px solid #cbd5e1;border-radius:4px;padding:3px 6px;font-size:12px;"
                        oninput="matSearch(${r}, this)"
                        onkeydown="matKeydown(${r}, this, event)"
                        onblur="matHide(${r})">
                    <input type="hidden" name="items[${r}][material_id]" id="mat-id-${r}" value="${mid ?? ''}" required>
                    <ul id="mat-list-${r}" style="display:none;position:absolute;top:100%;left:0;min-width:240px;background:#fff;border:1px solid #cbd5e1;border-radius:6px;max-height:160px;overflow-y:auto;z-index:1000;margin:0;padding:4px 0;box-shadow:0 4px 12px rgba(0,0,0,.12);"></ul>
                </td>
                <td class="px-2 py-1"><input type="number" name="items[${r}][quantity]" class="w-full border rounded px-2 py-1 text-sm qty" min="0.001" step="0.001" value="${qty}" onchange="calcRow(this)" required></td>
                <td class="px-2 py-1"><input type="number" name="items[${r}][unit_price]" class="w-full border rounded px-2 py-1 text-sm price" min="0" step="0.01" value="${price}" onchange="calcRow(this)" required></td>
                <td class="px-2 py-1 text-right font-medium row-total">${(qty*price).toLocaleString('id-ID')}</td>
                <td class="px-2 py-1 text-center"><button type="button" onclick="this.closest('tr').remove();calcGrand()" class="text-red-500 hover:text-red-700">&#10005;</button></td>
            `;
            tbody.appendChild(tr);
            rowIndex++;
            calcGrand();
        }

        // ── Material typeahead per row ─────────────────────────────────
        function matSearch(r, inp) {
            inp._activeIdx = -1;
            const q = inp.value.trim().toLowerCase();
            document.getElementById(`mat-id-${r}`).value = '';
            const list = document.getElementById(`mat-list-${r}`);
            const materials = filteredMaterials.length ? filteredMaterials : allMaterials;
            if (!q || !materials.length) { list.style.display = 'none'; return; }
            const hits = materials.filter(m =>
                m.code.toLowerCase().includes(q) || m.name.toLowerCase().includes(q)
            ).slice(0, 20);
            if (!hits.length) { list.style.display = 'none'; return; }
            list.innerHTML = hits.map(m =>
                `<li data-id="${m.id}" data-label="${m.code} - ${m.name}" data-price="${m.price}"
                    style="padding:5px 10px;cursor:pointer;font-size:12px;white-space:nowrap;list-style:none;"
                    onmousedown="matPick(${r}, this)">
                    <b>${m.code}</b> &mdash; ${m.name}
                </li>`
            ).join('');
            list.style.display = 'block';
        }
        function matPick(r, li) {
            const inp = document.getElementById(`mat-text-${r}`);
            inp.value = li.dataset.label;
            document.getElementById(`mat-id-${r}`).value = li.dataset.id;
            document.getElementById(`mat-list-${r}`).style.display = 'none';
            const row = inp.closest('tr');
            const priceInp = row.querySelector('.price');
            if (priceInp) { priceInp.value = li.dataset.price || 0; calcRow(priceInp); }
        }
        function matKeydown(r, inp, e) {
            const list = document.getElementById(`mat-list-${r}`);
            if (list.style.display === 'none') return;
            const items = list.querySelectorAll('li');
            if (!items.length) return;
            if (inp._activeIdx === undefined) inp._activeIdx = -1;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                inp._activeIdx = Math.min(inp._activeIdx + 1, items.length - 1);
                items.forEach((li, i) => li.style.background = i === inp._activeIdx ? '#EFF6FF' : '');
                items[inp._activeIdx]?.scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                inp._activeIdx = Math.max(inp._activeIdx - 1, 0);
                items.forEach((li, i) => li.style.background = i === inp._activeIdx ? '#EFF6FF' : '');
                items[inp._activeIdx]?.scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (inp._activeIdx >= 0 && inp._activeIdx < items.length) matPick(r, items[inp._activeIdx]);
            } else if (e.key === 'Escape') {
                list.style.display = 'none';
            }
        }
        function matHide(r) {
            setTimeout(() => { const l = document.getElementById(`mat-list-${r}`); if (l) l.style.display = 'none'; }, 150);
        }
        // ─────────────────────────────────────────────────────────────────

        function updatePrice(sel){const opt=sel.options[sel.selectedIndex];const row=sel.closest('tr');row.querySelector('.price').value=opt.dataset.price||0;calcRow(sel);}
        function calcRow(el){const r=el.closest('tr');const t=(parseFloat(r.querySelector('.qty').value)||0)*(parseFloat(r.querySelector('.price').value)||0);r.querySelector('.row-total').textContent=t.toLocaleString('id-ID',{minimumFractionDigits:0});calcGrand();}
        function calcGrand(){let g=0;document.querySelectorAll('.row-total').forEach(e=>g+=parseFloat(e.textContent.replace(/\./g,'').replace(',','.'))||0);document.getElementById('grand-total').textContent=g.toLocaleString('id-ID',{minimumFractionDigits:0});}

        // Init: set location, load existing items
        document.addEventListener('DOMContentLoaded', () => {
            const sel = document.getElementById('location-select');
            if (sel.value) {
                onLocationChange(sel, true);
            }
            existingItems.forEach(i => addItem(i.material_id, i.quantity, i.unit_price, i.material_code, i.material_name));
        });
    </script>
</x-app-layout>
