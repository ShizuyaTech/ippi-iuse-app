<x-vendor-layout>
    <x-slot name="title">Detail Order: {{ $productionOrder->order_number }}</x-slot>

    <div class="max-w-4xl space-y-4">
        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <a href="{{ route('vendor.production-orders.index') }}" class="text-teal-700 hover:underline text-sm">← Kembali</a>
                    <h2 class="text-lg font-semibold text-gray-700">{{ $productionOrder->order_number }}</h2>
                    <span class="px-2 py-0.5 rounded text-xs {{ $productionOrder->statusColor() }}">{{ $productionOrder->statusLabel() }}</span>
                </div>
                <div class="flex gap-2">
                    @if($productionOrder->status === 'draft')
                    <form method="POST" action="{{ route('vendor.production-orders.release', $productionOrder) }}">
                        @csrf
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">Release</button>
                    </form>
                    @endif
                    @if(in_array($productionOrder->status, ['released', 'in_progress']))
                    <form method="POST" action="{{ route('vendor.production-orders.complete', $productionOrder) }}">
                        @csrf
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700">Complete</button>
                    </form>
                    @endif
                    @if(!in_array($productionOrder->status, ['completed', 'cancelled']))
                    <form method="POST" action="{{ route('vendor.production-orders.cancel', $productionOrder) }}" onsubmit="return confirm('Batalkan order ini?')">
                        @csrf
                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded text-sm hover:bg-red-600">Cancel</button>
                    </form>
                    @endif
                </div>
            </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <div class="text-gray-500 text-xs">Material</div>
                    <div class="font-mono text-xs text-gray-500">{{ $productionOrder->material?->code }}</div>
                    <div>{{ $productionOrder->material?->name }}</div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs">Tipe / Satuan</div>
                    <div>
                        <span class="px-1.5 py-0.5 rounded text-xs
                            {{ $productionOrder->material?->type==='WIP' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' }}">
                            {{ $productionOrder->material?->type ?? '-' }}
                        </span>
                        <span class="ml-1 text-gray-600">{{ $productionOrder->material?->unit_of_measure ?? '-' }}</span>
                    </div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs">Referensi PO</div>
                    <div class="font-mono text-xs font-medium text-teal-700">{{ $productionOrder->purchaseOrderItem?->purchaseOrder?->po_number ?? '-' }}</div>
                    @if($productionOrder->purchaseOrderItem?->expected_delivery_date)
                    <div class="text-xs text-gray-500">Est. kirim: {{ $productionOrder->purchaseOrderItem->expected_delivery_date?->format('d/m/Y') }}</div>
                    @endif
                </div>
                <div>
                    <div class="text-gray-500 text-xs">Qty Planned</div>
                    <div class="font-semibold">{{ number_format($productionOrder->quantity_planned, 3) }} {{ $productionOrder->material?->unit_of_measure }}</div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs">Qty OK</div>
                    <div class="font-semibold text-green-700">{{ number_format($productionOrder->quantity_ok, 3) }}</div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs">Qty NG</div>
                    <div class="font-semibold text-red-700">{{ number_format($productionOrder->quantity_ng, 3) }}</div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs">Sisa (Remaining)</div>
                    <div class="font-semibold text-blue-700">{{ number_format($productionOrder->remainingQty(), 3) }}</div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs">Rencana Mulai – Selesai</div>
                    <div class="text-xs">{{ $productionOrder->planned_start_date?->format('d/m/Y') ?? '-' }} — {{ $productionOrder->planned_end_date?->format('d/m/Y') ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs">Aktual Mulai – Selesai</div>
                    <div class="text-xs">{{ $productionOrder->actual_start_date?->format('d/m/Y') ?? '-' }} — {{ $productionOrder->actual_end_date?->format('d/m/Y') ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs">Dibuat Pada</div>
                    <div class="text-xs font-medium">{{ $productionOrder->created_at->format('d/m/Y H:i') }}</div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs">Dibuat Oleh</div>
                    <div class="text-sm">{{ $productionOrder->createdBy?->name ?? '-' }}</div>
                </div>
                </div>{{-- end grid --}}

            @if($productionOrder->notes)
            <div class="mt-3 text-sm">
                <div class="text-gray-500 text-xs">Catatan</div>
                <div>{{ $productionOrder->notes }}</div>
            </div>
            @endif

            @if($productionOrder->deliveryNote)
            <div class="mt-3 text-sm bg-teal-50 border border-teal-200 text-teal-800 rounded px-3 py-2">
                Surat Jalan otomatis sudah dibuat:
                <a class="font-mono underline" href="{{ route('vendor.delivery-notes.show', $productionOrder->deliveryNote) }}">{{ $productionOrder->deliveryNote->dn_number }}</a>
            </div>
            @endif
        </div>

        @if(in_array($productionOrder->status, ['released', 'in_progress']))
        <div class="bg-white rounded-lg shadow p-5">
            <h3 class="font-semibold text-gray-700 mb-3">Input Report (Qty OK / Qty NG)</h3>
            <form method="POST" action="{{ route('vendor.production-orders.report', $productionOrder) }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                @csrf
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Tanggal</label>
                    <input type="date" name="report_date" value="{{ user_now()->format('Y-m-d') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Qty OK</label>
                    <input type="number" name="quantity_ok" min="0" step="0.001" value="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Qty NG</label>
                    <input type="number" name="quantity_ng" min="0" step="0.001" value="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                </div>
                <div class="md:col-span-4">
                    <label class="block text-xs text-gray-500 mb-1">Notes</label>
                    <input type="text" name="notes" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Opsional">
                </div>
                <div class="md:col-span-4">
                    <button type="submit" class="bg-teal-700 text-white px-4 py-2 rounded text-sm hover:bg-teal-800">Simpan Report</button>
                </div>
            </form>
            @if($errors->any())
            <div class="mt-3 text-xs text-red-600">{{ $errors->first() }}</div>
            @endif
        </div>
        @endif

        <div class="bg-white rounded-lg shadow p-5">
            <h3 class="font-semibold text-gray-700 mb-3">Riwayat Report</h3>
            <table class="w-full text-sm border-collapse">
                <thead class="bg-teal-700 text-white">
                    <tr>
                        <th class="px-4 py-2 text-left">Tanggal</th>
                        <th class="px-4 py-2 text-right">Qty OK</th>
                        <th class="px-4 py-2 text-right">Qty NG</th>
                        <th class="px-4 py-2 text-left">Notes</th>
                        <th class="px-4 py-2 text-left">User</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($productionOrder->reports as $report)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-2">{{ $report->report_date?->format('d/m/Y') }}</td>
                        <td class="px-4 py-2 text-right text-green-700">{{ number_format($report->quantity_ok, 3) }}</td>
                        <td class="px-4 py-2 text-right text-red-700">{{ number_format($report->quantity_ng, 3) }}</td>
                        <td class="px-4 py-2">{{ $report->notes ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $report->createdBy?->name ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-4 text-center text-gray-400">Belum ada report output.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-vendor-layout>
