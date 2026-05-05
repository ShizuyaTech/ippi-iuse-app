<x-app-layout>
    <x-slot name="title">Summary Kanban Material (SKM)</x-slot>
    <div class="space-y-6">

        {{-- Stats Cards --}}
        <div class="flex gap-4">
            <div class="flex-1 bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-blue-700">{{ $stats['total'] }}</div>
                <div class="text-xs text-gray-500 mt-1">Total SKM</div>
            </div>
            <div class="flex-1 bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-gray-600">{{ $stats['draft'] }}</div>
                <div class="text-xs text-gray-500 mt-1">Draft</div>
            </div>
            <div class="flex-1 bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $stats['sent'] }}</div>
                <div class="text-xs text-gray-500 mt-1">Dikirim ke Vendor</div>
            </div>
            <div class="flex-1 bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-yellow-600">{{ $stats['partial_received'] }}</div>
                <div class="text-xs text-gray-500 mt-1">Diterima Sebagian</div>
            </div>
            <div class="flex-1 bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-green-600">{{ $stats['completed'] }}</div>
                <div class="text-xs text-gray-500 mt-1">Selesai (Semua Diterima)</div>
            </div>
            <div class="flex-1 bg-white rounded-lg shadow p-4 text-center border-2 {{ $stats['pending'] > 0 ? 'border-red-400' : 'border-gray-200' }}">
                <div class="text-2xl font-bold {{ $stats['pending'] > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ $stats['pending'] }}</div>
                <div class="text-xs {{ $stats['pending'] > 0 ? 'text-red-500 font-semibold' : 'text-gray-500' }} mt-1">Item Perlu Order</div>
            </div>
        </div>

        {{-- Pending Alert + Create Button --}}
        @if($stats['pending'] > 0)
        <div class="bg-red-50 border border-red-300 rounded-lg p-4 flex items-center justify-between">
            <div>
                <div class="font-semibold text-red-700">{{ $stats['pending'] }} material SKM stoknya di bawah minimum!</div>
                <div class="text-sm text-red-600 mt-0.5">Buat dokumen SKM sekarang untuk memesan material yang dibutuhkan.</div>
            </div>
            <a href="{{ route('mm.skm.create') }}"
               class="bg-red-600 text-white px-5 py-2 rounded text-sm font-semibold hover:bg-red-700 whitespace-nowrap">
                Buat SKM Sekarang
            </a>
        </div>
        @else
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 flex items-center justify-between">
            <div class="text-green-700 text-sm font-medium">Semua stok material SKM mencukupi. Tidak ada item yang perlu dipesan.</div>
            <a href="{{ route('mm.skm.create') }}"
               class="bg-blue-700 text-white px-5 py-2 rounded text-sm hover:bg-blue-800 whitespace-nowrap">
                Buat SKM Manual
            </a>
        </div>
        @endif

        {{-- SKM Orders Table --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-700 mb-3">Riwayat SKM</h3>
            <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-blue-900 text-white">
                    <tr>
                        <th class="px-4 py-2 text-left">Nomor SKM</th>
                        <th class="px-4 py-2 text-left">Tanggal Order</th>
                        <th class="px-4 py-2 text-right">Jml Item</th>
                        <th class="px-4 py-2 text-center">Status</th>
                        <th class="px-4 py-2 text-left">Dibuat oleh</th>
                        <th class="px-4 py-2 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-2 font-mono font-semibold text-blue-700">{{ $order->skm_number }}</td>
                        <td class="px-4 py-2">{{ $order->order_date->format('d M Y') }}</td>
                        <td class="px-4 py-2 text-right font-medium">{{ $order->items_count }}</td>
                        <td class="px-4 py-2 text-center">
                            <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $order->status_color }}">
                                {{ $order->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-gray-600">{{ $order->createdBy->name ?? '-' }}</td>
                        <td class="px-4 py-2 text-center">
                            <div class="flex justify-center gap-3">
                                <a href="{{ route('mm.skm.show', $order) }}" class="text-blue-600 hover:underline text-sm">Detail</a>
                                @if($order->status === 'draft')
                                <form method="POST" action="{{ route('mm.skm.destroy', $order) }}"
                                      onsubmit="return confirm('Hapus SKM {{ $order->skm_number }}?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:text-red-700 text-sm">Hapus</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-4 text-center text-gray-400">Belum ada dokumen SKM.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
            <div class="mt-4">{{ $orders->links() }}</div>
        </div>

        {{-- Pending Items Preview --}}
        @if(!empty($pending))
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-700 mb-1">Preview Item Perlu Dipesan</h3>
            <p class="text-xs text-gray-400 mb-3">Kalkulasi kanban beredar: LT 3 hari + SS 2 hari + Proses 1 hari = 6 hari × kanban/hari. Klik "Buat SKM Sekarang" untuk memprosesnya.</p>
            <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-orange-100 text-orange-800 text-xs">
                    <tr>
                        <th class="px-3 py-2 text-left">Material</th>
                        <th class="px-3 py-2 text-left">Vendor</th>
                        <th class="px-3 py-2 text-right">Stok Saat Ini</th>
                        <th class="px-3 py-2 text-right">Total Kanban</th>
                        <th class="px-3 py-2 text-right">Stok (kanban)</th>
                        <th class="px-3 py-2 text-right">Outstanding</th>
                        <th class="px-3 py-2 text-right">Qty/Kartu</th>
                        <th class="px-3 py-2 text-right">Saran Kartu</th>
                        <th class="px-3 py-2 text-right">Total Order</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pending as $p)
                    <tr class="border-b hover:bg-orange-50">
                        <td class="px-3 py-2">
                            <div class="font-mono text-blue-700 text-xs font-semibold">{{ $p['material']->code }}</div>
                            <div class="text-gray-700">{{ $p['material']->name }}</div>
                        </td>
                        <td class="px-3 py-2 text-gray-600 text-xs">{{ $p['material']->vendor->name ?? '-' }}</td>
                        <td class="px-3 py-2 text-right text-red-600 font-medium">{{ number_format($p['current_stock'], 0) }}</td>
                        <td class="px-3 py-2 text-right font-semibold text-blue-900">{{ $p['total_kanban'] }}</td>
                        <td class="px-3 py-2 text-right text-gray-600">{{ $p['stock_kanban'] }}</td>
                        <td class="px-3 py-2 text-right text-orange-600">{{ $p['outstanding_kanban'] }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($p['kanban_qty'], 0) }}</td>
                        <td class="px-3 py-2 text-right font-semibold text-blue-700">{{ $p['num_cards_suggest'] }}</td>
                        <td class="px-3 py-2 text-right font-bold text-blue-900">{{ number_format($p['order_qty_suggest'], 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
        @endif

        {{-- Demand FP Bulanan ────────────────────────────────────────────── --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <h3 class="font-semibold text-gray-700">Data Demand FP Bulan Berjalan</h3>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Demand digunakan untuk menghitung total kanban beredar. Import sekali per bulan — data akan tetap aktif sampai diganti import baru.
                        @if($demands->isNotEmpty())
                        <span class="text-blue-600 font-medium">Periode aktif: {{ $demands->first()->period ?? '-' }} ({{ $demands->count() }} material FP/WIP)</span>
                        @endif
                    </p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('mm.skm.demands.template') }}"
                       class="bg-green-600 text-white px-3 py-1.5 rounded text-xs font-medium hover:bg-green-700">
                        Download Template
                    </a>
                    @if($demands->isNotEmpty())
                    <form method="POST" action="{{ route('mm.skm.demands.clear') }}"
                          onsubmit="return confirm('Hapus semua demand aktif?')">
                        @csrf @method('DELETE')
                        <button class="bg-red-100 text-red-700 px-3 py-1.5 rounded text-xs font-medium hover:bg-red-200">
                            Hapus Semua
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            {{-- Import form --}}
            <form method="POST" action="{{ route('mm.skm.demands.import') }}" enctype="multipart/form-data"
                  class="flex gap-3 items-end mb-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Upload File Excel (Demand Bulan Ini)</label>
                    <input type="file" name="file" accept=".xlsx,.xls" required
                           class="border rounded px-3 py-1.5 text-sm">
                </div>
                <button class="bg-blue-700 text-white px-4 py-1.5 rounded text-sm font-medium hover:bg-blue-800">
                    Import &amp; Ganti Demand
                </button>
            </form>

            @if($demands->isNotEmpty())
            <div class="overflow-x-auto">
            <table class="w-full text-xs border-collapse">
                <thead class="bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-3 py-1.5 text-left">Material FP/WIP</th>
                        <th class="px-3 py-1.5 text-right">Demand (pcs)</th>
                        <th class="px-3 py-1.5 text-right">Hari Kerja</th>
                        <th class="px-3 py-1.5 text-left">Periode</th>
                        <th class="px-3 py-1.5 text-left">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($demands as $d)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-3 py-1.5">
                            <span class="font-mono text-blue-700 font-semibold">{{ $d->material->code ?? '-' }}</span>
                            <span class="text-gray-600 ml-1">{{ $d->material->name ?? '-' }}</span>
                        </td>
                        <td class="px-3 py-1.5 text-right font-semibold">{{ number_format($d->demand_qty, 0) }}</td>
                        <td class="px-3 py-1.5 text-right">{{ $d->working_days }}</td>
                        <td class="px-3 py-1.5">{{ $d->period ?? '-' }}</td>
                        <td class="px-3 py-1.5 text-gray-500">{{ $d->notes ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            @else
            <p class="text-xs text-amber-600 bg-amber-50 rounded px-3 py-2">
                Belum ada demand aktif. Kanban dihitung berdasarkan min_stock sebagai fallback sampai demand diimport.
            </p>
            @endif
        </div>

    </div>
</x-app-layout>
