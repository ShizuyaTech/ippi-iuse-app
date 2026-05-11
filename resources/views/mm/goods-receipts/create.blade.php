<x-app-layout>
    <x-slot name="title">Buat Goods Receipt</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Buat Goods Receipt</h2>

        @isset($deliveryNote)
        <div class="mb-4 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded text-sm">
            <strong>Dibuat dari Surat Jalan:</strong>
            <span class="font-mono font-semibold">{{ $deliveryNote->dn_number }}</span>
            &mdash; Qty item sudah diisi sesuai surat jalan. Centang item yang ingin diproses.
        </div>
        @endisset

        {{-- PO Selection --}}
        @php
            $posJson = $pos->map(fn($p) => ['id' => $p->id, 'label' => $p->po_number.' - '.$p->vendor->name]);
            $selectedPoLabel = request('po_id') ? ($pos->firstWhere('id', request('po_id'))?->po_number.' - '.$pos->firstWhere('id', request('po_id'))?->vendor?->name) : '';
        @endphp
        <form method="GET" id="po-select-form" class="mb-6 p-4 bg-gray-50 rounded">
            @isset($deliveryNote)
            <input type="hidden" name="dn_id" value="{{ $deliveryNote->id }}">
            @endisset
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Purchase Order</label>
                <div class="relative">
                    <input type="text" id="po-search"
                           value="{{ $selectedPoLabel }}"
                           placeholder="Ketik nomor PO atau nama vendor..."
                           autocomplete="off"
                           class="w-full border rounded px-3 py-2 text-sm"
                           oninput="poSearch(this)"
                           onkeydown="poKeydown(event)">
                    <input type="hidden" name="po_id" id="po-id-hidden" value="{{ request('po_id') }}">
                    <div id="po-suggestions"
                         class="absolute z-50 w-full bg-white border border-gray-200 rounded-b shadow-lg max-h-60 overflow-y-auto hidden"></div>
                </div>
            </div>
        </form>

        <script>
            const allPos = @json($posJson);

            function poSearch(input) {
                input._activeIdx = -1;
                const q = input.value.trim().toLowerCase();
                const box = document.getElementById('po-suggestions');
                document.getElementById('po-id-hidden').value = '';
                if (!q) { box.classList.add('hidden'); return; }
                const matches = allPos.filter(p => p.label.toLowerCase().includes(q)).slice(0, 30);
                if (!matches.length) { box.classList.add('hidden'); return; }
                box.innerHTML = matches.map(p =>
                    `<div class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50 border-b border-gray-100"
                          data-id="${p.id}" data-label="${p.label}">
                        <span class="font-mono text-blue-600 font-semibold">${p.label.split(' - ')[0]}</span>
                        <span class="ml-2 text-gray-700">${p.label.split(' - ').slice(1).join(' - ')}</span>
                    </div>`
                ).join('');
                box.classList.remove('hidden');
            }

            document.getElementById('po-suggestions').addEventListener('click', function(e) {
                const item = e.target.closest('[data-id]');
                if (!item) return;
                document.getElementById('po-search').value = item.dataset.label;
                document.getElementById('po-id-hidden').value = item.dataset.id;
                this.classList.add('hidden');
                document.getElementById('po-select-form').submit();
            });

            function poKeydown(e) {
                const box = document.getElementById('po-suggestions');
                if (box.classList.contains('hidden')) return;
                const inp = document.getElementById('po-search');
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
                        document.getElementById('po-id-hidden').value = el.dataset.id;
                        box.classList.add('hidden');
                        document.getElementById('po-select-form').submit();
                    }
                } else if (e.key === 'Escape') {
                    box.classList.add('hidden');
                }
            }

            document.addEventListener('click', function(e) {
                if (!e.target.closest('#po-search') && !e.target.closest('#po-suggestions')) {
                    const box = document.getElementById('po-suggestions');
                    if (box) box.classList.add('hidden');
                }
            });
        </script>

        @if($selectedPo)
        <form method="POST" action="{{ route('mm.goods-receipts.store') }}" class="space-y-4"
              onkeydown="if(event.key==='Enter' && event.target.tagName !== 'TEXTAREA' && event.target.type !== 'submit'){ event.preventDefault(); }">
            @csrf
            <input type="hidden" name="purchase_order_id" value="{{ $selectedPo->id }}">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Penerimaan *</label>
                    <input type="date" name="receipt_date" value="{{ user_now()->format('Y-m-d') }}" class="w-full border rounded px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Storage Location</label>
                    <input type="hidden" name="storage_location_id" value="{{ $selectedPo->storage_location_id }}">
                    <div class="border rounded px-3 py-2 text-sm bg-gray-50 text-gray-700">
                        {{ $selectedPo->storageLocation?->code }} - {{ $selectedPo->storageLocation?->name }}
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan GR</label>
                <textarea name="notes" rows="2" class="w-full border rounded px-3 py-2 text-sm"></textarea>
            </div>

            <div>
                <h3 class="font-semibold text-gray-700 mb-2">Item yang Diterima</h3>

                @php
                    $rowIndex = 0;
                @endphp

                @foreach($selectedPo->items as $item)
                @php
                    $remaining   = $item->quantity - $item->quantity_received;
                    $isClosed    = $remaining <= 0;
                    $dnQtyForItem = $dnQtyMap[$item->id] ?? null;
                    // When coming from SJ: single row with DN qty, auto-checked
                    if ($dnQtyForItem !== null && !$isClosed) {
                        $caseCount = 1;
                        $cases     = [(float) $dnQtyForItem];
                        $fromDn    = true;
                    } else {
                        $fromDn     = false;
                        $qtyPerCase = (float) $item->material->qty_per_case;
                        // Split: if qty_per_case defined, break into N case rows
                        if (!$isClosed && $qtyPerCase > 0) {
                            $caseCount = (int) ceil($remaining / $qtyPerCase);
                            $cases = [];
                            $left  = $remaining;
                            for ($c = 0; $c < $caseCount; $c++) {
                                $cases[] = min($qtyPerCase, $left);
                                $left -= $qtyPerCase;
                            }
                        } else {
                            $caseCount = 1;
                            $cases     = [$remaining];
                        }
                    }
                @endphp

                {{-- Material header row --}}
                <table class="w-full text-sm border-collapse mb-4">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-3 py-2 text-left w-48">Material</th>
                            <th class="px-3 py-2 text-right w-24">Qty Order</th>
                            <th class="px-3 py-2 text-right w-24">Sudah Terima</th>
                            <th class="px-3 py-2 text-right w-24">Sisa</th>
                            <th class="px-3 py-2 text-center w-8" title="Centang/hapus semua">
                                <input type="checkbox" class="accent-green-600" title="Centang/hapus semua" onchange="toggleAllInTable(this)">
                            </th>
                            <th class="px-3 py-2 text-center w-6 text-gray-400">#</th>
                            <th class="px-3 py-2 text-right w-28">Qty Terima</th>
                            <th class="px-3 py-2 text-left">ID Packing / Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($isClosed)
                        {{-- Closed item - single row, no input --}}
                        <tr class="border-b bg-gray-50 opacity-60">
                            <td class="px-3 py-2">
                                <div class="font-mono text-blue-700 text-xs">{{ $item->material->code }}</div>
                                <div class="text-gray-700">{{ $item->material->name }}</div>
                                <span class="text-xs text-gray-400 font-semibold">CLOSED</span>
                            </td>
                            <td class="px-3 py-2 text-right">{{ number_format($item->quantity, 3) }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($item->quantity_received, 3) }}</td>
                            <td class="px-3 py-2 text-right text-gray-400">0.000</td>
                            <td class="px-3 py-2 text-center"><input type="checkbox" disabled class="accent-green-600"></td>
                            <td class="px-3 py-2 text-center text-gray-300">—</td>
                            <td class="px-3 py-2 text-center text-gray-300" colspan="2">—</td>
                        </tr>
                        @else
                        {{-- Active item - one row per case --}}
                        @foreach($cases as $cIdx => $caseQty)
                        <tr class="border-b hover:bg-blue-50">
                            @if($cIdx === 0)
                            {{-- Material info only on first row, spanning all case rows --}}
                            <td class="px-3 py-2 align-top" rowspan="{{ $caseCount }}">
                                <div class="font-mono text-blue-700 text-xs">{{ $item->material->code }}</div>
                                <div class="text-gray-700">{{ $item->material->name }}</div>
                                @if($qtyPerCase > 0)
                                <div class="text-xs text-gray-400 mt-1">{{ number_format($qtyPerCase, 0) }} {{ $item->material->unit_of_measure }}/case &bull; {{ $caseCount }} case</div>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right align-top" rowspan="{{ $caseCount }}">{{ number_format($item->quantity, 3) }}</td>
                            <td class="px-3 py-2 text-right align-top" rowspan="{{ $caseCount }}">{{ number_format($item->quantity_received, 3) }}</td>
                            <td class="px-3 py-2 text-right text-blue-700 font-medium align-top" rowspan="{{ $caseCount }}">{{ number_format($remaining, 3) }}</td>
                            @endif

                            <td class="px-3 py-2 text-center">
                                <input type="hidden" name="items[{{ $rowIndex }}][po_item_id]" value="{{ $item->id }}" class="row-input" {{ $fromDn ? '' : 'disabled' }}>
                                <input type="checkbox" class="accent-green-600 row-check" onchange="toggleRow(this)" {{ $fromDn ? 'checked' : '' }}>
                            </td>
                            <td class="px-3 py-2 text-center text-gray-400 text-xs font-mono">{{ $cIdx + 1 }}</td>
                            <td class="px-3 py-2">
                                <input type="number"
                                    name="items[{{ $rowIndex }}][quantity]"
                                    value="{{ $caseQty }}"
                                    min="0" step="0.001" max="{{ $remaining }}"
                                    class="w-full border rounded px-2 py-1 text-sm text-right row-input"
                                    {{ $fromDn ? '' : 'disabled' }}>
                            </td>
                            <td class="px-3 py-2">
                                <input type="text"
                                    name="items[{{ $rowIndex }}][packing_note]"
                                    placeholder="Contoh: CASE-{{ str_pad($cIdx+1,3,'0',STR_PAD_LEFT) }}"
                                    class="w-full border rounded px-2 py-1 text-sm row-input scanner-input"
                                    maxlength="100"
                                    disabled>
                            </td>
                        </tr>
                        @php $rowIndex++; @endphp
                        @endforeach
                        @endif
                    </tbody>
                </table>
                {{-- Apply initial disabled+dimmed state for unchecked rows --}}
                <script>
                    document.querySelectorAll('.row-check').forEach(cb => {
                        if (!cb.checked) {
                            cb.closest('tr').classList.add('opacity-40','bg-gray-50');
                            cb.closest('tr').querySelectorAll('.row-input').forEach(i => i.disabled = true);
                        }
                    });
                </script>
                @endforeach
            </div>

            @error('items')
            <p class="text-red-600 text-sm">{{ $message }}</p>
            @enderror

            <div class="flex gap-3">
                <button type="submit" class="bg-green-700 text-white px-6 py-2 rounded text-sm hover:bg-green-800">Post Goods Receipt</button>
                <a href="{{ route('mm.goods-receipts.index') }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded text-sm">Batal</a>
            </div>
        </form>

        <script>
            function toggleRow(cb) {
                const row = cb.closest('tr');
                // Disable/enable all named inputs in this row (hidden + text + number)
                row.querySelectorAll('.row-input').forEach(inp => {
                    inp.disabled = !cb.checked;
                });
                row.classList.toggle('opacity-40', !cb.checked);
                row.classList.toggle('bg-gray-50', !cb.checked);
            }

            // Toggle all row checkboxes within the same table
            function toggleAllInTable(masterCb) {
                const table = masterCb.closest('table');
                table.querySelectorAll('.row-check').forEach(cb => {
                    cb.checked = masterCb.checked;
                    toggleRow(cb);
                });
            }

            // Scanner support: Enter on packing_note auto-checks the row and moves focus to next scanner-input
            document.addEventListener('keydown', function(e) {
                if (e.key !== 'Enter') return;
                const target = e.target;
                if (!target.classList.contains('scanner-input')) return;
                e.preventDefault();

                // Auto-check the row checkbox if not already checked
                const row = target.closest('tr');
                if (row) {
                    const cb = row.querySelector('.row-check');
                    if (cb && !cb.checked) {
                        cb.checked = true;
                        toggleRow(cb);
                    }
                }

                // Move focus to next enabled scanner-input
                const allInputs = Array.from(document.querySelectorAll('.scanner-input:not([disabled])'));
                const idx = allInputs.indexOf(target);
                if (idx >= 0 && idx < allInputs.length - 1) {
                    allInputs[idx + 1].focus();
                    allInputs[idx + 1].select();
                }
            });
        </script>
        @else
        <div class="text-center py-8 text-gray-400">Pilih Purchase Order di atas untuk melanjutkan.</div>
        @endif
    </div>
</x-app-layout>
