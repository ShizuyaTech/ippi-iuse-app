<x-app-layout>
    <x-slot name="title">Buat GR Non-PO</x-slot>
    <div class="bg-white rounded-lg shadow p-6 max-w-4xl">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('mm.goods-receipts.index') }}" data-back-key="back_mm_goods_receipts" class="text-blue-600 hover:underline text-sm">← Kembali</a>
            <h2 class="text-lg font-semibold text-gray-700">Buat GR Non-PO</h2>
            <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded">Tanpa Purchase Order</span>
        </div>

        @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded text-sm flex items-center gap-2">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('success') }}
        </div>
        @endif

        @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-2 rounded text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('mm.goods-receipts.store-non-po') }}" class="space-y-5"
              onkeydown="if(event.key==='Enter'&&event.target.tagName!=='TEXTAREA'&&event.target.type!=='submit'){event.preventDefault();}">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vendor *</label>
                    <select name="vendor_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                        <option value="">-- Pilih Vendor --</option>
                        @foreach($vendors as $v)
                        <option value="{{ $v->id }}" {{ old('vendor_id')==$v->id?'selected':'' }}>{{ $v->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Penerimaan *</label>
                    <input type="date" name="receipt_date" value="{{ old('receipt_date', user_now()->format('Y-m-d')) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Storage Location *</label>
                    <select name="storage_location_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                        <option value="">-- Pilih Lokasi --</option>
                        @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ old('storage_location_id')==$loc->id?'selected':'' }}>
                            {{ $loc->code }} - {{ $loc->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan GR</label>
                <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('notes') }}</textarea>
            </div>

            <div>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-semibold text-gray-700">Item yang Diterima</h3>
                    <button type="button" onclick="addRow()" class="bg-blue-600 text-white px-3 py-1.5 rounded text-sm hover:bg-blue-700">+ Tambah Item</button>
                </div>
                <div class="border border-gray-200 rounded-lg">
                <table class="w-full text-sm border-collapse">
                    <thead class="bg-gray-100 text-gray-600 text-xs uppercase">
                        <tr>
                            <th class="px-3 py-2 text-left">Material</th>
                            <th class="px-3 py-2 text-right w-28">Qty</th>
                            <th class="px-3 py-2 text-left">ID Packing / Keterangan</th>
                            <th class="px-3 py-2 text-center w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="items-body"></tbody>
                </table>
                </div>
                @error('items')<p class="text-red-600 text-sm mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-green-700 text-white px-6 py-2 rounded text-sm hover:bg-green-800">Post GR Non-PO</button>
                <a href="{{ route('mm.goods-receipts.index') }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded text-sm">Batal</a>
            </div>
        </form>
    </div>

    <script>
    @php
        $matJson = $materials->map(fn($m) => ['id'=>$m->id,'code'=>$m->code,'name'=>$m->name,'uom'=>$m->unit_of_measure,'type'=>$m->type]);
    @endphp
    const allMaterials = @json($matJson);
    let rowIdx = 0;

    function addRow() {
        const tbody = document.getElementById('items-body');
        const r = rowIdx++;
        const opts = allMaterials.map(m =>
            `<option value="${m.id}" data-uom="${m.uom}">${m.code} — ${m.name} (${m.uom})</option>`
        ).join('');
        const tr = document.createElement('tr');
        tr.className = 'border-b';
        tr.innerHTML = `
            <td class="px-2 py-2" style="min-width:220px;">
                <div class="relative">
                <input type="text" id="mat-txt-${r}" autocomplete="off"
                       placeholder="Ketik kode atau nama..."
                       class="w-full border rounded px-2 py-1.5 text-sm"
                       oninput="matSearch(${r},this)"
                       onkeydown="matKeydown(${r},this,event)"
                       onblur="setTimeout(()=>matHide(${r}),150)">
                <input type="hidden" name="items[${r}][material_id]" id="mat-id-${r}" required>
                <ul id="mat-list-${r}" style="display:none;position:absolute;top:100%;left:0;min-width:240px;background:#fff;border:1px solid #cbd5e1;border-radius:6px;max-height:160px;overflow-y:auto;z-index:1000;list-style:none;margin:0;padding:4px 0;box-shadow:0 4px 12px rgba(0,0,0,.12);"></ul>
                </div>
            </td>
            <td class="px-2 py-2">
                <div class="flex items-center gap-1">
                    <input type="number" name="items[${r}][quantity]" min="0.001" step="0.001"
                           class="w-24 border rounded px-2 py-1.5 text-sm text-right" required>
                    <span id="uom-${r}" class="text-xs text-gray-400 whitespace-nowrap"></span>
                </div>
            </td>
            <td class="px-2 py-2">
                <input type="text" name="items[${r}][packing_note]" maxlength="100"
                       placeholder="ID Packing / opsional" class="w-full border rounded px-2 py-1.5 text-sm">
            </td>
            <td class="px-2 py-2 text-center">
                <button type="button" onclick="this.closest('tr').remove()" class="text-red-500 hover:text-red-700 text-lg leading-none">&times;</button>
            </td>
        `;
        tbody.appendChild(tr);
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
            items.forEach((el, i) => el.style.background = i === inp._activeIdx ? '#EFF6FF' : '');
            items[inp._activeIdx]?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            inp._activeIdx = Math.max(inp._activeIdx - 1, 0);
            items.forEach((el, i) => el.style.background = i === inp._activeIdx ? '#EFF6FF' : '');
            items[inp._activeIdx]?.scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (inp._activeIdx >= 0 && inp._activeIdx < items.length) {
                matPick(r, items[inp._activeIdx]);
            }
        } else if (e.key === 'Escape') {
            list.style.display = 'none';
        }
    }

    function matSearch(r, inp) {
        inp._activeIdx = -1;
        const q = inp.value.trim().toLowerCase();
        const list = document.getElementById(`mat-list-${r}`);
        document.getElementById(`mat-id-${r}`).value = '';
        if (!q) { list.style.display = 'none'; return; }
        const hits = allMaterials.filter(m =>
            m.code.toLowerCase().includes(q) || m.name.toLowerCase().includes(q)
        ).slice(0, 20);
        if (!hits.length) { list.style.display = 'none'; return; }
        list.innerHTML = hits.map(m =>
            `<li data-id="${m.id}" data-uom="${m.uom}"
                 style="padding:5px 10px;cursor:pointer;font-size:12px;"
                 onmousedown="matPick(${r},this)">
                <b>${m.code}</b> &mdash; ${m.name} <span style="color:#9ca3af;">(${m.uom})</span>
            </li>`
        ).join('');
        list.style.display = 'block';
    }

    function matPick(r, li) {
        document.getElementById(`mat-txt-${r}`).value = li.dataset.id
            ? allMaterials.find(m => m.id == li.dataset.id)?.code + ' — ' + allMaterials.find(m => m.id == li.dataset.id)?.name
            : '';
        document.getElementById(`mat-id-${r}`).value = li.dataset.id;
        document.getElementById(`uom-${r}`).textContent = li.dataset.uom;
        document.getElementById(`mat-list-${r}`).style.display = 'none';
    }

    function matHide(r) {
        const el = document.getElementById(`mat-list-${r}`);
        if (el) el.style.display = 'none';
    }

    // Start with one row
    addRow();
    </script>
</x-app-layout>
