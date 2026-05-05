<x-vendor-layout>
    <x-slot name="title">Buat Goods Receipt</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center gap-3 mb-4">
            <a href="{{ route('vendor.goods-receipts.index') }}" class="text-teal-600 hover:underline text-sm">← Kembali</a>
            <h2 class="text-lg font-semibold text-gray-700">Buat Goods Receipt</h2>
        </div>

        @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-2 rounded text-sm">
                <ul class="list-disc pl-4">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        {{-- Step 1: Pilih PO --}}
        @php
            $posJson = $pos->map(fn($p) => ['id' => $p->id, 'label' => $p->po_number]);
            $selectedPoLabel = $selectedPo ? $selectedPo->po_number : '';
        @endphp
        <form method="GET" id="po-select-form" class="mb-6 p-4 bg-gray-50 rounded border">
            <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Purchase Order (status: Approved / Partial)</label>
            <div class="relative flex gap-2">
                <div class="flex-1 relative">
                    <input type="text" id="po-search" value="{{ $selectedPoLabel }}"
                        placeholder="Ketik nomor PO..." autocomplete="off"
                        class="w-full border rounded px-3 py-2 text-sm"
                        oninput="poSearch(this)" onkeydown="poKeydown(event)">
                    <input type="hidden" name="po_id" id="po-id-hidden" value="{{ request('po_id') }}">
                    <div id="po-suggestions"
                        class="absolute z-50 w-full bg-white border border-gray-200 rounded shadow-lg max-h-48 overflow-y-auto hidden"></div>
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
                const matches = allPos.filter(p => p.label.toLowerCase().includes(q)).slice(0, 20);
                if (!matches.length) { box.classList.add('hidden'); return; }
                box.innerHTML = matches.map(p =>
                    `<div class="px-3 py-2 text-sm cursor-pointer hover:bg-teal-50 border-b border-gray-100"
                          data-id="${p.id}" data-label="${p.label}">
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
                if (e.key === 'ArrowDown') { e.preventDefault(); inp._activeIdx = Math.min((inp._activeIdx ?? -1)+1, items.length-1); items.forEach((el,i) => el.style.background = i===inp._activeIdx?'#F0FDFA':''); }
                else if (e.key === 'ArrowUp') { e.preventDefault(); inp._activeIdx = Math.max((inp._activeIdx??0)-1,0); items.forEach((el,i) => el.style.background = i===inp._activeIdx?'#F0FDFA':''); }
                else if (e.key === 'Enter') { e.preventDefault(); const el = items[inp._activeIdx]; if(el){inp.value=el.dataset.label;document.getElementById('po-id-hidden').value=el.dataset.id;box.classList.add('hidden');document.getElementById('po-select-form').submit();} }
                else if (e.key === 'Escape') { box.classList.add('hidden'); }
            }
            document.addEventListener('click', e => { if(!e.target.closest('#po-search') && !e.target.closest('#po-suggestions')) document.getElementById('po-suggestions').classList.add('hidden'); });
        </script>

        @if($selectedPo)
        {{-- Step 2: GR Form --}}
        <form method="POST" action="{{ route('vendor.goods-receipts.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="purchase_order_id" value="{{ $selectedPo->id }}">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Penerimaan *</label>
                    <input type="date" name="receipt_date" value="{{ date('Y-m-d') }}" class="w-full border rounded px-3 py-2 text-sm" required>
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
                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                <textarea name="notes" rows="2" class="w-full border rounded px-3 py-2 text-sm"></textarea>
            </div>

            <h3 class="font-semibold text-gray-700 mb-2">Item PO</h3>
            @foreach($selectedPo->items as $idx => $item)
            @php $remaining = $item->quantity - ($item->quantity_received ?? 0); @endphp
            <div class="border rounded p-3 {{ $remaining <= 0 ? 'opacity-50' : '' }}">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <span class="font-mono text-xs text-teal-700">{{ $item->material->code }}</span>
                        <span class="ml-2 text-sm font-medium">{{ $item->material->name }}</span>
                    </div>
                    <div class="text-xs text-gray-500">
                        Sisa: <strong class="text-teal-700">{{ number_format($remaining, 3) }}</strong> {{ $item->material->unit_of_measure }}
                    </div>
                </div>
                <input type="hidden" name="items[{{ $idx }}][po_item_id]" value="{{ $item->id }}">
                <div class="flex gap-3 items-center">
                    <label class="text-sm text-gray-600 w-32">Qty Diterima</label>
                    <input type="number" name="items[{{ $idx }}][quantity_received]"
                        step="0.001" min="0" max="{{ $remaining }}"
                        value="{{ $remaining > 0 ? $remaining : 0 }}"
                        class="border rounded px-3 py-1.5 text-sm w-36 {{ $remaining <= 0 ? 'bg-gray-100' : '' }}"
                        {{ $remaining <= 0 ? 'disabled' : '' }}>
                    <span class="text-sm text-gray-500">{{ $item->material->unit_of_measure }}</span>
                    @if($remaining <= 0)
                        <span class="text-xs text-gray-400 italic">Sudah selesai</span>
                    @endif
                </div>
            </div>
            @endforeach

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-blue-700 text-white px-5 py-2 rounded hover:bg-blue-800 text-sm">Simpan Goods Receipt</button>
                <a href="{{ route('vendor.goods-receipts.index') }}" class="bg-gray-100 text-gray-600 px-5 py-2 rounded border hover:bg-gray-200 text-sm">Batal</a>
            </div>
        </form>
        @endif
    </div>
</x-vendor-layout>
