<x-app-layout>
    <x-slot name="title">Buat Purchase Order</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Buat Purchase Order Baru</h2>
        <form method="POST" action="{{ route('mm.purchase-orders.store') }}" id="po-form" class="space-y-6">
            @csrf

            {{-- Lokasi Gudang --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi Gudang *</label>
                    <select name="storage_location_id" id="location-select" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required onchange="onLocationChange(this)">
                        <option value="">-- Pilih Lokasi Gudang --</option>
                        @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" data-code="{{ $loc->code }}"
                            {{ old('storage_location_id')==$loc->id?'selected':'' }}>
                            {{ $loc->code }} - {{ $loc->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2 flex items-end">
                    <p id="location-hint" class="text-xs text-gray-500 italic">Pilih lokasi gudang terlebih dahulu untuk menampilkan material yang sesuai.</p>
                </div>
            </div>

            {{-- Vendor + Tanggal --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vendor *</label>
                    <div class="relative">
                        <input type="text" id="vendor-search"
                               value="{{ old('vendor_id') ? ($vendors->firstWhere('id', old('vendor_id'))?->code.' - '.$vendors->firstWhere('id', old('vendor_id'))?->name) : '' }}"
                               placeholder="Ketik kode atau nama vendor..."
                               autocomplete="off"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                               oninput="vendorSearch(this)"
                               onkeydown="vendorKeydown(event)">
                        <input type="hidden" name="vendor_id" id="vendor-id-hidden" value="{{ old('vendor_id') }}">
                        <div id="vendor-suggestions"
                             class="absolute z-50 w-full bg-white border border-gray-200 rounded-b shadow-lg max-h-52 overflow-y-auto hidden"></div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Order *</label>
                    <input type="date" name="order_date" value="{{ old('order_date', user_now()->format('Y-m-d')) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Est. Pengiriman</label>
                    <input type="date" name="expected_delivery_date" value="{{ old('expected_delivery_date') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('notes') }}</textarea>
            </div>

            {{-- Import Excel Panel --}}
            <div class="border border-blue-200 rounded-lg bg-blue-50 p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        <span class="text-sm font-semibold text-blue-700">Import Item dari Excel</span>
                    </div>
                    <a href="{{ route('mm.purchase-orders.import-template') }}"
                       class="inline-flex items-center gap-1.5 text-xs bg-white border border-blue-300 text-blue-700 px-3 py-1.5 rounded hover:bg-blue-50">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Download Template
                    </a>
                </div>
                <p class="text-xs text-blue-600 mb-3">
                    Download template, isi Kode Material + Qty + Harga Satuan, lalu upload. Item akan otomatis masuk ke tabel di bawah.
                    Lokasi gudang harus dipilih terlebih dahulu agar filter material sesuai.
                </p>
                <div class="flex gap-3 items-start">
                    <div class="flex-1">
                        <input type="file" id="import-file" accept=".xlsx,.xls"
                               class="block w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:bg-white file:text-blue-700 file:border file:border-blue-300 hover:file:bg-blue-50 cursor-pointer">
                    </div>
                    <button type="button" id="import-btn"
                            onclick="doImport()"
                            class="bg-blue-700 text-white px-4 py-1.5 rounded text-sm hover:bg-blue-800 whitespace-nowrap">
                        Upload &amp; Import
                    </button>
                </div>
                <div id="import-result" class="mt-3 hidden"></div>
            </div>

            <div>
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-semibold text-gray-700">Item PO</h3>
                    <button type="button" onclick="addItem()" id="add-item-btn" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700 disabled:opacity-50" disabled>+ Tambah Item</button>
                </div>
                <table class="w-full text-sm border-collapse" id="items-table">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-3 py-2 text-left">Material</th>
                            <th class="px-3 py-2 text-right w-28">Qty</th>
                            <th class="px-3 py-2 text-right w-36">Harga Satuan</th>
                            <th class="px-3 py-2 text-right w-36">Total</th>
                            <th class="px-3 py-2 w-12"></th>
                        </tr>
                    </thead>
                    <tbody id="items-body">
                        {{-- rows inserted by JS --}}
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50 font-semibold">
                            <td colspan="3" class="px-3 py-2 text-right">Total PO:</td>
                            <td class="px-3 py-2 text-right" id="grand-total">0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-blue-700 text-white px-6 py-2 rounded hover:bg-blue-800 text-sm">Simpan PO</button>
                <a href="{{ route('mm.purchase-orders.index') }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded text-sm">Batal</a>
            </div>
        </form>
    </div>

    <script>
        @php
            $materialJson = $materials->map(fn($m) => ['id'=>$m->id,'code'=>$m->code,'name'=>$m->name,'price'=>$m->standard_price,'type'=>$m->type]);
            // Map: location code → material_type (RM/WIP/FP/null for general)
            $locationMapJson = $locations->mapWithKeys(fn($l) => [$l->code => $l->material_type]);
            $vendorJson = $vendors->map(fn($v) => ['id'=>$v->id,'code'=>$v->code,'name'=>$v->name,'vendor_type'=>$v->vendor_type]);
        @endphp
        const allMaterials = @json($materialJson);
        const locationCodeTypeMap = @json($locationMapJson);
        let rowIndex = 0;
        let filteredMaterials = [];
        let importedGroups = null;

        // ── Vendor autocomplete ───────────────────────────────────────────
        const allVendors = @json($vendorJson);

        function vendorSearch(input) {
            input._activeIdx = -1;
            const q = input.value.trim().toLowerCase();
            const box = document.getElementById('vendor-suggestions');
            document.getElementById('vendor-id-hidden').value = '';
            if (!q) { box.classList.add('hidden'); return; }
            const matches = allVendors.filter(v =>
                v.code.toLowerCase().includes(q) || v.name.toLowerCase().includes(q)
            ).slice(0, 20);
            if (!matches.length) { box.classList.add('hidden'); return; }
            box.innerHTML = matches.map(v =>
                `<div class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50 border-b border-gray-100"
                      data-id="${v.id}" data-label="${v.code} - ${v.name}">
                    <span class="font-mono text-blue-600 text-xs font-semibold">${v.code}</span>
                    <span class="ml-2 text-gray-700">${v.name}</span>
                </div>`
            ).join('');
            box.classList.remove('hidden');
        }

        document.addEventListener('click', function(e) {
            const input = document.getElementById('vendor-search');
            const box   = document.getElementById('vendor-suggestions');
            if (input && !input.contains(e.target) && box && !box.contains(e.target)) {
                box.classList.add('hidden');
            }
        });

        document.getElementById('vendor-suggestions').addEventListener('click', function(e) {
            const item = e.target.closest('[data-id]');
            if (!item) return;
            document.getElementById('vendor-search').value = item.dataset.label;
            document.getElementById('vendor-id-hidden').value = item.dataset.id;
            this.classList.add('hidden');
            onVendorSelect(item.dataset.id);
        });

        function onVendorSelect(vendorId) {
            const vendor = allVendors.find(v => String(v.id) === String(vendorId));
            const isProcess = vendor && vendor.vendor_type === 'process';
            window._selectedVendorIsProcess = isProcess;
            // Re-run location filter with updated vendor context
            const locSel = document.getElementById('location-select');
            if (locSel.value) onLocationChange(locSel);
        }

        function vendorKeydown(e) {
            const box = document.getElementById('vendor-suggestions');
            if (box.classList.contains('hidden')) return;
            const items = box.querySelectorAll('[data-id]');
            if (!items.length) return;
            const inp = document.getElementById('vendor-search');
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
                    const sel = items[inp._activeIdx];
                    inp.value = sel.dataset.label;
                    document.getElementById('vendor-id-hidden').value = sel.dataset.id;
                    box.classList.add('hidden');
                    onVendorSelect(sel.dataset.id);
                }
            } else if (e.key === 'Escape') {
                box.classList.add('hidden');
            }
        }

        // Validate vendor selected before submit
        document.getElementById('po-form').addEventListener('submit', function(e) {
            if (!document.getElementById('vendor-id-hidden').value) {
                e.preventDefault();
                document.getElementById('vendor-search').focus();
                document.getElementById('vendor-search').classList.add('border-red-500');
                alert('Pilih vendor dari daftar saran terlebih dahulu.');
            }
        });
        // ─────────────────────────────────────────────────────────────────

        function onLocationChange(sel) {
            const code = sel.options[sel.selectedIndex]?.dataset?.code;
            const materialType = code ? (locationCodeTypeMap[code] ?? null) : null;
            const isProcess = window._selectedVendorIsProcess === true;

            if (!code) {
                filteredMaterials = [];
            } else if (materialType && !isProcess) {
                // Lokasi punya tipe spesifik dan vendor bukan proses → filter ketat
                filteredMaterials = allMaterials.filter(m => m.type === materialType);
            } else {
                // Gudang umum (null) ATAU vendor proses → tampilkan semua material
                filteredMaterials = allMaterials;
            }

            const hint = document.getElementById('location-hint');
            const btn  = document.getElementById('add-item-btn');

            if (!code) {
                hint.textContent = 'Pilih lokasi gudang terlebih dahulu untuk menampilkan material yang sesuai.';
                btn.disabled = true;
            } else if (filteredMaterials.length) {
                const label = isProcess
                    ? `Vendor proses — menampilkan semua material (${filteredMaterials.length} item).`
                    : (materialType
                        ? `Menampilkan material tipe ${materialType} (${filteredMaterials.length} item).`
                        : `Menampilkan semua material (${filteredMaterials.length} item).`);
                hint.textContent = label;
                btn.disabled = false;
            } else {
                hint.textContent = 'Tidak ada material untuk lokasi ini.';
                btn.disabled = true;
            }

            // Clear existing rows when location changes
            document.getElementById('items-body').innerHTML = '';
            rowIndex = 0;
            calcGrand();
        }

        // ── Material typeahead per row ─────────────────────────────────
        function matSearch(r, inp) {
            inp._activeIdx = -1;
            const q = inp.value.trim().toLowerCase();
            document.getElementById(`mat-id-${r}`).value = '';
            const list = document.getElementById(`mat-list-${r}`);
            if (!q || !filteredMaterials.length) { list.style.display = 'none'; return; }
            const hits = filteredMaterials.filter(m =>
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

        function addItem() {
            if (!filteredMaterials.length) return;
            const tbody = document.getElementById('items-body');
            const tr = document.createElement('tr');
            tr.className = 'border-b';
            tr.innerHTML = `
                <td class="px-2 py-1" style="position:relative;overflow:visible;min-width:200px;">
                    <input type="text" id="mat-text-${rowIndex}" autocomplete="off" placeholder="Ketik kode/nama..."
                        style="width:100%;border:1px solid #cbd5e1;border-radius:4px;padding:3px 6px;font-size:12px;"
                        oninput="matSearch(${rowIndex}, this)"
                        onkeydown="matKeydown(${rowIndex}, this, event)"
                        onblur="matHide(${rowIndex})">
                    <input type="hidden" name="items[${rowIndex}][material_id]" id="mat-id-${rowIndex}" required>
                    <ul id="mat-list-${rowIndex}" style="display:none;position:absolute;top:100%;left:0;min-width:240px;background:#fff;border:1px solid #cbd5e1;border-radius:6px;max-height:160px;overflow-y:auto;z-index:1000;margin:0;padding:4px 0;box-shadow:0 4px 12px rgba(0,0,0,.12);"></ul>
                </td>
                <td class="px-2 py-1"><input type="number" name="items[${rowIndex}][quantity]" class="w-full border rounded px-2 py-1 text-sm qty" min="0.001" step="0.001" onchange="calcRow(this)" required></td>
                <td class="px-2 py-1"><input type="number" name="items[${rowIndex}][unit_price]" class="w-full border rounded px-2 py-1 text-sm price" min="0" step="0.01" value="0" onchange="calcRow(this)" required></td>
                <td class="px-2 py-1 text-right font-medium row-total">0</td>
                <td class="px-2 py-1 text-center"><button type="button" onclick="this.closest('tr').remove();calcGrand()" class="text-red-500 hover:text-red-700">&#10005;</button></td>
            `;
            tbody.appendChild(tr);
            rowIndex++;
        }

        function calcRow(el) {
            const row = el.closest('tr');
            const qty = parseFloat(row.querySelector('.qty').value) || 0;
            const price = parseFloat(row.querySelector('.price').value) || 0;
            const total = qty * price;
            row.querySelector('.row-total').textContent = total.toLocaleString('id-ID', {minimumFractionDigits:0});
            calcGrand();
        }

        function calcGrand() {
            let grand = 0;
            document.querySelectorAll('.row-total').forEach(el => {
                grand += parseFloat(el.textContent.replace(/\./g,'').replace(',','.')) || 0;
            });
            document.getElementById('grand-total').textContent = grand.toLocaleString('id-ID', {minimumFractionDigits:0});
        }

        // Restore state on validation error
        @if(old('storage_location_id'))
        document.addEventListener('DOMContentLoaded', () => {
            const sel = document.getElementById('location-select');
            sel.value = "{{ old('storage_location_id') }}";
            onLocationChange(sel);
        });
        @endif

        async function doImport() {
            const fileInput = document.getElementById('import-file');

            if (!fileInput.files.length) {
                showImportMsg('error', ['Pilih file Excel terlebih dahulu.']);
                return;
            }
            const locationSel = document.getElementById('location-select');
            if (!locationSel.value) {
                showImportMsg('error', ['Pilih Lokasi Gudang terlebih dahulu sebelum import, agar filter material sesuai.']);
                return;
            }

            const btn = document.getElementById('import-btn');
            btn.disabled = true;
            btn.textContent = 'Memproses...';

            const fd = new FormData();
            fd.append('file', fileInput.files[0]);
            fd.append('_token', '{{ csrf_token() }}');

            try {
                const res  = await fetch('{{ route('mm.purchase-orders.import-excel') }}', { method: 'POST', body: fd });
                const data = await res.json();

                if (data.items && data.items.length > 0) {
                    // Auto-fill order_date dari kolom D baris pertama
                    if (data.order_date) {
                        document.querySelector('[name="order_date"]').value = data.order_date;
                    }

                    // Kelompokkan item berdasarkan expected_delivery_date
                    const groupMap = {};
                    data.items.forEach(item => {
                        const key = item.expected_delivery_date || '__nodate__';
                        if (!groupMap[key]) groupMap[key] = { delivery_date: item.expected_delivery_date || null, items: [] };
                        groupMap[key].items.push(item);
                    });
                    importedGroups = Object.values(groupMap);
                    showImportPreview(importedGroups, data.errors || []);
                } else if (data.errors && data.errors.length > 0) {
                    showImportMsg('error', data.errors);
                } else {
                    showImportMsg('error', ['Tidak ada item yang dapat diimport. Periksa format file.']);
                }
            } catch (e) {
                showImportMsg('error', ['Gagal memproses file. Pastikan format Excel sesuai template.']);
            } finally {
                btn.disabled = false;
                btn.textContent = 'Upload & Import';
            }
        }

        function fmtDate(d) {
            if (!d) return '<span class="italic text-gray-400">Tanpa tanggal</span>';
            const dt = new Date(d + 'T00:00:00');
            return dt.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
        }

        function showImportPreview(groups, errors) {
            const box = document.getElementById('import-result');
            const totalItems = groups.reduce((s, g) => s + g.items.length, 0);

            let html = `<div class="font-semibold text-blue-800 mb-3">
                Preview: <strong>${totalItems} item</strong> akan dibuat menjadi <strong>${groups.length} PO</strong> berdasarkan tanggal estimasi pengiriman.
            </div>`;

            groups.forEach((g, i) => {
                html += `<div class="mb-2 p-2 bg-white rounded border border-blue-200">
                    <div class="font-medium text-sm text-blue-700 mb-1">
                        PO ${i + 1} — Est. Kirim: ${fmtDate(g.delivery_date)}
                        <span class="text-gray-500 font-normal">&nbsp;(${g.items.length} item)</span>
                    </div>
                    <div class="text-xs text-gray-600">
                        ${g.items.map(it => `<span class="inline-block mr-2">${it.material_code} &times; ${it.quantity}</span>`).join('')}
                    </div>
                </div>`;
            });

            if (errors.length > 0) {
                html += `<div class="mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-800">
                    <div class="font-medium mb-1">Baris yang dilewati:</div>
                    ${errors.map(e => `<div>• ${e}</div>`).join('')}
                </div>`;
            }

            html += `<div class="mt-3 flex gap-2">
                <button type="button" id="create-po-btn" onclick="createImportedPOs()"
                    class="bg-blue-700 text-white px-4 py-2 rounded text-sm font-medium hover:bg-blue-800">
                    Buat ${groups.length} PO Sekarang
                </button>
                <button type="button" onclick="cancelImport()"
                    class="bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-300">
                    Batal
                </button>
            </div>`;

            box.className = 'mt-3 p-3 rounded border text-sm bg-blue-50 border-blue-300 text-blue-900';
            box.innerHTML = html;
            box.classList.remove('hidden');
        }

        async function createImportedPOs() {
            if (!importedGroups) return;

            const vendorId   = document.querySelector('[name="vendor_id"]').value;
            const locationId = document.getElementById('location-select').value;
            const orderDate  = document.querySelector('[name="order_date"]').value;
            const notes      = document.querySelector('[name="notes"]').value;

            if (!vendorId)   { alert('Pilih Vendor terlebih dahulu.'); return; }
            if (!locationId) { alert('Pilih Lokasi Gudang terlebih dahulu.'); return; }
            if (!orderDate)  { alert('Isi Tanggal Order terlebih dahulu.'); return; }

            const btn = document.getElementById('create-po-btn');
            btn.disabled = true;
            btn.textContent = 'Membuat PO...';

            try {
                const res = await fetch('{{ route('mm.purchase-orders.import-create') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        vendor_id:           vendorId,
                        storage_location_id: locationId,
                        order_date:          orderDate,
                        notes:               notes,
                        groups: importedGroups.map(g => ({
                            delivery_date: g.delivery_date,
                            items: g.items.map(it => ({
                                material_id: it.material_id,
                                quantity:    it.quantity,
                                unit_price:  it.unit_price,
                            })),
                        })),
                    }),
                });

                const data = await res.json();

                if (data.success) {
                    const box = document.getElementById('import-result');
                    box.className = 'mt-3 p-3 rounded border text-sm bg-green-50 border-green-300 text-green-800';
                    box.innerHTML = `<div class="font-semibold mb-2">✓ Berhasil membuat ${data.po_numbers.length} Purchase Order:</div>
                        ${data.po_numbers.map(p => `<div class="ml-2">• ${p.po_number}</div>`).join('')}
                        <div class="mt-2 text-xs text-green-700">Mengalihkan ke daftar PO...</div>`;
                    setTimeout(() => { window.location.href = data.redirect; }, 2000);
                } else {
                    showImportMsg('error', [data.message || 'Gagal membuat PO.']);
                    btn.disabled = false;
                    btn.textContent = `Buat ${importedGroups.length} PO Sekarang`;
                }
            } catch (e) {
                showImportMsg('error', ['Terjadi kesalahan. Silakan coba lagi.']);
                btn.disabled = false;
                btn.textContent = `Buat ${importedGroups.length} PO Sekarang`;
            }
        }

        function cancelImport() {
            importedGroups = null;
            document.getElementById('import-result').classList.add('hidden');
            document.getElementById('import-file').value = '';
        }

        function showImportMsg(type, lines) {
            const box = document.getElementById('import-result');
            const colors = {
                success: 'bg-green-50 border-green-300 text-green-800',
                warn:    'bg-yellow-50 border-yellow-300 text-yellow-800',
                error:   'bg-red-50 border-red-300 text-red-700',
            };
            box.className = `mt-3 p-3 rounded border text-sm ${colors[type] || colors.error}`;
            box.innerHTML = lines.map(l => `<div>• ${l}</div>`).join('');
            box.classList.remove('hidden');
        }
    </script>
</x-app-layout>
