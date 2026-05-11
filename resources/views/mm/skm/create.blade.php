<x-app-layout>
    <x-slot name="title">Buat SKM - Summary Kanban Material</x-slot>
    <div class="space-y-6">

        {{-- ── Create SKM Form ──────────────────────────────────────────────── --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-700">Buat Summary Kanban Material</h2>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Sistem mendeteksi {{ count($pending) }} item perlu dipesan berdasarkan kalkulasi kanban beredar.
                        Centang item yang ingin dipesan, sesuaikan jumlah kartu.
                    </p>
                </div>
                <a href="{{ route('mm.skm.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm">Batal</a>
            </div>

            <form method="POST" action="{{ route('mm.skm.store') }}" id="skm-form">
                @csrf
                <div class="flex flex-wrap gap-3 items-end mb-6">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal Order *</label>
                        <input type="date" name="order_date" value="{{ user_now()->format('Y-m-d') }}" required
                               class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Est. Pengiriman</label>
                        <input type="date" name="expected_delivery_date"
                               class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Lokasi Gudang Tujuan</label>
                        <select name="storage_location_id" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm min-w-48">
                            <option value="">— Pilih Lokasi —</option>
                            @foreach($storageLocations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->code }} - {{ $loc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-1 min-w-48">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Catatan SKM</label>
                        <input type="text" name="notes" placeholder="Opsional..."
                               class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                    </div>
                </div>

                <div class="overflow-x-auto mb-4">
                <table class="w-full text-sm border-collapse">
                    <thead class="bg-blue-900 text-white text-xs">
                        <tr>
                            <th class="px-3 py-2 w-8">
                                <input type="checkbox" id="check-all" checked class="w-4 h-4">
                            </th>
                            <th class="px-3 py-2 text-left">Material</th>
                            <th class="px-3 py-2 text-left">Vendor</th>
                            <th class="px-3 py-2 text-right">Stok Saat Ini</th>
                            <th class="px-3 py-2 text-right" title="Kanban per hari × (LT+SS+Proses)">Total Kanban Beredar</th>
                            <th class="px-3 py-2 text-right" title="floor(stok ÷ qty/kartu)">Stok (kanban)</th>
                            <th class="px-3 py-2 text-right" title="Sisa order SKM yang belum GR">Outstanding</th>
                            <th class="px-3 py-2 text-right">Qty/Kartu</th>
                            <th class="px-3 py-2 text-right w-24">Jml Kartu *</th>
                            <th class="px-3 py-2 text-right w-24">Total Order</th>
                            <th class="px-3 py-2 text-left w-40">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pending as $idx => $p)
                        <tr class="border-b hover:bg-gray-50" id="row-{{ $idx }}">
                            <td class="px-3 py-2 text-center">
                                <input type="checkbox" name="items[{{ $idx }}][selected]" value="1"
                                       class="row-check w-4 h-4" checked
                                       onchange="toggleRow({{ $idx }}, this.checked)">
                                <input type="hidden" name="items[{{ $idx }}][material_id]" value="{{ $p['material']->id }}">
                            </td>
                            <td class="px-3 py-2">
                                <div class="font-mono text-blue-700 text-xs font-semibold">{{ $p['material']->code }}</div>
                                <div class="text-gray-700">{{ $p['material']->name }}</div>
                                <div class="text-gray-400 text-xs">{{ $p['material']->unit_of_measure }}</div>
                                @if($p['rm_sheet_demand'] > 0)
                                <div class="text-xs text-indigo-500 mt-0.5">Demand: {{ number_format($p['rm_sheet_demand'], 0) }} sht | SPD: {{ number_format($p['rm_sheet_demand'] / max(1, $p['working_days']), 1) }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-600">
                                @if($p['material']->vendor)
                                    {{ $p['material']->vendor->name }}
                                @else
                                    <span class="text-red-500 font-medium">Belum ada vendor</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right text-red-600 font-medium">{{ number_format($p['current_stock'], 0) }}</td>
                            <td class="px-3 py-2 text-right font-semibold text-blue-900">
                                {{ $p['total_kanban'] }}
                                @if($p['kanban_per_day'] > 0)
                                <div class="text-xs font-normal text-gray-400">{{ $p['kanban_per_day'] }}/hari × {{ (3+2+1) }}hari</div>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right text-gray-600">{{ $p['stock_kanban'] }}</td>
                            <td class="px-3 py-2 text-right text-orange-600 font-medium">
                                {{ $p['outstanding_kanban'] }}
                                @if($p['outstanding_qty'] > 0)
                                <div class="text-xs font-normal text-gray-400">{{ number_format($p['outstanding_qty'], 0) }} sht</div>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right font-medium">{{ number_format($p['kanban_qty'], 0) }}</td>
                            <td class="px-3 py-2">
                                <input type="number" name="items[{{ $idx }}][num_cards]"
                                       value="{{ $p['num_cards_suggest'] }}" min="1" required
                                       class="w-full border rounded px-2 py-1 text-sm text-right num-cards-input"
                                       data-kanban="{{ $p['kanban_qty'] }}"
                                       data-row="{{ $idx }}"
                                       oninput="calcTotal({{ $idx }})">
                            </td>
                            <td class="px-3 py-2 text-right font-bold text-blue-900" id="total-{{ $idx }}">
                                {{ number_format($p['order_qty_suggest'], 0) }}
                            </td>
                            <td class="px-3 py-2">
                                <input type="text" name="items[{{ $idx }}][notes]"
                                       class="w-full border rounded px-2 py-1 text-sm text-gray-600"
                                       placeholder="Opsional...">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>

                <div class="flex justify-between items-center">
                    <p class="text-xs text-gray-500">
                        <span id="selected-count">{{ count($pending) }}</span> dari {{ count($pending) }} item dipilih
                    </p>
                    <button type="submit" id="submit-btn"
                            class="bg-blue-700 text-white px-8 py-2 rounded text-sm font-semibold hover:bg-blue-800">
                        Generate SKM
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.getElementById('check-all').addEventListener('change', function () {
        document.querySelectorAll('.row-check').forEach((cb, idx) => {
            cb.checked = this.checked;
            toggleRow(idx, this.checked);
        });
        updateCount();
    });

    function toggleRow(idx, checked) {
        const row = document.getElementById('row-' + idx);
        const inputs = row.querySelectorAll('input:not([type=checkbox])');
        inputs.forEach(i => { i.disabled = !checked; });
        row.classList.toggle('opacity-40', !checked);
        updateCount();
    }

    function calcTotal(idx) {
        const input = document.querySelector('[data-row="' + idx + '"]');
        const kanbanQty = parseFloat(input.dataset.kanban) || 0;
        const numCards  = parseInt(input.value) || 0;
        const total     = kanbanQty * numCards;
        document.getElementById('total-' + idx).textContent = total.toLocaleString('id-ID');
    }

    function updateCount() {
        const total    = document.querySelectorAll('.row-check').length;
        const selected = document.querySelectorAll('.row-check:checked').length;
        document.getElementById('selected-count').textContent = selected;
        document.getElementById('submit-btn').disabled = selected === 0;
    }

    // Filter out unchecked items on submit
    document.getElementById('skm-form').addEventListener('submit', function (e) {
        document.querySelectorAll('.row-check').forEach((cb, idx) => {
            if (!cb.checked) {
                // Remove all inputs in this row from submission
                const row = document.getElementById('row-' + idx);
                row.querySelectorAll('input').forEach(i => i.name = '');
            }
        });
    });
    </script>
</x-app-layout>
