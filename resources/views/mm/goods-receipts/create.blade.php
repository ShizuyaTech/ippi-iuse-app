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
        <form method="GET" id="po-select-form" class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
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
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
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
        @php
            $hasOpenItems = $selectedPo->items->contains(fn($i) => ($i->quantity - $i->quantity_received) > 0);
        @endphp
        @if(!$hasOpenItems)
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-4 rounded text-sm text-center">
            <strong>Semua item pada PO {{ $selectedPo->po_number }} sudah selesai diterima.</strong>
            Tidak ada item yang tersisa untuk dibuat GR.
        </div>
        @else
        <form method="POST" action="{{ route('mm.goods-receipts.store') }}" class="space-y-4"
              onkeydown="if(event.key==='Enter' && event.target.tagName !== 'TEXTAREA' && event.target.type !== 'submit'){ event.preventDefault(); }">
            @csrf
            <input type="hidden" name="purchase_order_id" value="{{ $selectedPo->id }}">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Penerimaan *</label>
                    <input type="date" name="receipt_date" value="{{ user_now()->format('Y-m-d') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Storage Location</label>
                    <input type="hidden" name="storage_location_id" value="{{ $selectedPo->storage_location_id }}">
                    <div class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-50 text-gray-700">
                        {{ $selectedPo->storageLocation?->code }} - {{ $selectedPo->storageLocation?->name }}
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan GR</label>
                <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
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
                @endphp
                @continue($isClosed)
                @php
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

                {{-- Material header (outside table — not affected by mobile-cards conversion) --}}
                <div class="item-header bg-blue-50 border border-blue-200 rounded-t-lg px-3 py-2 flex flex-wrap justify-between items-center gap-2">
                    <div>
                        <span class="font-mono text-blue-700 text-xs font-bold">{{ $item->material->code }}</span>
                        <span class="text-gray-800 text-sm ml-2 font-medium">{{ $item->material->name }}</span>
                        @if(!$fromDn && ($qtyPerCase ?? 0) > 0)
                        <span class="text-xs text-gray-400 ml-2">{{ number_format($qtyPerCase, 0) }} {{ $item->material->unit_of_measure }}/case &bull; {{ $caseCount }} case</span>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500">
                        <span>Order: <b class="text-gray-700">{{ number_format($item->quantity, 3) }}</b> {{ $item->material->unit_of_measure }}</span>
                        <span>Diterima: <b class="text-gray-700">{{ number_format($item->quantity_received, 3) }}</b></span>
                        <span class="text-blue-700 font-semibold">Sisa: {{ number_format($remaining, 3) }}</span>
                        <label class="flex items-center gap-1 cursor-pointer ml-1 text-green-700 font-semibold border border-green-300 rounded px-2 py-0.5 bg-white">
                            <input type="checkbox" class="accent-green-600" onchange="toggleAllInItem(this)">
                            Pilih Semua
                        </label>
                    </div>
                </div>

                <div class="overflow-x-auto border border-t-0 border-blue-200 rounded-b-lg mb-5">
                <table class="w-full text-sm border-collapse">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wide">
                        <tr>
                            <th class="px-3 py-2 text-center w-12">Cek</th>
                            <th class="px-3 py-2 text-center w-8">#</th>
                            <th class="px-3 py-2 text-right w-28">Qty Terima</th>
                            <th class="px-3 py-2 text-left">ID Packing / Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cases as $cIdx => $caseQty)
                        <tr class="border-b hover:bg-blue-50 {{ $fromDn ? '' : 'opacity-40 bg-gray-50' }}">
                            <td class="px-3 py-3 text-center" data-label="Cek">
                                <input type="hidden" name="items[{{ $rowIndex }}][po_item_id]" value="{{ $item->id }}" class="row-input" {{ $fromDn ? '' : 'disabled' }}>
                                <input type="checkbox" class="accent-green-600 row-check w-5 h-5 cursor-pointer" onchange="toggleRow(this)" {{ $fromDn ? 'checked' : '' }}>
                            </td>
                            <td class="px-3 py-3 text-center text-gray-400 text-xs font-mono font-bold" data-label="Case #">{{ $cIdx + 1 }}</td>
                            <td class="px-3 py-3" data-label="Qty Terima">
                                <input type="number"
                                    name="items[{{ $rowIndex }}][quantity]"
                                    value="{{ $caseQty }}"
                                    min="0" step="0.001" max="{{ $remaining }}"
                                    class="w-full border rounded px-2 py-1.5 text-sm text-right row-input"
                                    {{ $fromDn ? '' : 'disabled' }}>
                            </td>
                            <td class="px-3 py-3" data-label="ID Packing">
                                <input type="text"
                                    name="items[{{ $rowIndex }}][packing_note]"
                                    placeholder="Scan / ketik ID Packing..."
                                    class="w-full border rounded px-2 py-1.5 text-sm row-input scanner-input"
                                    maxlength="100"
                                    disabled>
                            </td>
                        </tr>
                        @php $rowIndex++; @endphp
                        @endforeach
                    </tbody>
                </table>
                </div>
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

            // Toggle all row checkboxes within the same item (header div + sibling table wrapper)
            function toggleAllInItem(masterCb) {
                const header = masterCb.closest('.item-header');
                const wrapper = header ? header.nextElementSibling : null;
                const table = wrapper ? wrapper.querySelector('table') : null;
                if (!table) return;
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
        @endif {{-- hasOpenItems --}}
        @else
        <div class="text-center py-8 text-gray-400">Pilih Purchase Order di atas untuk melanjutkan.</div>
        @endif
    </div>
</x-app-layout>
