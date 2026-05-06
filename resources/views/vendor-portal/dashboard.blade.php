<x-vendor-layout>
    <x-slot name="title">Dashboard Vendor</x-slot>

    @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded text-sm">{{ session('success') }}</div>
    @endif

    {{-- Stats row --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">PO Aktif</div>
            <div class="text-3xl font-bold text-teal-700">{{ $stats['po_open'] }}</div>
            <div class="text-xs text-gray-400 mt-1">Draft / Disetujui / Sebagian</div>
        </div>
        <div class="bg-white rounded-lg shadow p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Surat Jalan Bulan Ini</div>
            <div class="text-3xl font-bold text-blue-700">{{ $stats['sj_this_month'] }}</div>
            <div class="text-xs text-gray-400 mt-1">{{ now()->translatedFormat('F Y') }}</div>
        </div>
        @if(!$isCoilCenter)
        <div class="bg-white rounded-lg shadow p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Production Order Aktif</div>
            <div class="text-3xl font-bold text-indigo-700">{{ $stats['vpo_active'] }}</div>
            <div class="text-xs text-gray-400 mt-1">Draft / Released / In Progress</div>
        </div>
        <div class="bg-white rounded-lg shadow p-5">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Kiriman Bahan Pending</div>
            <div class="text-3xl font-bold text-orange-600">{{ $stats['kiriman_pending'] }}</div>
            <div class="text-xs text-gray-400 mt-1">Belum dikonfirmasi</div>
        </div>
        @else
        {{-- Coil center: ganti 2 card stats dengan informasi lain --}}
        <div class="bg-white rounded-lg shadow p-5 col-span-2">
            <div class="text-xs text-gray-500 uppercase tracking-wide mb-1">Tipe Vendor</div>
            <div class="text-lg font-bold text-blue-700 mt-2">Coil Center</div>
            <div class="text-xs text-gray-400 mt-1">Supplier Bahan Baku</div>
        </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        {{-- Recent PO --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-base font-semibold text-gray-700 mb-3">5 Purchase Order Terbaru</h2>
            <table class="w-full text-sm border-collapse">
                <thead class="bg-teal-800 text-white">
                    <tr>
                        <th class="px-3 py-2 text-left">No. PO</th>
                        <th class="px-3 py-2 text-left">Tgl Order</th>
                        <th class="px-3 py-2 text-right">Item</th>
                        <th class="px-3 py-2 text-center">Status</th>
                        <th class="px-3 py-2 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPos as $po)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-3 py-2 font-mono text-teal-700 font-medium text-xs">{{ $po->po_number }}</td>
                        <td class="px-3 py-2 text-xs">{{ $po->order_date?->format('d/m/Y') ?? '-' }}</td>
                        <td class="px-3 py-2 text-right text-xs">{{ $po->items->count() }}</td>
                        <td class="px-3 py-2 text-center">
                            <span class="px-2 py-0.5 rounded text-xs
                                {{ $po->status==='draft'?'bg-gray-100 text-gray-600':'' }}
                                {{ $po->status==='approved'?'bg-blue-100 text-blue-700':'' }}
                                {{ $po->status==='partially_received'?'bg-yellow-100 text-yellow-700':'' }}
                                {{ $po->status==='completed'?'bg-green-100 text-green-700':'' }}
                                {{ $po->status==='cancelled'?'bg-red-100 text-red-700':'' }}
                            ">{{ ucfirst(str_replace('_',' ',$po->status)) }}</span>
                        </td>
                        <td class="px-3 py-2 text-center">
                            <a href="{{ route('vendor.purchase-orders.show', $po) }}" class="text-teal-600 hover:underline text-xs">Detail</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-3 py-4 text-center text-gray-400 text-xs">Belum ada Purchase Order.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Stock Summary --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-base font-semibold text-gray-700">
                    {{ $isCoilCenter ? 'Material yang Disuplai' : 'Stok Material' }}
                </h2>
                @if(!$isCoilCenter)
                <a href="{{ route('vendor.stocks.index') }}" class="text-teal-600 hover:underline text-xs">Lihat Semua →</a>
                @endif
            </div>
            @if($stockSummary->isEmpty())
                <p class="text-sm text-gray-400 py-4 text-center">Tidak ada material dengan stok aktif.</p>
            @else
            <table class="w-full text-sm border-collapse">
                <thead class="bg-teal-800 text-white">
                    <tr>
                        <th class="px-3 py-2 text-left">Material</th>
                        <th class="px-3 py-2 text-center">Tipe</th>
                        <th class="px-3 py-2 text-right">Stok</th>
                        <th class="px-3 py-2 text-left">Satuan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stockSummary->take(8) as $row)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-3 py-1.5">
                            <div class="font-mono text-xs text-gray-500">{{ $row['code'] }}</div>
                            <div class="text-xs">{{ $row['name'] }}</div>
                        </td>
                        <td class="px-3 py-1.5 text-center">
                            <span class="px-1.5 py-0.5 rounded text-xs
                                {{ $row['type']==='RM'?'bg-gray-100 text-gray-600':'' }}
                                {{ $row['type']==='WIP'?'bg-yellow-100 text-yellow-700':'' }}
                                {{ $row['type']==='FP'?'bg-green-100 text-green-700':'' }}
                            ">{{ $row['type'] }}</span>
                        </td>
                        <td class="px-3 py-1.5 text-right font-medium">{{ number_format($row['total'], 3) }}</td>
                        <td class="px-3 py-1.5 text-xs text-gray-500">{{ $row['uom'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @if($stockSummary->count() > 8)
                <div class="mt-2 text-xs text-gray-400 text-right">+{{ $stockSummary->count() - 8 }} material lainnya</div>
            @endif
            @endif
        </div>
    </div>
</x-vendor-layout>
