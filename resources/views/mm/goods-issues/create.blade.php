<x-app-layout>
    <x-slot name="title">Buat Goods Issue</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Buat Goods Issue</h2>

        @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-300 text-red-700 rounded text-sm">
            @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('mm.goods-issues.store') }}" class="space-y-6">
            @csrf

            {{-- Row 1: Tanggal + Lokasi Asal --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Issue *</label>
                    <input type="date" name="issue_date" value="{{ old('issue_date', user_now()->format('Y-m-d')) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dari Storage Location *</label>
                    <select name="storage_location_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                        <option value="">-- Pilih Lokasi --</option>
                        @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ old('storage_location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->code }} - {{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Row 2: Tipe Issue + Tujuan --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Issue *</label>
                    <select id="issue_type" name="issue_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required onchange="toggleDestination()">
                        <option value="internal"     {{ old('issue_type','internal')=='internal'     ? 'selected' : '' }}>Pemakaian Internal</option>
                        <option value="to_vendor"    {{ old('issue_type')=='to_vendor'    ? 'selected' : '' }}>Kirim ke Vendor (Proses)</option>
                        <option value="to_customer"  {{ old('issue_type')=='to_customer'  ? 'selected' : '' }}>Kirim ke Customer</option>
                    </select>
                    <p id="hint-internal"    class="mt-1 text-xs text-gray-400">Stok dikeluarkan untuk konsumsi produksi internal.</p>
                    <p id="hint-to_vendor"   class="mt-1 text-xs text-blue-500 hidden">Material dikirim ke vendor untuk diproses menjadi WIP / Finish Goods, lalu dikembalikan via Goods Receipt.</p>
                    <p id="hint-to_customer" class="mt-1 text-xs text-green-600 hidden">Finish Goods dikirim ke customer. Catat nama / ID customer pada kolom Tujuan.</p>
                </div>
                <div id="destination-wrap" class="hidden">
                    <label id="destination-label" class="block text-sm font-medium text-gray-700 mb-1">Tujuan *</label>
                    {{-- Internal: destination storage location --}}
                    <select id="location-select" name="destination_storage_location_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm hidden" disabled>
                        <option value="">-- Pilih Lokasi Tujuan --</option>
                        @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ old('destination_storage_location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->code }} - {{ $loc->name }}</option>
                        @endforeach
                    </select>
                    {{-- Vendor dropdown (to_vendor) --}}
                    <select id="vendor-select" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm hidden" disabled>
                        <option value="">-- Pilih Vendor --</option>
                        @foreach($vendors as $v)
                        <option value="{{ $v->id }}" data-name="{{ $v->name }}" {{ old('vendor_id') == $v->id ? 'selected' : '' }}>{{ $v->code }} - {{ $v->name }}</option>
                        @endforeach
                    </select>
                    {{-- Hidden fields for GI to_vendor --}}
                    <input type="hidden" id="vendor-id-input" name="vendor_id" value="{{ old('vendor_id') }}">
                    <input type="hidden" id="vendor-destination-name" name="destination_name" value="{{ old('destination_name') }}">
                    {{-- Customer dropdown (to_customer) --}}
                    <select id="customer-select" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm hidden" disabled>
                        <option value="">-- Pilih Customer --</option>
                        @foreach($customers as $c)
                        <option value="{{ $c->id }}" data-name="{{ $c->name }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->code }} - {{ $c->name }}</option>
                        @endforeach
                    </select>
                    {{-- Hidden fields for GI to_customer --}}
                    <input type="hidden" id="customer-id-input" name="customer_id" value="{{ old('customer_id') }}">
                    <input type="hidden" id="customer-destination-name" name="destination_name" value="{{ old('destination_name') }}">
                </div>
            </div>

            {{-- Notes --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">{{ old('notes') }}</textarea>
            </div>

            {{-- Items Table --}}
            <div>
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-semibold text-gray-700">Material yang Dikeluarkan</h3>
                    <button type="button" onclick="addRow()" class="bg-green-600 text-white px-3 py-1 rounded text-sm">+ Tambah Baris</button>
                </div>
                @error('items')
                <p class="text-red-600 text-xs mb-2">{{ $message }}</p>
                @enderror
                <div class="overflow-x-auto" style="overflow:visible;">
                    <table class="w-full text-sm border-collapse" style="overflow:visible;">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-3 py-2 text-left">Material</th>
                                <th class="px-3 py-2 text-right w-32">Qty</th>
                                <th class="px-3 py-2 text-left w-56">Note / ID Packing</th>
                                <th class="px-3 py-2 w-10"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body"></tbody>
                    </table>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="submit" id="btn-post-gi"
                        class="bg-orange-600 text-white px-6 py-2 rounded text-sm hover:bg-orange-700">
                    Post Goods Issue
                </button>
                <a href="{{ route('mm.goods-issues.index') }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded text-sm hover:bg-gray-300">Batal</a>
            </div>
        </form>
    </div>

    <script>
        @php
            $materialJson = $materials->map(fn($m) => ['id'=>$m->id,'code'=>$m->code,'name'=>$m->name]);
        @endphp
        const materials = @json($materialJson);
        let r = 0;

        function addRow() {
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
                             class="absolute z-50 bg-white border border-gray-200 rounded-b shadow-lg max-h-48 overflow-y-auto hidden"
                             style="min-width:320px; top:100%; left:0;"></div>
                    </div>
                </td>
                <td class="px-2 py-1">
                    <input type="number" name="items[${r}][quantity]" class="w-full border rounded px-2 py-1 text-sm text-right" min="0.001" step="0.001" value="1" required>
                </td>
                <td class="px-2 py-1">
                    <input type="text" name="items[${r}][note]" class="w-full border rounded px-2 py-1 text-sm" placeholder="Contoh: PKG-0042">
                </td>
                <td class="px-2 py-1 text-center">
                    <button type="button" onclick="this.closest('tr').remove()" class="text-red-500 hover:text-red-700 text-lg leading-none">&times;</button>
                </td>
            `;
            document.getElementById('items-body').appendChild(tr);
            // Attach suggestion-click handler for this row
            document.getElementById(`mat-sug-${r}`).addEventListener('click', function(e) {
                const item = e.target.closest('[data-id]');
                if (!item) return;
                document.getElementById(`mat-search-${item.dataset.row}`).value = item.dataset.label;
                document.getElementById(`mat-id-${item.dataset.row}`).value = item.dataset.id;
                this.classList.add('hidden');
            });
            r++;
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
                </div>`
            ).join('');
            box.classList.remove('hidden');
        }

        // Close all suggestion boxes on outside click
        document.addEventListener('click', function(e) {
            if (!e.target.closest('[id^="mat-search-"]') && !e.target.closest('[id^="mat-sug-"]')) {
                document.querySelectorAll('[id^="mat-sug-"]').forEach(b => b.classList.add('hidden'));
            }
            if (!e.target.closest('#vendor-search') && !e.target.closest('#vendor-suggestions')) {
                const vs = document.getElementById('vendor-suggestions');
                if (vs) vs.classList.add('hidden');
            }
        });

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

        function toggleDestination() {
            const type = document.getElementById('issue_type').value;
            const wrap = document.getElementById('destination-wrap');
            const locSel      = document.getElementById('location-select');
            const vendorSel   = document.getElementById('vendor-select');
            const customerSel = document.getElementById('customer-select');
            const label = document.getElementById('destination-label');
            const hints = {
                'internal':    document.getElementById('hint-internal'),
                'to_vendor':   document.getElementById('hint-to_vendor'),
                'to_customer': document.getElementById('hint-to_customer'),
            };

            // show/hide hints
            Object.keys(hints).forEach(k => hints[k].classList.toggle('hidden', k !== type));

            // reset all
            [locSel, vendorSel, customerSel].forEach(el => {
                el.classList.add('hidden');
                el.disabled = true;
                el.name = '';
            });
            // clear hidden fields when type changes
            document.getElementById('vendor-id-input').value = '';
            document.getElementById('vendor-destination-name').value = '';
            document.getElementById('customer-id-input').value = '';
            document.getElementById('customer-destination-name').value = '';

            if (type === 'internal') {
                wrap.classList.remove('hidden');
                label.textContent = 'Lokasi Tujuan (opsional)';
                locSel.classList.remove('hidden');
                locSel.disabled = false;
                locSel.name = 'destination_storage_location_id';
            } else if (type === 'to_vendor') {
                wrap.classList.remove('hidden');
                label.textContent = 'Tujuan Vendor *';
                vendorSel.classList.remove('hidden');
                vendorSel.disabled = false;
                // sync hidden inputs when vendor changes
                vendorSel.onchange = function() {
                    const opt = this.options[this.selectedIndex];
                    document.getElementById('vendor-id-input').value = opt.value || '';
                    document.getElementById('vendor-destination-name').value = opt.dataset.name || '';
                };
                // sync immediately in case old value is present
                if (vendorSel.value) {
                    const opt = vendorSel.options[vendorSel.selectedIndex];
                    document.getElementById('vendor-id-input').value = opt.value || '';
                    document.getElementById('vendor-destination-name').value = opt.dataset.name || '';
                }
            } else {
                wrap.classList.remove('hidden');
                label.textContent = 'Tujuan Customer *';
                customerSel.classList.remove('hidden');
                customerSel.disabled = false;
                // sync hidden inputs when customer changes
                customerSel.onchange = function() {
                    const opt = this.options[this.selectedIndex];
                    document.getElementById('customer-id-input').value = opt.value || '';
                    document.getElementById('customer-destination-name').value = opt.dataset.name || '';
                };
                // sync immediately in case old value is present
                if (customerSel.value) {
                    const opt = customerSel.options[customerSel.selectedIndex];
                    document.getElementById('customer-id-input').value = opt.value || '';
                    document.getElementById('customer-destination-name').value = opt.dataset.name || '';
                }
            }
        }

        // Init on load
        document.addEventListener('DOMContentLoaded', function () {
            toggleDestination();
            addRow();
        });

    </script>
</x-app-layout>

