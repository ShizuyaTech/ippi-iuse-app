<x-app-layout>
    <x-slot name="title">Surat Jalan Masuk</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-700">Surat Jalan dari Vendor</h2>
            <a href="{{ route('mm.delivery-notes.export', request()->query()) }}"
               class="bg-emerald-600 text-white px-4 py-2 rounded text-sm hover:bg-emerald-700">
                Export Excel
            </a>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded text-sm">{{ session('success') }}</div>
        @endif

        <form method="GET" class="flex flex-wrap gap-2 mb-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="No. SJ / No. PO / Vendor..."
                class="border rounded px-3 py-2 text-sm flex-1 min-w-48">
            <select name="vendor_id" class="border rounded px-3 py-2 text-sm">
                <option value="">Semua Vendor</option>
                @foreach($vendors as $v)
                    <option value="{{ $v->id }}" {{ request('vendor_id')==$v->id ? 'selected' : '' }}>{{ $v->name }}</option>
                @endforeach
            </select>
            <select name="status" class="border rounded px-3 py-2 text-sm">
                <option value="">Semua Status</option>
                <option value="pending"   {{ request('status')=='pending'   ? 'selected' : '' }}>Menunggu Konfirmasi</option>
                <option value="confirmed" {{ request('status')=='confirmed' ? 'selected' : '' }}>Dikonfirmasi</option>
                <option value="received"  {{ request('status')=='received'  ? 'selected' : '' }}>Sudah Diterima</option>
                <option value="cancelled" {{ request('status')=='cancelled' ? 'selected' : '' }}>Dibatalkan</option>
            </select>
            <select name="source_type" class="border rounded px-3 py-2 text-sm">
                <option value="">Semua Sumber</option>
                <option value="vendor_production_order" {{ request('source_type')=='vendor_production_order' ? 'selected' : '' }}>Auto dari Vendor PO</option>
                <option value="manual" {{ request('source_type')=='manual' ? 'selected' : '' }}>Manual Vendor</option>
            </select>
            <select name="gr_status" class="border rounded px-3 py-2 text-sm">
                <option value="">Semua GR</option>
                <option value="pending" {{ request('gr_status')=='pending' ? 'selected' : '' }}>Belum GR</option>
                <option value="created" {{ request('gr_status')=='created' ? 'selected' : '' }}>Sudah GR</option>
            </select>
            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded text-sm">Filter</button>
            <a href="{{ route('mm.delivery-notes.index') }}" class="bg-gray-100 text-gray-600 px-4 py-2 rounded text-sm border hover:bg-gray-200">Reset</a>
        </form>

        <table class="w-full text-sm border-collapse">
            <thead class="bg-blue-800 text-white">
                <tr>
                    <th class="px-4 py-2 text-left">No. SJ</th>
                    <th class="px-4 py-2 text-left">Vendor</th>
                    <th class="px-4 py-2 text-left">No. PO</th>
                    <th class="px-4 py-2 text-left">Est. Pengiriman</th>
                    <th class="px-4 py-2 text-center">Item</th>
                    <th class="px-4 py-2 text-center">Status</th>
                    <th class="px-4 py-2 text-center">GR</th>
                    <th class="px-4 py-2 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deliveryNotes as $dn)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-blue-700">
                        {{ $dn->dn_number }}
                        @if($dn->source_type === 'vendor_production_order')
                            <span class="ml-2 px-2 py-0.5 rounded bg-indigo-100 text-indigo-700 text-[10px]">AUTO VPO</span>
                        @endif
                    </td>
                    <td class="px-4 py-2">{{ $dn->vendor?->name ?? '-' }}</td>
                    <td class="px-4 py-2 font-mono text-xs text-gray-600">{{ $dn->purchaseOrder?->po_number ?? '-' }}</td>
                    <td class="px-4 py-2">{{ $dn->estimated_delivery_date?->format('d/m/Y') ?? '-' }}</td>
                    <td class="px-4 py-2 text-center text-gray-500">{{ $dn->items_count }} item</td>
                    <td class="px-4 py-2 text-center">
                        <span class="px-2 py-0.5 rounded text-xs {{ $dn->statusColor() }}">{{ $dn->statusLabel() }}</span>
                    </td>
                    <td class="px-4 py-2 text-center">
                        @if($dn->goodsReceipt)
                            <a href="{{ route('mm.goods-receipts.show', $dn->goodsReceipt) }}" class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-green-100 text-green-700 hover:bg-green-200">
                                Sudah GR
                            </a>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-yellow-100 text-yellow-700">Belum GR</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-center">
                        <div class="flex items-center justify-center gap-1">
                            <a href="{{ route('mm.delivery-notes.show', $dn) }}"
                               class="bg-blue-600 text-white px-3 py-1 rounded text-xs hover:bg-blue-700">Detail</a>

                            @if($dn->status === 'received' && !$dn->goodsReceipt)
                                <a href="{{ route('mm.goods-receipts.create', ['po_id' => $dn->purchase_order_id, 'dn_id' => $dn->id]) }}"
                                   class="bg-orange-600 text-white px-3 py-1 rounded text-xs hover:bg-orange-700">Buat GR</a>
                            @endif

                            @if($dn->goodsReceipt)
                                <a href="{{ route('mm.goods-receipts.show', $dn->goodsReceipt) }}"
                                   class="bg-indigo-600 text-white px-3 py-1 rounded text-xs hover:bg-indigo-700">Lihat GR</a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-4 text-center text-gray-400">Belum ada surat jalan masuk.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $deliveryNotes->links() }}</div>
    </div>
</x-app-layout>
