<x-app-layout>
    <x-slot name="title">Buat Kiriman Bahan ke Vendor</x-slot>
    <div class="max-w-4xl bg-white rounded-lg shadow p-6">
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('mm.vendor-deliveries.index') }}" class="text-blue-600 hover:underline text-sm">← Kembali</a>
            <h2 class="text-lg font-semibold text-gray-700">Buat Kiriman Bahan ke Vendor</h2>
        </div>

        @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-2 rounded text-sm">
            <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        <form method="POST" action="{{ route('mm.vendor-deliveries.store') }}">
            @csrf

            {{-- Header --}}
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vendor *</label>
                    <select name="vendor_id" class="w-full border rounded px-3 py-2 text-sm" required>
                        <option value="">-- Pilih Vendor --</option>
                        @foreach($vendors as $v)
                        <option value="{{ $v->id }}" {{ old('vendor_id')==$v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Kirim *</label>
                    <input type="date" name="delivery_date" value="{{ old('delivery_date', user_now()->format('Y-m-d')) }}"
                        class="w-full border rounded px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Storage Location (Sumber) *</label>
                    <select name="storage_location_id" class="w-full border rounded px-3 py-2 text-sm" required>
                        <option value="">-- Pilih Lokasi --</option>
                        @foreach($locations as $l)
                        <option value="{{ $l->id }}" {{ old('storage_location_id')==$l->id ? 'selected' : '' }}>{{ $l->code }} – {{ $l->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">No. Kendaraan</label>
                    <input type="text" name="vehicle_number" value="{{ old('vehicle_number') }}"
                        class="w-full border rounded px-3 py-2 text-sm" placeholder="Contoh: B 1234 CD">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Driver</label>
                    <input type="text" name="driver_name" value="{{ old('driver_name') }}"
                        class="w-full border rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                    <input type="text" name="notes" value="{{ old('notes') }}"
                        class="w-full border rounded px-3 py-2 text-sm">
                </div>
            </div>

            {{-- Items --}}
            <div class="mb-6" style="overflow:visible;">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-semibold text-gray-700">Item Material</h3>
                    <button type="button" onclick="addRow()" class="bg-blue-100 text-blue-700 px-3 py-1 rounded text-xs hover:bg-blue-200">+ Tambah Item</button>
                </div>
                <div class="overflow-x-auto" style="overflow:visible;">
                    <table class="w-full text-sm border-collapse" style="overflow:visible;">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-3 py-2 text-left">Material *</th>
                                <th class="px-3 py-2 text-right w-32">Qty *</th>
                                <th class="px-3 py-2 text-left w-48">Catatan</th>
                                <th class="px-3 py-2 w-8"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body"></tbody>
                    </table>
                </div>
                <p id="empty-hint" class="text-center text-gray-400 text-sm py-4">Klik "+ Tambah Item" untuk menambahkan material.</p>
            </div>

            <div class="flex gap-3">
                <button type="submit" class="bg-blue-700 text-white px-6 py-2 rounded text-sm hover:bg-blue-800">Kirim ke Vendor</button>
                <a href="{{ route('mm.vendor-deliveries.index') }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded text-sm hover:bg-gray-300">Batal</a>
            </div>
        </form>
    </div>

    <script>
        @php
            $materialJson = $materials->map(fn($m) => [
                'id'   => $m->id,
                'code' => $m->code,
                'name' => $m->name,
                'uom'  => $m->unit_of_measure,
            ]);
        @endphp
        const materials = @json($materialJson);
        let r = 0;

        function addRow() {
            document.getElementById('empty-hint').classList.add('hidden');
            const tr = document.createElement('tr');
            tr.className = 'border-b';
            tr.innerHTML = `
                <td class="px-2 py-1">
                    <div class="relative">
                        <input type="text" id="mat-search-${r}"
                               placeholder="Ketik kode atau nama material..."
                               autocomplete="off"
                               class="w-full border rounded px-2 py-1 text-sm"
                               oninput="matSearch(${r}, this)"
                               onkeydown="matKeydown(${r}, this, event)">
                        <input type="hidden" name="items[${r}][material_id]" id="mat-id-${r}">
                        <div id="mat-sug-${r}"
                             class="absolute z-50 bg-white border border-gray-200 rounded shadow-lg max-h-48 overflow-y-auto hidden"
                             style="min-width:320px; top:100%; left:0;"></div>
                    </div>
                </td>
                <td class="px-2 py-1">
                    <input type="number" name="items[${r}][quantity]"
                        class="w-full border rounded px-2 py-1 text-sm text-right"
                        min="0.001" step="0.001" value="1" required>
                </td>
                <td class="px-2 py-1">
                    <input type="text" name="items[${r}][notes]"
                        class="w-full border rounded px-2 py-1 text-sm" maxlength="100">
                </td>
                <td class="px-2 py-1 text-center">
                    <button type="button" onclick="removeRow(this)" class="text-red-500 hover:text-red-700 text-lg leading-none">&times;</button>
                </td>
            `;
            document.getElementById('items-body').appendChild(tr);

            document.getElementById(`mat-sug-${r}`).addEventListener('click', function(e) {
                const item = e.target.closest('[data-id]');
                if (!item) return;
                document.getElementById(`mat-search-${item.dataset.row}`).value = item.dataset.label;
                document.getElementById(`mat-id-${item.dataset.row}`).value = item.dataset.id;
                this.classList.add('hidden');
            });
            r++;
        }

        function removeRow(btn) {
            btn.closest('tr').remove();
            if (document.getElementById('items-body').children.length === 0) {
                document.getElementById('empty-hint').classList.remove('hidden');
            }
        }

        function matSearch(idx, input) {
            input._activeIdx = -1;
            const q = input.value.trim().toLowerCase();
            const box = document.getElementById(`mat-sug-${idx}`);
            document.getElementById(`mat-id-${idx}`).value = '';
            if (!q) { box.classList.add('hidden'); return; }
            const matches = materials.filter(m =>
                m.code.toLowerCase().includes(q) || m.name.toLowerCase().includes(q)
            ).slice(0, 20);
            if (!matches.length) { box.classList.add('hidden'); return; }
            box.innerHTML = matches.map(m =>
                `<div class="px-3 py-2 text-xs cursor-pointer hover:bg-blue-50 border-b border-gray-100"
                      data-id="${m.id}" data-row="${idx}" data-label="${m.code} - ${m.name}">
                    <span class="font-mono text-blue-600 font-semibold">${m.code}</span>
                    <span class="ml-2 text-gray-700">${m.name}</span>
                    <span class="ml-1 text-gray-400">(${m.uom})</span>
                </div>`
            ).join('');
            box.classList.remove('hidden');
        }

        function matKeydown(idx, inp, e) {
            const box = document.getElementById(`mat-sug-${idx}`);
            if (!box || box.classList.contains('hidden')) return;
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
                    document.getElementById(`mat-search-${idx}`).value = el.dataset.label;
                    document.getElementById(`mat-id-${idx}`).value = el.dataset.id;
                    box.classList.add('hidden');
                }
            } else if (e.key === 'Escape') {
                box.classList.add('hidden');
            }
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('[id^="mat-search-"]') && !e.target.closest('[id^="mat-sug-"]')) {
                document.querySelectorAll('[id^="mat-sug-"]').forEach(b => b.classList.add('hidden'));
            }
        });

        // Start with one row
        addRow();
    </script>
</x-app-layout>

