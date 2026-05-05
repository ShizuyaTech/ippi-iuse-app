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
                    <input type="date" name="planned_start_date" id="planned_start_date" value="{{ old('planned_start_date', date('Y-m-d')) }}" class="w-full border rounded px-3 py-2 text-sm" required>
                </div>
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tgl Selesai Rencana *</label>
                    <input type="date" name="planned_end_date" id="planned_end_date" value="{{ old('planned_end_date', date('Y-m-d')) }}" class="w-full border rounded px-3 py-2 text-sm" required>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Umum</label>
                    <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Opsional" class="w-full border rounded px-3 py-2 text-sm">
                </div>
            </div>

            {{-- Tabel item --}}
            <div>
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-semibold text-gray-700">Daftar Production Order</h3>
                    <button type="button" onclick="addRow()" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">+ Tambah Baris</button>
                </div>
                <div style="overflow:visible;">
                <table class="w-full text-sm border-collapse" id="items-table">
                    <thead class="bg-blue-900 text-white">
                        <tr>
                            <th class="px-3 py-2 text-left" style="min-width:200px">Material *</th>
                            <th class="px-3 py-2 text-left" style="min-width:160px">BOM *</th>
                            <th class="px-3 py-2 text-left" style="min-width:160px">Routing</th>
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
                <td class="px-2 py-1" style="position:relative;overflow:visible;">
                    <input type="text" id="mat-text-${r}" placeholder="Ketik kode / nama..." autocomplete="off"
                        style="width:100%;border:1px solid #cbd5e1;border-radius:4px;padding:3px 6px;font-size:12px;"
                        oninput="matSearch(${r}, this)" onkeydown="matKeydown(${r}, this, event)" onblur="hideMat(${r})">
                    <input type="hidden" name="orders[${r}][material_id]" id="mat-id-${r}" required>
                    <ul id="mat-list-${r}" style="display:none;position:absolute;top:100%;left:0;min-width:220px;background:#fff;border:1px solid #cbd5e1;border-radius:6px;max-height:160px;overflow-y:auto;z-index:999;list-style:none;margin:0;padding:4px 0;box-shadow:0 4px 12px rgba(0,0,0,.12);"></ul>
                </td>
                <td class="px-2 py-1">
                    <select name="orders[${r}][bom_id]" id="bom-${r}" class="w-full border rounded px-2 py-1 text-sm" required>
                        <option value="">-- pilih material --</option>
                    </select>
                </td>
                <td class="px-2 py-1">
                    <select name="orders[${r}][routing_id]" id="rtg-${r}" class="w-full border rounded px-2 py-1 text-sm">
                        <option value="">-</option>
                    </select>
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

        function onMaterialChange(r, materialId) {
            // Rebuild BOM options
            const bomSel = document.getElementById(`bom-${r}`);
            const filtered = BOMS.filter(b => b.material_id == materialId);
            bomSel.innerHTML = filtered.length
                ? filtered.map(b => `<option value="${b.id}">${b.number}</option>`).join('')
                : '<option value="">-- Tidak ada BOM --</option>';
            if (filtered.length === 1) bomSel.value = filtered[0].id;

            // Rebuild Routing options
            const rtgSel = document.getElementById(`rtg-${r}`);
            const filteredR = ROUTINGS.filter(x => x.material_id == materialId);
            rtgSel.innerHTML = '<option value="">-</option>' +
                filteredR.map(x => `<option value="${x.id}">${x.number}</option>`).join('');
            if (filteredR.length === 1) rtgSel.value = filteredR[0].id;
        }

        // Start with one empty row
        addRow();
    </script>
</x-app-layout>
