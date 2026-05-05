<x-app-layout>
    <x-slot name="title">Buat Routing</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Buat Routing Produksi Baru</h2>
        <form method="POST" action="{{ route('pp.routings.store') }}" class="space-y-6">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div style="position:relative;overflow:visible;">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Material *</label>
                    <input type="text" id="mat-text" placeholder="Ketik kode / nama material..." autocomplete="off"
                        class="w-full border rounded px-3 py-2 text-sm" oninput="matSearch(this)" onkeydown="matKeydown(event)" onblur="hideMat()">
                    <input type="hidden" name="material_id" id="mat-id" required>
                    <ul id="mat-list" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #cbd5e1;border-radius:6px;max-height:180px;overflow-y:auto;z-index:999;list-style:none;margin:0;padding:4px 0;box-shadow:0 4px 12px rgba(0,0,0,.12);"></ul>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                    <input type="text" name="description" value="{{ old('description') }}" class="w-full border rounded px-3 py-2 text-sm">
                </div>
            </div>
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-700 mr-2">Status:</label>
                <select name="status" class="border rounded px-3 py-2 text-sm">
                    <option value="active" {{ old('status','active')==='active'?'selected':'' }}>Aktif</option>
                    <option value="inactive" {{ old('status')==='inactive'?'selected':'' }}>Nonaktif</option>
                </select>
            </div>
            <div>
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-semibold text-gray-700">Operasi Routing</h3>
                    <button type="button" onclick="addOp()" class="bg-green-600 text-white px-3 py-1 rounded text-sm">+ Tambah Operasi</button>
                </div>
                <table class="w-full text-sm border-collapse">
                    <thead class="bg-gray-100"><tr>
                        <th class="px-3 py-2 text-left w-16">No. Op</th>
                        <th class="px-3 py-2 text-left">Deskripsi Operasi</th>
                        <th class="px-3 py-2 text-left">Work Center</th>
                        <th class="px-3 py-2 text-right w-28">Std. Time (jam)</th>
                        <th class="px-3 py-2 w-12"></th>
                    </tr></thead>
                    <tbody id="ops-body"></tbody>
                </table>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="bg-blue-700 text-white px-6 py-2 rounded text-sm">Simpan Routing</button>
                <a href="{{ route('pp.routings.index') }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded text-sm">Batal</a>
            </div>
        </form>
    </div>
    <script>
        @php
            $wcJson  = $workCenters->map(fn($w) => ['id'=>$w->id,'code'=>$w->code,'name'=>$w->name]);
            $matJson = $materials->map(fn($m) => ['id'=>$m->id,'code'=>$m->code,'name'=>$m->name]);
        @endphp
        const workCenters = @json($wcJson);
        const ALL_MATS    = @json($matJson);

        function matSearch(inp) {
            inp._activeIdx = -1;
            const q = inp.value.trim().toLowerCase();
            document.getElementById('mat-id').value = '';
            const list = document.getElementById('mat-list');
            if (!q) { list.style.display = 'none'; return; }
            const hits = ALL_MATS.filter(m => m.code.toLowerCase().includes(q) || m.name.toLowerCase().includes(q)).slice(0, 20);
            if (!hits.length) { list.style.display = 'none'; return; }
            list.innerHTML = hits.map(m =>
                `<li data-id="${m.id}" data-label="${m.code} - ${m.name}" style="padding:6px 10px;cursor:pointer;font-size:13px;" onmousedown="pickMat(event)">
                    <span style="font-weight:600">${m.code}</span> &mdash; ${m.name}
                </li>`
            ).join('');
            list.style.display = 'block';
        }
        function pickMat(e) {
            const li = e.currentTarget;
            document.getElementById('mat-text').value = li.dataset.label;
            document.getElementById('mat-id').value   = li.dataset.id;
            document.getElementById('mat-list').style.display = 'none';
        }
        function matKeydown(e) {
            const list = document.getElementById('mat-list');
            if (list.style.display === 'none') return;
            const inp = document.getElementById('mat-text');
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
                    const li = items[inp._activeIdx];
                    inp.value = li.dataset.label;
                    document.getElementById('mat-id').value = li.dataset.id;
                    list.style.display = 'none';
                }
            } else if (e.key === 'Escape') {
                list.style.display = 'none';
            }
        }
        function hideMat() {
            setTimeout(() => { document.getElementById('mat-list').style.display = 'none'; }, 150);
        }

        let r = 0;
        function addOp(opNo=null, name='', wcId=null, time=1){
            const wcOpts = workCenters.map(w=>`<option value="${w.id}" ${wcId==w.id?'selected':''}>${w.code} - ${w.name}</option>`).join('');
            const tr = document.createElement('tr');
            tr.className='border-b';
            tr.innerHTML=`
                <td class="px-2 py-1"><input type="number" name="operations[${r}][operation_number]" value="${opNo ?? (r+1)*10}" class="w-full border rounded px-2 py-1 text-sm" required></td>
                <td class="px-2 py-1"><input type="text" name="operations[${r}][description]" value="${name}" class="w-full border rounded px-2 py-1 text-sm" required placeholder="e.g. Pemotongan"></td>
                <td class="px-2 py-1"><select name="operations[${r}][work_center_id]" class="w-full border rounded px-2 py-1 text-sm" required><option value="">-- Pilih --</option>${wcOpts}</select></td>
                <td class="px-2 py-1"><input type="number" name="operations[${r}][standard_time]" value="${time}" class="w-full border rounded px-2 py-1 text-sm text-right" min="0.001" step="0.001" required></td>
                <td class="px-2 py-1 text-center"><button type="button" onclick="this.closest('tr').remove()" class="text-red-500">&#10005;</button></td>
            `;
            document.getElementById('ops-body').appendChild(tr); r++;
        }
        addOp();
    </script>
</x-app-layout>
