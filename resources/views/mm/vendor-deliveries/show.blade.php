<x-app-layout>
    <x-slot name="title">Detail Kiriman: {{ $vendorDelivery->vmd_number }}</x-slot>
    <div class="max-w-3xl space-y-4">
        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <a href="{{ route('mm.vendor-deliveries.index') }}" class="text-blue-600 hover:underline text-sm">← Kembali</a>
                    <h2 class="text-lg font-semibold text-gray-700">
                        Kiriman: <span class="font-mono text-blue-700">{{ $vendorDelivery->vmd_number }}</span>
                    </h2>
                    <span class="px-2 py-0.5 rounded text-xs {{ $vendorDelivery->statusColor() }}">{{ $vendorDelivery->statusLabel() }}</span>
                </div>
            </div>

            @if(session('success'))
            <div class="mb-3 bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded text-sm">{{ session('success') }}</div>
            @endif

            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <div class="text-gray-500 text-xs">Vendor</div>
                    <div class="font-medium">{{ $vendorDelivery->vendor?->name }}</div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs">Tanggal Kirim</div>
                    <div class="font-medium">{{ $vendorDelivery->delivery_date?->format('d/m/Y') }}</div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs">No. Kendaraan</div>
                    <div class="font-medium">{{ $vendorDelivery->vehicle_number ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs">Driver</div>
                    <div class="font-medium">{{ $vendorDelivery->driver_name ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs">Dibuat Oleh</div>
                    <div class="font-medium">{{ $vendorDelivery->createdBy?->name }}</div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs">Dibuat Pada</div>
                    <div class="font-medium">{{ $vendorDelivery->created_at->format('d/m/Y H:i') }}</div>
                </div>
                @if($vendorDelivery->status === 'confirmed')
                <div>
                    <div class="text-gray-500 text-xs">Dikonfirmasi Oleh</div>
                    <div class="font-medium">{{ $vendorDelivery->confirmedBy?->name }} — {{ $vendorDelivery->confirmed_at?->format('d/m/Y H:i') }}</div>
                </div>
                @endif
                @if($vendorDelivery->notes)
                <div class="col-span-2">
                    <div class="text-gray-500 text-xs">Catatan</div>
                    <div>{{ $vendorDelivery->notes }}</div>
                </div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-5">
            <h3 class="font-semibold text-gray-700 mb-3">Item yang Dikirim</h3>
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left">Material</th>
                        <th class="px-4 py-2 text-left">Storage Location (Sumber)</th>
                        <th class="px-4 py-2 text-right">Qty</th>
                        <th class="px-4 py-2 text-left">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vendorDelivery->items as $item)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-2">
                            <div class="font-mono text-xs text-blue-600">{{ $item->material?->code }}</div>
                            <div>{{ $item->material?->name }}</div>
                        </td>
                        <td class="px-4 py-2">{{ $item->storageLocation?->code }} - {{ $item->storageLocation?->name }}</td>
                        <td class="px-4 py-2 text-right font-medium">{{ fmt_qty($item->quantity) }} {{ $item->material?->unit_of_measure }}</td>
                        <td class="px-4 py-2 text-gray-500 text-xs">{{ $item->notes ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
