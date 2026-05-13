<x-app-layout>
    <x-slot name="title">Detail SKM {{ $skm->skm_number }}</x-slot>
    <div class="space-y-6">

        {{-- Header Card --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex flex-wrap justify-between items-start gap-3">
                <div>
                    <div class="text-xs text-gray-400">Nomor SKM</div>
                    <div class="text-2xl font-bold text-blue-700 font-mono">{{ $skm->skm_number }}</div>
                    <div class="flex flex-wrap gap-4 mt-1 text-sm text-gray-600">
                        <span>Tanggal Order: <b>{{ $skm->order_date->format('d M Y') }}</b></span>
                        @php $firstItem = $skm->items->first(); @endphp
                        @if($firstItem?->expected_delivery_date)
                        <span>Est. Pengiriman: <b class="text-blue-700">{{ $firstItem->expected_delivery_date->format('d M Y') }}</b></span>
                        @endif
                        @if($firstItem?->storageLocation)
                        <span>Lokasi Gudang: <b class="text-blue-700">{{ $firstItem->storageLocation->code }} — {{ $firstItem->storageLocation->name }}</b></span>
                        @endif
                        <span>Dibuat oleh: <b>{{ $skm->createdBy->name ?? '-' }}</b></span>
                        <span>Dibuat pada: <b>{{ $skm->created_at->format('d/m/Y H:i') }}</b></span>
                        <span>
                            Status:
                            <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $skm->status_color }}">
                                {{ $skm->status_label }}
                            </span>
                        </span>
                    </div>
                    @if($skm->notes)
                    <div class="text-sm text-gray-500 mt-1 italic">{{ $skm->notes }}</div>
                    @endif
                </div>
                <div class="flex flex-wrap gap-2">
                    {{-- Export buttons (always visible) --}}
                    <a href="{{ route('mm.skm.excel', $skm) }}"
                       class="inline-flex items-center gap-1.5 bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Excel
                    </a>
                    <a href="{{ route('mm.skm.pdf', $skm) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 bg-red-700 text-white px-4 py-2 rounded text-sm hover:bg-red-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        PDF
                    </a>

                    {{-- Generate PO: hanya tampil jika belum ada PO yang dibuat dari SKM ini --}}
                    @if(in_array($skm->status, ['draft','sent']) && $skm->purchaseOrders->isEmpty())
                    <form method="POST" action="{{ route('mm.skm.generate-po', $skm) }}"
                          onsubmit="return confirm('Buat Purchase Order dari SKM ini? PO akan dibuat per vendor.')">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 bg-purple-700 text-white px-4 py-2 rounded text-sm hover:bg-purple-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-1M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            Generate PO
                        </button>
                    </form>
                    @endif

                    {{-- Status Update: Tandai Dikirim hanya jika draft DAN belum ada PO --}}
                    @if($skm->status === 'draft' && $skm->purchaseOrders->isEmpty())
                    <form method="POST" action="{{ route('mm.skm.status', $skm) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="sent">
                        <button type="submit"
                                class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">
                            Tandai Dikirim
                        </button>
                    </form>
                    <form method="POST" action="{{ route('mm.skm.status', $skm) }}"
                          onsubmit="return confirm('Batalkan SKM ini?')">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="cancelled">
                        <button type="submit" class="bg-gray-400 text-white px-4 py-2 rounded text-sm hover:bg-gray-500">
                            Batalkan
                        </button>
                    </form>
                    @elseif(in_array($skm->status, ['sent', 'partial_received']))
                    <form method="POST" action="{{ route('mm.skm.status', $skm) }}"
                          onsubmit="return confirm('Batalkan SKM ini?')">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="cancelled">
                        <button type="submit" class="bg-gray-400 text-white px-4 py-2 rounded text-sm hover:bg-gray-500">
                            Batalkan
                        </button>
                    </form>
                    @endif

                    @if($skm->status === 'draft' && $skm->purchaseOrders->isEmpty())
                    <form method="POST" action="{{ route('mm.skm.destroy', $skm) }}"
                          onsubmit="return confirm('Hapus SKM {{ $skm->skm_number }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700">Hapus</button>
                    </form>
                    @endif

                    <a href="{{ route('mm.skm.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm">Kembali</a>
                </div>
            </div>

            {{-- Summary --}}
            <div class="mt-4 grid grid-cols-3 gap-4 text-sm">
                <div class="bg-blue-50 p-3 rounded">
                    <div class="text-gray-500">Total Item</div>
                    <div class="text-xl font-bold text-blue-700">{{ $skm->items->count() }}</div>
                </div>
                <div class="bg-purple-50 p-3 rounded">
                    <div class="text-gray-500">Total Kartu</div>
                    <div class="text-xl font-bold text-purple-700">{{ $skm->items->sum('num_cards') }}</div>
                </div>
                <div class="bg-green-50 p-3 rounded">
                    <div class="text-gray-500">Vendor Terlibat</div>
                    <div class="text-xl font-bold text-green-700">{{ $skm->items->pluck('vendor_id')->filter()->unique()->count() }}</div>
                </div>
            </div>
        </div>

        {{-- Items Table --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-700 mb-3">Detail Item SKM</h3>
            <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-blue-900 text-white text-xs">
                    <tr>
                        <th class="px-3 py-2 text-left">#</th>
                        <th class="px-3 py-2 text-left">Material</th>
                        <th class="px-3 py-2 text-left">Vendor</th>
                        <th class="px-3 py-2 text-right">Stok Saat SKM</th>
                        <th class="px-3 py-2 text-right">Min. Stok</th>
                        <th class="px-3 py-2 text-right">Qty/Kartu</th>
                        <th class="px-3 py-2 text-right">Jml Kartu</th>
                        <th class="px-3 py-2 text-right">Total Order</th>
                        <th class="px-3 py-2 text-left">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($skm->items as $i => $item)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-3 py-2 text-gray-400 text-xs">{{ $i + 1 }}</td>
                        <td class="px-3 py-2">
                            <div class="font-mono text-blue-700 text-xs font-semibold">{{ $item->material->code ?? '-' }}</div>
                            <div class="text-gray-700">{{ $item->material->name ?? '-' }}</div>
                            <div class="text-gray-400 text-xs">{{ $item->material->unit_of_measure ?? '' }}</div>
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-600">
                            {{ $item->vendor->name ?? '-' }}
                        </td>
                        <td class="px-3 py-2 text-right {{ (float)$item->current_stock < (float)$item->min_stock ? 'text-red-600' : 'text-green-700' }} font-medium">
                            {{ number_format($item->current_stock, 3) }}
                        </td>
                        <td class="px-3 py-2 text-right text-gray-600">{{ number_format($item->min_stock, 3) }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($item->kanban_qty, 0) }}</td>
                        <td class="px-3 py-2 text-right font-semibold text-blue-700">{{ $item->num_cards }}</td>
                        <td class="px-3 py-2 text-right font-bold text-blue-900 text-base">{{ number_format($item->order_qty, 0) }}</td>
                        <td class="px-3 py-2 text-gray-500 text-xs">{{ $item->notes ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="px-4 py-4 text-center text-gray-400">Tidak ada item.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        {{-- Linked POs --}}
        @if($skm->purchaseOrders->isNotEmpty())
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-700 mb-3">Purchase Order Terkait</h3>
            <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100 text-gray-700 text-xs">
                    <tr>
                        <th class="px-3 py-2 text-left">No. PO</th>
                        <th class="px-3 py-2 text-left">Vendor</th>
                        <th class="px-3 py-2 text-left">Est. Pengiriman</th>
                        <th class="px-3 py-2 text-left">Lokasi Tujuan</th>
                        <th class="px-3 py-2 text-center">Status PO</th>
                        <th class="px-3 py-2 text-right">Total</th>
                        <th class="px-3 py-2 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($skm->purchaseOrders as $po)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-3 py-2 font-mono text-blue-700 font-semibold">{{ $po->po_number }}</td>
                        <td class="px-3 py-2">{{ $po->vendor->name ?? '-' }}</td>
                        <td class="px-3 py-2 text-xs text-gray-600">
                            {{ $po->expected_delivery_date ? $po->expected_delivery_date->format('d M Y') : '-' }}
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-600">
                            {{ $po->storageLocation ? $po->storageLocation->code . ' - ' . $po->storageLocation->name : '-' }}
                        </td>
                        <td class="px-3 py-2 text-center">
                            @php
                                $poStatusColor = match($po->status) {
                                    'draft'              => 'bg-gray-100 text-gray-600',
                                    'approved'           => 'bg-blue-100 text-blue-700',
                                    'partially_received' => 'bg-yellow-100 text-yellow-700',
                                    'received'           => 'bg-green-100 text-green-700',
                                    'cancelled'          => 'bg-red-100 text-red-600',
                                    default              => 'bg-gray-100 text-gray-600',
                                };
                                $poStatusLabel = match($po->status) {
                                    'draft'              => 'Draft',
                                    'approved'           => 'Approved',
                                    'partially_received' => 'Diterima Sebagian',
                                    'received'           => 'Diterima Semua',
                                    'cancelled'          => 'Dibatalkan',
                                    default              => ucfirst($po->status),
                                };
                            @endphp
                            <span class="px-2 py-0.5 rounded text-xs font-semibold {{ $poStatusColor }}">{{ $poStatusLabel }}</span>
                        </td>
                        <td class="px-3 py-2 text-right font-semibold">Rp {{ number_format($po->total_amount, 0, ',', '.') }}</td>
                        <td class="px-3 py-2 text-center">
                            <a href="{{ route('mm.purchase-orders.show', $po) }}" class="text-blue-600 hover:underline text-xs">Lihat PO</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
        @endif

    </div>
</x-app-layout>