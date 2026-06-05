<x-app-layout>
    <x-slot name="title">Production Order</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-wrap justify-between items-center gap-2 mb-4">
            <h2 class="text-lg font-semibold text-gray-700">Daftar Production Order</h2>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('pp.production-orders.export-pdf', request()->query()) }}" target="_blank" class="inline-flex items-center gap-1.5 bg-red-700 text-white px-4 py-2 rounded text-sm hover:bg-red-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    Print PDF
                </a>
                <a href="{{ route('pp.production-orders.create') }}" class="bg-blue-700 text-white px-4 py-2 rounded text-sm hover:bg-blue-800">+ Buat Production Order</a>
            </div>
        </div>
        <form method="GET" class="flex flex-wrap gap-2 mb-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="No. Order / material..." class="border border-gray-300 rounded-lg px-3 py-2 text-sm flex-1 min-w-48">
            <input type="date" name="date_from" value="{{ request('date_from') }}" title="Dari tgl rencana mulai" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <input type="date" name="date_to"   value="{{ request('date_to') }}"   title="Sampai tgl rencana mulai" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Semua Status</option>
                @foreach(['created','released','in_progress','completed','cancelled'] as $st)
                <option value="{{ $st }}" {{ request('status')==$st?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$st)) }}</option>
                @endforeach
            </select>
            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded text-sm">Filter</button>
            <a href="{{ route('pp.production-orders.index') }}" class="bg-gray-100 text-gray-600 px-4 py-2 rounded text-sm border hover:bg-gray-200">Reset</a>
        </form>

        {{-- Bulk Action Bar --}}
        <form method="POST" action="{{ route('pp.production-orders.bulk-release') }}" id="bulkForm" onsubmit="return confirm('Release semua Production Order yang dipilih?')">
            @csrf
            <div class="flex items-center gap-3 mb-3 bg-blue-50 border border-blue-200 rounded px-3 py-2" id="bulkBar" style="display:none">
                <span class="text-sm text-blue-700 font-medium" id="bulkCount">0 dipilih</span>
                <button type="submit" class="bg-blue-700 text-white px-4 py-1.5 rounded text-sm hover:bg-blue-800">Release Semua yang Dipilih</button>
                <button type="button" onclick="clearSelection()" class="text-sm text-gray-500 hover:text-gray-700">Batal Pilih</button>
            </div>

        <div class="mobile-cards overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead class="bg-blue-900 text-white">
                <tr>
                    <th class="px-3 py-2 text-center w-8">
                        <input type="checkbox" id="checkAll" class="cursor-pointer" title="Pilih semua Created">
                    </th>
                    <th class="px-4 py-2 text-left">No. Order</th>
                    <th class="px-4 py-2 text-left">Material</th>
                    <th class="px-4 py-2 text-right">Qty Plan</th>
                    <th class="px-4 py-2 text-left">Tgl Mulai</th>
                    <th class="px-4 py-2 text-left">Tgl Selesai</th>
                    <th class="px-4 py-2 text-center">Status</th>
                    <th class="px-4 py-2 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $prd)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-3 py-2 text-center">
                        @if($prd->status === 'created')
                        <input type="checkbox" name="ids[]" value="{{ $prd->id }}" class="row-check cursor-pointer">
                        @endif
                    </td>
                    <td class="px-4 py-2 font-mono text-blue-700 font-medium" data-label="No. Order">{{ $prd->order_number }}</td>
                    <td class="px-4 py-2" data-label="Material">
                        <div class="font-mono text-xs text-gray-500">{{ $prd->material->code }}</div>
                        <div>{{ $prd->material->name }}</div>
                    </td>
                    <td class="px-4 py-2 text-right" data-label="Qty Plan">{{ fmt_qty($prd->quantity_planned) }}</td>
                    <td class="px-4 py-2" data-label="Tgl Mulai">{{ $prd->planned_start_date?->format('d/m/Y') ?? '-' }}</td>
                    <td class="px-4 py-2" data-label="Tgl Selesai">{{ $prd->planned_end_date?->format('d/m/Y') ?? '-' }}</td>
                    <td class="px-4 py-2 text-center" data-label="Status">
                        <span class="px-2 py-0.5 rounded text-xs
                            {{ $prd->status==='created'?'bg-gray-100 text-gray-600':''}}
                            {{ $prd->status==='released'?'bg-blue-100 text-blue-700':'' }}
                            {{ $prd->status==='in_progress'?'bg-yellow-100 text-yellow-700':'' }}
                            {{ $prd->status==='completed'?'bg-green-100 text-green-700':'' }}
                            {{ $prd->status==='cancelled'?'bg-red-100 text-red-700':'' }}
                        ">{{ ucfirst(str_replace('_',' ',$prd->status)) }}</span>
                    </td>
                    <td class="px-4 py-2 text-center">
                        <div class="flex flex-wrap gap-1 justify-center items-center">
                            <a href="{{ route('pp.production-orders.show', $prd) }}" class="text-blue-600 hover:underline text-xs whitespace-nowrap">Detail</a>
                            @if($prd->status === 'created')
                            <span class="text-gray-300 text-xs">|</span>
                            <a href="{{ route('pp.production-orders.edit', $prd) }}" class="text-yellow-600 hover:underline text-xs whitespace-nowrap">Edit</a>
                            <span class="text-gray-300 text-xs">|</span>
                            <form method="POST" action="{{ route('pp.production-orders.destroy', $prd) }}" class="inline" onsubmit="return confirm('Hapus Production Order {{ $prd->order_number }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline text-xs whitespace-nowrap">Hapus</button>
                            </form>
                            @endif
                            <span class="text-gray-300 text-xs">|</span>
                            <a href="{{ route('pp.production-orders.print', $prd) }}" target="_blank" class="text-emerald-600 hover:underline text-xs whitespace-nowrap">Print</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-4 text-center text-gray-400">Belum ada Production Order.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        </form>

        <div class="mt-4">{{ $orders->links() }}</div>
    </div>

    <script>
        const checkAll  = document.getElementById('checkAll');
        const bulkBar   = document.getElementById('bulkBar');
        const bulkCount = document.getElementById('bulkCount');

        function updateBar() {
            const checked = document.querySelectorAll('.row-check:checked');
            if (checked.length > 0) {
                bulkBar.style.removeProperty('display');
                bulkCount.textContent = checked.length + ' dipilih';
            } else {
                bulkBar.style.display = 'none';
                if (checkAll) checkAll.checked = false;
            }
        }

        function clearSelection() {
            document.querySelectorAll('.row-check:checked').forEach(c => c.checked = false);
            if (checkAll) checkAll.checked = false;
            updateBar();
        }

        if (checkAll) {
            checkAll.addEventListener('change', function () {
                document.querySelectorAll('.row-check').forEach(c => c.checked = this.checked);
                updateBar();
            });
        }

        document.querySelectorAll('.row-check').forEach(c => c.addEventListener('change', updateBar));
    </script>
</x-app-layout>
