<x-app-layout>
    <x-slot name="title">Detail PO: {{ $purchaseOrder->po_number }}</x-slot>
    <div class="space-y-6">
        {{-- Header --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-start">
                <div>
                    <div class="text-xs text-gray-400">Nomor PO</div>
                    <div class="text-2xl font-bold text-blue-700 font-mono">{{ $purchaseOrder->po_number }}</div>
                    <div class="text-gray-600 mt-1">{{ $purchaseOrder->vendor->name ?? '-' }}</div>
                </div>
                <div class="text-right">
                    <span class="px-3 py-1 text-sm rounded-full font-medium
                        {{ $purchaseOrder->status==='draft'?'bg-gray-100 text-gray-600':'' }}
                        {{ $purchaseOrder->status==='approved'?'bg-blue-100 text-blue-700':'' }}
                        {{ $purchaseOrder->status==='received'?'bg-green-100 text-green-700':'' }}
                        {{ $purchaseOrder->status==='cancelled'?'bg-red-100 text-red-700':'' }}
                        {{ $purchaseOrder->status==='partially_received'?'bg-yellow-100 text-yellow-700':'' }}
                    ">{{ ucfirst(str_replace('_',' ',$purchaseOrder->status)) }}</span>
                    <div class="mt-2 flex gap-2 justify-end flex-wrap">
                        @if($purchaseOrder->status === 'draft')
                        @php
                            $today       = \Carbon\Carbon::today();
                            $delivDate   = $purchaseOrder->expected_delivery_date;
                            $daysLeft    = $delivDate ? $today->diffInDays($delivDate, false) : null; // negative = past
                            $autoApprDay = $delivDate ? $today->diffInDays($delivDate->copy()->subDays(2), false) : null;
                            $pastDeadline = $delivDate && $today->gt($delivDate);
                            $willAutoApprove = $delivDate && !$pastDeadline && $daysLeft <= 2;
                        @endphp

                        @if($pastDeadline)
                        {{-- Delivery date passed — cannot approve --}}
                        <div class="flex items-center gap-2 px-3 py-2 bg-red-50 border border-red-200 rounded text-sm text-red-700">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                            Tidak bisa approve — est. pengiriman sudah terlewat
                        </div>
                        @else
                        <form method="POST" action="{{ route('mm.purchase-orders.approve', $purchaseOrder) }}">
                            @csrf
                            <button class="bg-blue-700 text-white px-4 py-2 rounded text-sm hover:bg-blue-800">Approve</button>
                        </form>
                        @if($willAutoApprove)
                        <div class="flex items-center gap-1.5 px-3 py-1 bg-amber-50 border border-amber-300 rounded text-xs text-amber-700">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            @if($daysLeft == 0)
                            Auto-approve hari ini (H-0)
                            @elseif($daysLeft == 1)
                            Auto-approve besok (H-1)
                            @else
                            Auto-approve H-2 ({{ $delivDate->copy()->subDays(2)->format('d M Y') }})
                            @endif
                        </div>
                        @elseif($daysLeft !== null)
                        <div class="flex items-center gap-1.5 px-3 py-1 bg-gray-50 border border-gray-200 rounded text-xs text-gray-500">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Auto-approve pada {{ $delivDate->copy()->subDays(2)->format('d M Y') }} ({{ $daysLeft - 2 }} hari lagi)
                        </div>
                        @endif
                        @endif

                        <a href="{{ route('mm.purchase-orders.edit', $purchaseOrder) }}" class="bg-yellow-500 text-white px-4 py-2 rounded text-sm">Edit</a>
                        @endif

                        @if($purchaseOrder->status === 'approved')
                        <a href="{{ route('mm.goods-receipts.create', ['po_id'=>$purchaseOrder->id]) }}" class="bg-green-600 text-white px-4 py-2 rounded text-sm">Buat GR</a>
                        @endif
                        @if(!in_array($purchaseOrder->status, ['received','cancelled']))
                        <form method="POST" action="{{ route('mm.purchase-orders.cancel', $purchaseOrder) }}" onsubmit="return confirm('Batalkan PO ini?')">
                            @csrf
                            <button class="bg-red-600 text-white px-4 py-2 rounded text-sm">Cancel</button>
                        </form>
                        @endif
                        <a href="{{ route('mm.purchase-orders.pdf', $purchaseOrder) }}" target="_blank" class="bg-red-700 text-white px-4 py-2 rounded text-sm flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                            Print PDF
                        </a>
                        <a href="{{ route('mm.purchase-orders.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm">Kembali</a>
                    </div>
                    {{-- Approval info (once approved) --}}
                    @if($purchaseOrder->approved_at)
                    <div class="mt-2 text-xs text-right text-gray-400">
                        Disetujui: {{ $purchaseOrder->approved_at->format('d M Y H:i') }}
                        oleh <span class="font-medium">{{ $purchaseOrder->approved_by }}</span>
                    </div>
                    @endif
                </div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 text-sm">
                <div><span class="text-gray-500">Tgl Order:</span><br><span class="font-medium">{{ $purchaseOrder->order_date->format('d M Y') }}</span></div>
                <div><span class="text-gray-500">Est. Terima:</span><br><span class="font-medium">{{ $purchaseOrder->expected_delivery_date?->format('d M Y') ?? '-' }}</span></div>
                <div><span class="text-gray-500">Lokasi Gudang:</span><br><span class="font-medium">{{ $purchaseOrder->storageLocation?->code }} - {{ $purchaseOrder->storageLocation?->name ?? '-' }}</span></div>
                <div><span class="text-gray-500">Dibuat oleh:</span><br><span class="font-medium">{{ $purchaseOrder->createdBy->name ?? '-' }}</span></div>
                <div><span class="text-gray-500">Dibuat Pada:</span><br><span class="font-medium">{{ $purchaseOrder->created_at->format('d/m/Y H:i') }}</span></div>
            </div>
            <div class="mt-2 text-sm"><span class="text-gray-500">Total Amount:</span> <span class="font-bold text-blue-700 text-lg">{{ number_format($purchaseOrder->total_amount,0) }}</span></div>
            @if($purchaseOrder->notes)
            <div class="mt-3 text-sm text-gray-600"><span class="font-medium">Catatan:</span> {{ $purchaseOrder->notes }}</div>
            @endif
        </div>

        {{-- Items --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-700 mb-3">Item Purchase Order</h3>
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left">Material</th>
                        <th class="px-4 py-2 text-right">Qty Order</th>
                        <th class="px-4 py-2 text-right">Qty Terima</th>
                        <th class="px-4 py-2 text-right">Harga Satuan</th>
                        <th class="px-4 py-2 text-right">Total</th>
                        <th class="px-4 py-2 text-center">Progress</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseOrder->items as $item)
                    <tr class="border-b">
                        <td class="px-4 py-2">
                            <div class="font-mono text-blue-700 text-xs">{{ $item->material->code }}</div>
                            <div>{{ $item->material->name }}</div>
                        </td>
                        <td class="px-4 py-2 text-right">{{ number_format($item->quantity, 3) }}</td>
                        <td class="px-4 py-2 text-right">{{ number_format($item->quantity_received, 3) }}</td>
                        <td class="px-4 py-2 text-right">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="px-4 py-2 text-right font-medium">{{ number_format($item->total_price, 0) }}</td>
                        <td class="px-4 py-2">
                            @php $pct = $item->quantity > 0 ? min(100, ($item->quantity_received / $item->quantity) * 100) : 0; @endphp
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width:{{ $pct }}%"></div>
                            </div>
                            <div class="text-xs text-center text-gray-500 mt-0.5">{{ round($pct) }}%</div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- GR History --}}
        @if($purchaseOrder->goodsReceipts->count())
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-700 mb-3">Riwayat Goods Receipt</h3>
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left">No. GR</th>
                        <th class="px-4 py-2 text-left">Tanggal</th>
                        <th class="px-4 py-2 text-left">Lokasi</th>
                        <th class="px-4 py-2 text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseOrder->goodsReceipts as $gr)
                    <tr class="border-b">
                        <td class="px-4 py-2"><a href="{{ route('mm.goods-receipts.show', $gr) }}" class="text-blue-600 font-mono hover:underline">{{ $gr->gr_number }}</a></td>
                        <td class="px-4 py-2">{{ $gr->receipt_date->format('d/m/Y') }}</td>
                        <td class="px-4 py-2">{{ $gr->storageLocation->name ?? '-' }}</td>
                        <td class="px-4 py-2 text-center"><span class="px-2 py-0.5 rounded text-xs bg-green-100 text-green-700">{{ $gr->status }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</x-app-layout>
