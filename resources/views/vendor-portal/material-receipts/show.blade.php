<x-vendor-layout>
    <x-slot name="title">Detail Kiriman: {{ $materialReceipt->vmd_number }}</x-slot>
    <div class="max-w-3xl space-y-4">
        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <a href="{{ route('vendor.material-receipts.index') }}" data-back-key="back_vendor_material_receipts" class="text-teal-600 hover:underline text-sm">← Kembali</a>
                    <h2 class="text-lg font-semibold text-gray-700 mt-1">
                        Kiriman: <span class="font-mono text-teal-700">{{ $materialReceipt->vmd_number }}</span>
                    </h2>
                    <span class="mt-1 inline-block px-2 py-0.5 rounded text-xs {{ $materialReceipt->statusColor() }}">{{ $materialReceipt->statusLabel() }}</span>
                </div>
                <div class="flex flex-wrap gap-2 items-center">
                    <a href="{{ route('vendor.material-receipts.print-pdf', $materialReceipt) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 bg-red-700 text-white px-4 py-2 rounded text-sm hover:bg-red-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print PDF
                    </a>
                </div>
            </div>

            @if(session('success'))
            <div class="mb-3 bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded text-sm">{{ session('success') }}</div>
            @endif

            @if($materialReceipt->status === 'confirmed')
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded text-sm">
                Kiriman ini telah dikonfirmasi pada {{ $materialReceipt->confirmed_at?->format('d/m/Y H:i') }}.
            </div>
            @endif

            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><div class="text-gray-500 text-xs">Tanggal Kirim</div><div class="font-medium">{{ $materialReceipt->delivery_date?->format('d/m/Y') }}</div></div>
                <div><div class="text-gray-500 text-xs">No. Kendaraan</div><div class="font-medium">{{ $materialReceipt->vehicle_number ?? '-' }}</div></div>
                <div><div class="text-gray-500 text-xs">Driver</div><div class="font-medium">{{ $materialReceipt->driver_name ?? '-' }}</div></div>
                <div><div class="text-gray-500 text-xs">Dibuat Pada</div><div class="font-medium">{{ $materialReceipt->created_at->format('d/m/Y H:i') }}</div></div>
                @if($materialReceipt->notes)
                <div class="col-span-2"><div class="text-gray-500 text-xs">Catatan dari IPPI</div><div>{{ $materialReceipt->notes }}</div></div>
                @endif
            </div>
        </div>

        @if($materialReceipt->status === 'sent')
        <div class="bg-white rounded-lg shadow p-5">
            <h3 class="font-semibold text-gray-700 mb-1">Konfirmasi Penerimaan Bahan</h3>
            <p class="text-sm text-gray-500 mb-4">Periksa dan sesuaikan qty aktual yang diterima. Stok vendor akan bertambah sesuai qty yang Anda konfirmasi.</p>

            <form method="POST" action="{{ route('vendor.material-receipts.confirm', $materialReceipt) }}">
                @csrf
                <table class="w-full text-sm border-collapse mb-4">
                    <thead class="bg-teal-700 text-white">
                        <tr>
                            <th class="px-4 py-2 text-left">Material</th>
                            <th class="px-4 py-2 text-right">Qty Dikirim</th>
                            <th class="px-4 py-2 text-right">Qty Aktual Diterima</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($materialReceipt->items as $item)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-2">
                                <div class="font-mono text-xs text-teal-700">{{ $item->material?->code }}</div>
                                <div>{{ $item->material?->name }}</div>
                                <div class="text-xs text-gray-400">{{ $item->material?->unit_of_measure }}</div>
                            </td>
                            <td class="px-4 py-2 text-right font-medium text-gray-600">
                                {{ fmt_qty($item->quantity) }}
                            </td>
                            <td class="px-4 py-2 text-right">
                                <input type="number" step="0.001" min="0" max="{{ $item->quantity }}"
                                    name="items[{{ $item->id }}][quantity_confirmed]"
                                    value="{{ number_format($item->quantity, 3, '.', '') }}"
                                    class="w-28 border rounded px-2 py-1 text-right text-sm focus:outline-none focus:ring-2 focus:ring-teal-400">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                @error('items.*')
                <div class="mb-3 text-red-600 text-sm">{{ $message }}</div>
                @enderror

                <div class="flex justify-end">
                    <button type="submit" onclick="return confirm('Konfirmasi penerimaan bahan dengan qty aktual di atas?')"
                        class="bg-teal-600 text-white px-5 py-2 rounded text-sm hover:bg-teal-700 font-medium">
                        Konfirmasi Penerimaan
                    </button>
                </div>
            </form>
        </div>
        @else
        <div class="bg-white rounded-lg shadow p-5">
            <h3 class="font-semibold text-gray-700 mb-3">Detail Material</h3>
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left">Material</th>
                        <th class="px-4 py-2 text-right">Qty Dikirim</th>
                        <th class="px-4 py-2 text-right">Qty Diterima</th>
                        <th class="px-4 py-2 text-right">Selisih</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($materialReceipt->items as $item)
                    @php $selisih = $item->quantity - ($item->quantity_confirmed ?? $item->quantity); @endphp
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-2">
                            <div class="font-mono text-xs text-teal-700">{{ $item->material?->code }}</div>
                            <div>{{ $item->material?->name }}</div>
                        </td>
                        <td class="px-4 py-2 text-right text-gray-500">{{ fmt_qty($item->quantity) }}</td>
                        <td class="px-4 py-2 text-right font-medium text-teal-700">{{ fmt_qty($item->quantity_confirmed ?? $item->quantity) }} {{ $item->material?->unit_of_measure }}</td>
                        <td class="px-4 py-2 text-right {{ $selisih > 0 ? 'text-orange-600 font-medium' : 'text-gray-400' }}">
                            {{ $selisih > 0 ? '-'.fmt_qty($selisih) : '' }}{!! $selisih == 0 ? '&ndash;' : '' !!}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</x-vendor-layout>