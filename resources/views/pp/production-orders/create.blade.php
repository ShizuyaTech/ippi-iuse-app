<x-app-layout>
    <x-slot name="title">Buat Production Order</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Buat Production Order</h2>
        <form method="POST" action="{{ route('pp.production-orders.store') }}" id="po-form" class="space-y-5">
            @csrf

            {{-- Tanggal di atas --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-gray-50 rounded-lg">
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tgl Mulai Rencana *</label>
                    <input type="date" name="planned_start_date" id="planned_start_date" value="{{ old('planned_start_date', user_now()->format('Y-m-d')) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                </div>
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tgl Selesai Rencana *</label>
                    <input type="date" name="planned_end_date" id="planned_end_date" value="{{ old('planned_end_date', user_now()->format('Y-m-d')) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Umum</label>
                    <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Opsional" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>

            {{-- Tabel item --}}
            <div>
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-semibold text-gray-700">Daftar Production Order</h3>
                    <div class="flex gap-2 items-center">
                        <button type="button" onclick="document.getElementById('import-panel').classList.toggle('hidden')"
                                class="inline-flex items-center gap-1.5 bg-teal-600 text-white px-3 py-1 rounded text-sm hover:bg-teal-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                            Import Excel
                        </button>
                        <button type="button" onclick="addRow()" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">+ Tambah Baris</button>
                    </div>
                </div>

                {{-- Import Panel --}}
                <div id="import-panel" class="hidden mb-4 border border-teal-200 rounded-lg bg-teal-50 p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span class="text-sm font-semibold text-teal-700">Import Item dari Excel</span>
                        </div>
                        <a href="{{ route('pp.production-orders.import-template') }}"
                           class="inline-flex items-center gap-1.5 text-xs bg-white border border-teal-300 text-teal-700 px-3 py-1.5 rounded hover:bg-teal-50">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Download Template
                        </a>
                    </div>
                    <p class="text-xs text-teal-600">Download template, isi No. Order + Kode Material + Qty + Catatan, lalu upload. Item akan masuk ke tabel secara otomatis.</p>

                    <div class="flex gap-3 items-start">
                        <div class="flex-1">
                            <input type="file" id="import-file" accept=".xlsx,.xls"
                                   class="block w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:bg-white file:text-teal-700 file:border file:border-teal-300 hover:file:bg-teal-50 cursor-pointer">
                        </div>
                        <button type="button" onclick="readImportFile()"
                                class="bg-teal-700 text-white px-4 py-1.5 rounded text-sm hover:bg-teal-800 whitespace-nowrap">
                            Proses File
                        </button>
                    </div>

                    {{-- Errors --}}
                    <div id="import-errors" class="hidden bg-red-50 border border-red-200 text-red-700 rounded p-3 text-xs space-y-0.5"></div>

                    {{-- Preview table --}}
                    <div id="import-preview" class="hidden">
                        <p class="text-xs font-semibold text-teal-700 mb-1">Preview — <span id="preview-count"></span> item ditemukan:</p>
                        <div class="overflow-x-auto rounded border border-teal-200">
                            <table class="w-full text-xs border-collapse">
                                <thead class="bg-teal-700 text-white">
                                    <tr>
                                        <th class="px-3 py-1.5 text-left">No. Order</th>
                                        <th class="px-3 py-1.5 text-left">Kode Material</th>
                                        <th class="px-3 py-1.5 text-left">Nama Material</th>
                                        <th class="px-3 py-1.5 text-right">Qty Planned</th>
                                        <th class="px-3 py-1.5 text-left">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody id="preview-body"></tbody>
                            </table>
                        </div>
                        <div class="flex gap-2 mt-2">
                            <button type="button" onclick="applyImport()"
                                    class="bg-teal-700 text-white px-4 py-1.5 rounded text-sm hover:bg-teal-800">
                                ✓ Masukkan ke Form
                            </button>
                            <button type="button" onclick="clearImport()" class="text-sm text-gray-400 hover:text-gray-600 underline">Batal</button>
                        </div>
                    </div>
                </div>
                <div style="overflow:visible;">
                <table class="w-full text-sm border-collapse" id="items-table">
                    <thead class="bg-blue-900 text-white">
                        <tr>
                            <th class="px-3 py-2 text-left" style="min-width:150px">No. Order *</th>
                            <th class="px-3 py-2 text-left" style="min-width:200px">Material *</th>
                            <th class="px-3 py-2 text-right" style="min-width:100px">Qty Planned *</th>
                            <th class="px-3 py-2 text-left" style="min-width:150px">Catatan Item</th>
                            <th class="px-3 py-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="items-body">
                    </tbody>
                </table>
                </div>
                <p class="text-xs text-gray-400 mt-2">* Komponen BOM otomatis dibuat saat Production Order disimpan.</p>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-blue-700 text-white px-6 py-2 rounded text-sm hover:bg-blue-800">Simpan Semua</button>
                <a href="{{ route('pp.production-orders.index') }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded text-sm">Batal</a>
            </div>
        </form>
    </div>

    <script>
        @php
            $materialsJson = $materials->map(fn($m) => ['id'=>$m->id,'code'=>$m->code,'name'=>$m->name]);
            $bomsJson      = $boms->map(fn($b) => ['id'=>$b->id,'number'=>$b->bom_number,'material_id'=>$b->material_id]);
            $routingsJson  = $routings->map(fn($r) => ['id'=>$r->id,'number'=>$r->routing_number,'material_id'=>$r->material_id]);
        @endphp
        const MATERIALS = @json($materialsJson);
        const BOMS      = @json($bomsJson);
        const ROUTINGS  = @json($routingsJson);
        let rowIdx = 0;

        function buildOptions(arr, valueKey, labelKey, filterMaterialId) {
            return arr
                .filter(x => !filterMaterialId || x.material_id == filterMaterialId)
                .map(x => `<option value="${x[valueKey]}">${x[labelKey]}</option>`)
                .join('');
        }

        function matSearch(r, inp) {
            inp._activeIdx = -1;
            const q = inp.value.trim().toLowerCase();
            document.getElementById(`mat-id-${r}`).value = '';
            const list = document.getElementById(`mat-list-${r}`);
            if (!q) { list.style.display = 'none'; return; }
            const hits = MATERIALS.filter(m => m.code.toLowerCase().includes(q) || m.name.toLowerCase().includes(q)).slice(0, 20);
            if (!hits.length) { list.style.display = 'none'; return; }
            list.innerHTML = hits.map(m =>
                `<li data-id="${m.id}" data-label="${m.code} - ${m.name}" data-r="${r}"
                    style="padding:5px 8px;cursor:pointer;font-size:12px;white-space:nowrap;"
                    onmousedown="pickMat(event, ${r})">
                    <b>${m.code}</b> &mdash; ${m.name}
                </li>`
            ).join('');
            list.style.display = 'block';
        }
        function pickMat(e, r) {
            const li = e.currentTarget;
            document.getElementById(`mat-text-${r}`).value = li.dataset.label;
            document.getElementById(`mat-id-${r}`).value   = li.dataset.id;
            document.getElementById(`mat-list-${r}`).style.display = 'none';
            onMaterialChange(r, li.dataset.id);
        }
        function hideMat(r) {
            setTimeout(() => {
                const l = document.getElementById(`mat-list-${r}`);
                if (l) l.style.display = 'none';
            }, 150);
        }

        function matKeydown(r, inp, e) {
            const list = document.getElementById(`mat-list-${r}`);
            if (!list || list.style.display === 'none') return;
            const items = list.querySelectorAll('li');
            if (!items.length) return;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                inp._activeIdx = Math.min((inp._activeIdx ?? -1) + 1, items.length - 1);
                items.forEach((li, i) => li.style.background = i === inp._activeIdx ? '#EFF6FF' : '');
                items[inp._activeIdx]?.scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                inp._activeIdx = Math.max((inp._activeIdx ?? 0) - 1, 0);
                items.forEach((li, i) => li.style.background = i === inp._activeIdx ? '#EFF6FF' : '');
                items[inp._activeIdx]?.scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (inp._activeIdx >= 0 && inp._activeIdx < items.length) {
                    pickMat(r, items[inp._activeIdx]);
                }
            } else if (e.key === 'Escape') {
                list.style.display = 'none';
            }
        }

        function addRow() {
            const r = rowIdx++;
            const tr = document.createElement('tr');
            tr.className = 'border-b hover:bg-gray-50';
            tr.id = `row-${r}`;
            tr.innerHTML = `
                <td class="px-2 py-1">
                    <input type="text" name="orders[${r}][order_number]" placeholder="Nomor PO..."
                        style="width:100%;border:1px solid #cbd5e1;border-radius:4px;padding:3px 6px;font-size:12px;font-family:monospace;" required>
                </td>
                <td class="px-2 py-1" style="position:relative;overflow:visible;">
                    <input type="text" id="mat-text-${r}" placeholder="Ketik kode / nama..." autocomplete="off"
                        style="width:100%;border:1px solid #cbd5e1;border-radius:4px;padding:3px 6px;font-size:12px;"
                        oninput="matSearch(${r}, this)" onkeydown="matKeydown(${r}, this, event)" onblur="hideMat(${r})">
                    <input type="hidden" name="orders[${r}][material_id]" id="mat-id-${r}" required>
                    <ul id="mat-list-${r}" style="display:none;position:absolute;top:100%;left:0;min-width:220px;background:#fff;border:1px solid #cbd5e1;border-radius:6px;max-height:160px;overflow-y:auto;z-index:999;list-style:none;margin:0;padding:4px 0;box-shadow:0 4px 12px rgba(0,0,0,.12);"></ul>
                </td>
                <td class="px-2 py-1">
                    <input type="number" name="orders[${r}][quantity_planned]" value="1" min="0.001" step="0.001" class="w-full border rounded px-2 py-1 text-sm text-right" required>
                </td>
                <td class="px-2 py-1">
                    <input type="text" name="orders[${r}][notes]" class="w-full border rounded px-2 py-1 text-sm" placeholder="opsional">
                </td>
                <td class="px-2 py-1 text-center">
                    <button type="button" onclick="document.getElementById('row-${r}').remove()" class="text-red-500 hover:text-red-700 text-lg">&#10005;</button>
                </td>
            `;
            document.getElementById('items-body').appendChild(tr);
        }

        // Start with one empty row
        addRow();

        // ── Import Excel ───────────────────────────────────────────────
        const IMPORT_URL = "{{ route('pp.production-orders.import-excel') }}";
        const IMPORT_CSRF = "{{ csrf_token() }}";
        let importedItems = [];

        async function readImportFile() {
            const fileInput = document.getElementById('import-file');
            const errBox    = document.getElementById('import-errors');
            const preview   = document.getElementById('import-preview');
            errBox.classList.add('hidden');
            preview.classList.add('hidden');
            importedItems = [];

            if (!fileInput.files.length) {
                errBox.innerHTML = '<div>Pilih file Excel terlebih dahulu.</div>';
                errBox.classList.remove('hidden');
                return;
            }

            const formData = new FormData();
            formData.append('file', fileInput.files[0]);
            formData.append('_token', IMPORT_CSRF);

            try {
                const res  = await fetch(IMPORT_URL, { method: 'POST', body: formData });
                const data = await res.json();

                if (data.errors && data.errors.length) {
                    errBox.innerHTML = data.errors.map(e => `<div>• ${e}</div>`).join('');
                    errBox.classList.remove('hidden');
                }

                if (data.items && data.items.length) {
                    importedItems = data.items;
                    const tbody = document.getElementById('preview-body');
                    tbody.innerHTML = data.items.map(it =>
                        `<tr class="border-b">
                            <td class="px-3 py-1.5 font-mono text-blue-700 font-medium">${it.order_number}</td>
                            <td class="px-3 py-1.5 font-mono text-gray-600">${it.material_code}</td>
                            <td class="px-3 py-1.5">${it.material_name}</td>
                            <td class="px-3 py-1.5 text-right">${Number(it.qty).toLocaleString('id-ID', {minimumFractionDigits:0, maximumFractionDigits:3})}</td>
                            <td class="px-3 py-1.5 text-gray-500">${it.notes || '—'}</td>
                        </tr>`
                    ).join('');
                    document.getElementById('preview-count').textContent = data.items.length;
                    preview.classList.remove('hidden');
                } else if (!data.errors || !data.errors.length) {
                    errBox.innerHTML = '<div>Tidak ada data yang valid ditemukan dalam file.</div>';
                    errBox.classList.remove('hidden');
                }
            } catch (e) {
                errBox.innerHTML = '<div>Terjadi kesalahan saat memproses file.</div>';
                errBox.classList.remove('hidden');
            }
        }

        function applyImport() {
            // Clear existing rows
            document.getElementById('items-body').innerHTML = '';
            rowIdx = 0;

            importedItems.forEach(it => {
                addRow();
                const idx = rowIdx - 1;
                // Fill order number
                const orderInput = document.querySelector(`[name="orders[${idx}][order_number]"]`);
                if (orderInput) orderInput.value = it.order_number;
                // Fill material
                document.getElementById(`mat-text-${idx}`).value = `${it.material_code} - ${it.material_name}`;
                document.getElementById(`mat-id-${idx}`).value   = it.material_id;
                // Fill qty
                const qtyInput = document.querySelector(`[name="orders[${idx}][quantity_planned]"]`);
                if (qtyInput) qtyInput.value = it.qty;
                // Fill notes
                const notesInput = document.querySelector(`[name="orders[${idx}][notes]"]`);
                if (notesInput) notesInput.value = it.notes || '';
            });

            clearImport();
            document.getElementById('import-panel').classList.add('hidden');
        }

        function clearImport() {
            document.getElementById('import-file').value = '';
            document.getElementById('import-errors').classList.add('hidden');
            document.getElementById('import-preview').classList.add('hidden');
            importedItems = [];
        }
    </script>
</x-app-layout>
