<x-vendor-layout>
    <x-slot name="title">Buat Surat Jalan</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('vendor.delivery-notes.index') }}" class="text-teal-600 hover:underline text-sm">← Kembali</a>
            <h2 class="text-lg font-semibold text-gray-700">Buat Surat Jalan</h2>
        </div>

        @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-2 rounded text-sm">
                <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        {{-- Step 1: Pilih PO --}}
        @php
            $posJson = $pos->map(fn($p) => ['id' => $p->id, 'label' => $p->po_number . ' (est. ' . ($p->expected_delivery_date?->format('d/m/Y') ?? '-') . ')']);
            $selectedPoLabel = $selectedPo ? ($selectedPo->po_number . ' (est. ' . ($selectedPo->expected_delivery_date?->format('d/m/Y') ?? '-') . ')') : '';
        @endphp
        <form method="GET" id="po-select-form" class="mb-6 p-4 bg-gray-50 rounded border">
            <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Purchase Order (hanya yang disetujui)</label>
            <div class="relative">
                <input type="text" id="po-search" value="{{ $selectedPoLabel }}"
                    placeholder="Ketik nomor PO..." autocomplete="off"
                    class="w-full border rounded px-3 py-2 text-sm"
                    oninput="poSearch(this)" onkeydown="poKeydown(event)">
                <input type="hidden" name="po_id" id="po-id-hidden" value="{{ request('po_id') }}">
                <div id="po-suggestions"
                    class="absolute z-50 w-full bg-white border border-gray-200 rounded shadow-lg max-h-48 overflow-y-auto hidden"></div>
            </div>
            @if($pos->isEmpty())
                <p class="text-xs text-orange-600 mt-2">Tidak ada PO dengan status Approved/Partial. Hubungi tim Purchasing IPPI.</p>
            @endif
        </form>

        <script>
            const allPos = @json($posJson);
            function poSearch(input) {
                input._activeIdx = -1;
                const q = input.value.trim().toLowerCase();
                const box = document.getElementById('po-suggestions');
                document.getElementById('po-id-hidden').value = '';
                if (!q) { box.classList.add('hidden'); return; }
                const matches = allPos.filter(p => p.label.toLowerCase().includes(q)).slice(0, 20);
                if (!matches.length) { box.classList.add('hidden'); return; }
                box.innerHTML = matches.map(p =>
                    `<div class="px-3 py-2 text-sm cursor-pointer hover:bg-teal-50 border-b border-gray-100" data-id="${p.id}" data-label="${p.label}">
                        <span class="font-mono text-teal-700 font-semibold">${p.label}</span>
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
                if (e.key === 'ArrowDown') { e.preventDefault(); inp._activeIdx = Math.min((inp._activeIdx??-1)+1,items.length-1); items.forEach((el,i)=>el.style.background=i===inp._activeIdx?'#F0FDFA':''); }
                else if (e.key === 'ArrowUp') { e.preventDefault(); inp._activeIdx = Math.max((inp._activeIdx??0)-1,0); items.forEach((el,i)=>el.style.background=i===inp._activeIdx?'#F0FDFA':''); }
                else if (e.key === 'Enter') { e.preventDefault(); const el=items[inp._activeIdx]; if(el){inp.value=el.dataset.label;document.getElementById('po-id-hidden').value=el.dataset.id;box.classList.add('hidden');document.getElementById('po-select-form').submit();} }
                else if (e.key === 'Escape') { box.classList.add('hidden'); }
            }
            document.addEventListener('click', e => { if(!e.target.closest('#po-search')&&!e.target.closest('#po-suggestions')) document.getElementById('po-suggestions').classList.add('hidden'); });
        </script>

        @if($selectedPo)
        <form method="POST" action="{{ route('vendor.delivery-notes.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="purchase_order_id" value="{{ $selectedPo->id }}">

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estimasi Tanggal Pengiriman *</label>
                    <input type="date" name="estimated_delivery_date"
                        value="{{ old('estimated_delivery_date', date('Y-m-d', strtotime('+1 day'))) }}"
                        min="{{ date('Y-m-d') }}"
                        class="w-full border rounded px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">No. Kendaraan</label>
                    <input type="text" name="vehicle_number" value="{{ old('vehicle_number') }}"
                        placeholder="Contoh: B 1234 XYZ"
                        class="w-full border rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Driver</label>
                    <input type="text" name="driver_name" value="{{ old('driver_name') }}"
                        placeholder="Nama pengemudi"
                        class="w-full border rounded px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                <textarea name="notes" rows="2" class="w-full border rounded px-3 py-2 text-sm">{{ old('notes') }}</textarea>
            </div>

            <h3 class="font-semibold text-gray-700 mb-2">Item yang Akan Dikirim</h3>
            <p class="text-xs text-gray-500 mb-3">Isi qty yang akan dikirim. Boleh sebagian (partial shipment).</p>

            @foreach($selectedPo->items as $idx => $item)
            @php
                $remaining = $item->quantity - ($item->quantity_received ?? 0);
            @endphp
            <div class="border rounded p-3 {{ $remaining <= 0 ? 'opacity-50 bg-gray-50' : '' }}">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <span class="font-mono text-xs text-teal-700">{{ $item->material->code }}</span>
                        <span class="ml-2 text-sm font-medium">{{ $item->material->name }}</span>
                    </div>
                    <div class="text-xs text-gray-500 text-right">
                        <div>Qty PO: <strong>{{ number_format($item->quantity, 3) }}</strong></div>
                        <div>Sudah diterima: {{ number_format($item->quantity_received ?? 0, 3) }}</div>
                        <div>Sisa: <strong class="text-teal-700">{{ number_format($remaining, 3) }}</strong> {{ $item->material->unit_of_measure }}</div>
                    </div>
                </div>
                <input type="hidden" name="items[{{ $idx }}][po_item_id]" value="{{ $item->id }}">
                <div class="flex gap-3 items-center">
                    <label class="text-sm text-gray-600 w-32">Qty Dikirim</label>
                    <input type="number" name="items[{{ $idx }}][quantity]"
                        step="0.001" min="0" max="{{ $remaining }}"
                        value="{{ old("items.{$idx}.quantity", max(0, $remaining)) }}"
                        class="border rounded px-3 py-1.5 text-sm w-36 {{ $remaining <= 0 ? 'bg-gray-100' : '' }}"
                        {{ $remaining <= 0 ? 'disabled' : '' }}>
                    <span class="text-sm text-gray-500">{{ $item->material->unit_of_measure }}</span>
                    @if($remaining <= 0)
                        <span class="text-xs text-green-600 italic">Sudah selesai</span>
                    @endif
                </div>
            </div>
            @endforeach

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-blue-700 text-white px-5 py-2 rounded hover:bg-blue-800 text-sm">Kirim Surat Jalan</button>
                <a href="{{ route('vendor.delivery-notes.index') }}" class="bg-gray-100 text-gray-600 px-5 py-2 rounded border hover:bg-gray-200 text-sm">Batal</a>
            </div>
        </form>
        @endif
    </div>
</x-vendor-layout>
