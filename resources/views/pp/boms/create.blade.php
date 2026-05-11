<x-app-layout>
    <x-slot name="title">Buat BOM</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Buat Bill of Materials Baru</h2>
        <form method="POST" action="{{ route('pp.boms.store') }}" id="bom-form" class="space-y-6">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Material Hasil (FP/WIP) *</label>
                    <div class="relative">
                        <input type="text" id="fp-search"
                               value="{{ old('material_id') ? ($materials->firstWhere('id', old('material_id'))?->code.' - '.$materials->firstWhere('id', old('material_id'))?->name) : '' }}"
                               placeholder="Ketik kode atau nama material..."
                               autocomplete="off"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                               oninput="fpSearch(this)"
                               onkeydown="fpKeydown(event)">
                        <input type="hidden" name="material_id" id="fp-id" value="{{ old('material_id') }}">
                        <div id="fp-suggestions"
                             class="absolute z-50 w-full bg-white border border-gray-200 rounded-b shadow-lg max-h-52 overflow-y-auto hidden"></div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Qty Base *</label>
                    <input type="number" name="base_quantity" value="{{ old('base_quantity', 1) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" min="0.001" step="0.001" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Berlaku Mulai *</label>
                    <input type="date" name="valid_from" value="{{ old('valid_from', user_now()->format('Y-m-d')) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Berlaku Hingga</label>
                    <input type="date" name="valid_to" value="{{ old('valid_to') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="status" value="inactive">
                <input type="checkbox" name="status" id="status" value="active" {{ old('status', 'active')==='active' ? 'checked' : '' }} class="rounded">
                <label for="status" class="text-sm text-gray-700">Aktif</label>
            </div>

            <div>
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-semibold text-gray-700">Komponen BOM</h3>
                    <button type="button" onclick="addRow()" class="bg-green-600 text-white px-3 py-1 rounded text-sm">+ Tambah Komponen</button>
                </div>
                <table class="w-full text-sm border-collapse" style="overflow:visible;">
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
                <button type="submit" class="bg-blue-700 text-white px-6 py-2 rounded text-sm">Simpan BOM</button>
                <a href="{{ route('pp.boms.index') }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded text-sm">Batal</a>
            </div>
        </form>
    </div>
    <script>
        @php
            $materialJson = $materials->map(fn($m) => ['id'=>$m->id,'code'=>$m->code,'name'=>$m->name,'type'=>$m->type,'uom'=>$m->unit_of_measure]);
        @endphp
        const materials = @json($materialJson);
        let r = 0;

        // ── Material Hasil autocomplete ────────────────────────────
        function fpSearch(input) {
            input._activeIdx = -1;
            const q = input.value.trim().toLowerCase();
            const box = document.getElementById('fp-suggestions');
            document.getElementById('fp-id').value = '';
            if (!q) { box.classList.add('hidden'); return; }
            const matches = materials.filter(m =>
                m.code.toLowerCase().includes(q) || m.name.toLowerCase().includes(q)
            ).slice(0, 20);
            if (!matches.length) { box.classList.add('hidden'); return; }
            box.innerHTML = matches.map(m =>
                `<div class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50 border-b border-gray-100"
                      data-id="${m.id}" data-label="${m.code} - ${m.name}" data-uom="${m.uom ?? ''}">
                    <span class="font-mono text-blue-600 font-semibold text-xs">${m.code}</span>
                    <span class="ml-2 text-gray-700">${m.name}</span>
                    <span class="ml-1 text-gray-400 text-xs">(${m.type})</span>
                </div>`
            ).join('');
            box.classList.remove('hidden');
        }

        document.getElementById('fp-suggestions').addEventListener('click', function(e) {
            const item = e.target.closest('[data-id]');
            if (!item) return;
            document.getElementById('fp-search').value = item.dataset.label;
            document.getElementById('fp-id').value = item.dataset.id;
            this.classList.add('hidden');
        });

        function fpKeydown(e) {
            const box = document.getElementById('fp-suggestions');
            if (box.classList.contains('hidden')) return;
            const inp = document.getElementById('fp-search');
            const items = box.querySelectorAll('[data-id]');
            if (!items.length) return;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                inp._activeIdx = Math.min((inp._activeIdx ?? -1) + 1, items.length - 1);
                items.forEach((el, i) => el.style.background = i === inp._activeIdx ? '#EFF6FF' : '');
                items[inp._activeIdx]?.scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                inp._activeIdx = Math.max((inp._activeIdx ?? 0) - 1, 0);
                items.forEach((el, i) => el.style.background = i === inp._activeIdx ? '#EFF6FF' : '');
                items[inp._activeIdx]?.scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (inp._activeIdx >= 0 && inp._activeIdx < items.length) {
                    const el = items[inp._activeIdx];
                    inp.value = el.dataset.label;
                    document.getElementById('fp-id').value = el.dataset.id;
                    box.classList.add('hidden');
                }
            } else if (e.key === 'Escape') {
                box.classList.add('hidden');
            }
        }

        // ── Component row autocomplete ─────────────────────────────
        function addRow(mid=null, qty=1, uom='', label='') {
            const tr = document.createElement('tr');
            tr.className = 'border-b';
            tr.innerHTML = `
                <td class="px-2 py-1">
                    <div class="relative">
                        <input type="text" id="comp-search-${r}"
                               value="${label}"
                               placeholder="Ketik kode atau nama material..."
                               autocomplete="off"
                               class="w-full border rounded px-2 py-1 text-sm"
                               oninput="compSearch(${r}, this)"
                               onkeydown="compKeydown(${r}, event)">
                        <input type="hidden" name="items[${r}][material_id]" id="comp-id-${r}" value="${mid ?? ''}">
                        <div id="comp-sug-${r}"
                             class="absolute z-50 bg-white border border-gray-200 rounded-b shadow-lg max-h-48 overflow-y-auto hidden"
                             style="min-width:320px; top:100%; left:0;"></div>
                    </div>
                </td>
                <td class="px-2 py-1">
                    <input type="number" name="items[${r}][quantity]" value="${qty}"
                           class="w-full border rounded px-2 py-1 text-sm text-right" min="0.001" step="0.001" required>
                </td>
                <td class="px-2 py-1">
                    <input type="text" name="items[${r}][unit]" id="comp-uom-${r}" value="${uom}"
                           class="w-full border rounded px-2 py-1 text-sm" placeholder="PCS">
                </td>
                <td class="px-2 py-1 text-center">
                    <button type="button" onclick="this.closest('tr').remove()" class="text-red-500 text-lg leading-none">&times;</button>
                </td>
            `;
            document.getElementById('items-body').appendChild(tr);

            document.getElementById(`comp-sug-${r}`).addEventListener('click', function(e) {
                const item = e.target.closest('[data-id]');
                if (!item) return;
                const row = item.dataset.row;
                document.getElementById(`comp-search-${row}`).value = item.dataset.label;
                document.getElementById(`comp-id-${row}`).value = item.dataset.id;
                document.getElementById(`comp-uom-${row}`).value = item.dataset.uom || '';
                this.classList.add('hidden');
            });
            r++;
        }

        function compSearch(idx, input) {
            input._activeIdx = -1;
            const q = input.value.trim().toLowerCase();
            const box = document.getElementById(`comp-sug-${idx}`);
            document.getElementById(`comp-id-${idx}`).value = '';
            if (!q) { box.classList.add('hidden'); return; }
            const matches = materials.filter(m =>
                m.code.toLowerCase().includes(q) || m.name.toLowerCase().includes(q)
            ).slice(0, 20);
            if (!matches.length) { box.classList.add('hidden'); return; }
            box.innerHTML = matches.map(m =>
                `<div class="px-3 py-2 text-xs cursor-pointer hover:bg-blue-50 border-b border-gray-100"
                      data-id="${m.id}" data-row="${idx}" data-label="${m.code} - ${m.name}" data-uom="${m.uom ?? ''}">
                    <span class="font-mono text-blue-600 font-semibold">${m.code}</span>
                    <span class="ml-2 text-gray-700">${m.name}</span>
                    <span class="ml-1 text-gray-400">(${m.uom ?? '-'})</span>
                </div>`
            ).join('');
            box.classList.remove('hidden');
        }

        // Close all dropdowns on outside click
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#fp-search') && !e.target.closest('#fp-suggestions')) {
                document.getElementById('fp-suggestions').classList.add('hidden');
            }
            if (!e.target.closest('[id^="comp-search-"]') && !e.target.closest('[id^="comp-sug-"]')) {
                document.querySelectorAll('[id^="comp-sug-"]').forEach(b => b.classList.add('hidden'));
            }
        });

        function compKeydown(idx, e) {
            const box = document.getElementById(`comp-sug-${idx}`);
            if (!box || box.classList.contains('hidden')) return;
            const inp = document.getElementById(`comp-search-${idx}`);
            const items = box.querySelectorAll('[data-id]');
            if (!items.length) return;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                inp._activeIdx = Math.min((inp._activeIdx ?? -1) + 1, items.length - 1);
                items.forEach((el, i) => el.style.background = i === inp._activeIdx ? '#EFF6FF' : '');
                items[inp._activeIdx]?.scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                inp._activeIdx = Math.max((inp._activeIdx ?? 0) - 1, 0);
                items.forEach((el, i) => el.style.background = i === inp._activeIdx ? '#EFF6FF' : '');
                items[inp._activeIdx]?.scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (inp._activeIdx >= 0 && inp._activeIdx < items.length) {
                    const el = items[inp._activeIdx];
                    inp.value = el.dataset.label;
                    document.getElementById(`comp-id-${idx}`).value = el.dataset.id;
                    document.getElementById(`comp-uom-${idx}`).value = el.dataset.uom || '';
                    box.classList.add('hidden');
                }
            } else if (e.key === 'Escape') {
                box.classList.add('hidden');
            }
        }

        // Validate on submit
        document.getElementById('bom-form').addEventListener('submit', function(e) {
            if (!document.getElementById('fp-id').value) {
                e.preventDefault();
                document.getElementById('fp-search').focus();
                document.getElementById('fp-search').classList.add('border-red-500');
                alert('Pilih material hasil dari daftar saran terlebih dahulu.');
                return;
            }
            let ok = true;
            document.querySelectorAll('[id^="comp-id-"]').forEach(function(hidden) {
                if (!hidden.value) {
                    const idx = hidden.id.replace('comp-id-', '');
                    const inp = document.getElementById('comp-search-' + idx);
                    if (inp) { inp.classList.add('border-red-500'); ok = false; }
                }
            });
            if (!ok) {
                e.preventDefault();
                alert('Pilih material komponen dari daftar saran untuk semua baris.');
            }
        });

        addRow();
    </script>
</x-app-layout>
