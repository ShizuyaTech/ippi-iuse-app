<x-vendor-layout>
    <x-slot name="title">Detail Kiriman: {{ $materialReceipt->vmd_number }}</x-slot>
    <div class="max-w-3xl space-y-4">
        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <a href="{{ route('vendor.material-receipts.index') }}" class="text-teal-600 hover:underline text-sm">← Kembali</a>
                    <h2 class="text-lg font-semibold text-gray-700">
                        Kiriman: <span class="font-mono text-teal-700">{{ $materialReceipt->vmd_number }}</span>
                    </h2>
                    <span class="px-2 py-0.5 rounded text-xs {{ $materialReceipt->statusColor() }}">{{ $materialReceipt->statusLabel() }}</span>
                </div>

                @if($materialReceipt->status === 'sent')
                <form method="POST" action="{{ route('vendor.material-receipts.confirm', $materialReceipt) }}"
                    onsubmit="return confirm('Konfirmasi bahwa semua bahan sudah diterima sesuai daftar?')">
                    @csrf @method('PATCH')
                    <button type="submit" class="bg-teal-600 text-white px-4 py-2 rounded text-sm hover:bg-teal-700">
                        ✓ Konfirmasi Sudah Diterima
                    </button>
                </form>
                @endif
            </div>

            @if(session('success'))
            <div class="mb-3 bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded text-sm">{{ session('success') }}</div>
            @endif

            @if($materialReceipt->status === 'sent')
            <div class="mb-4 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-2 rounded text-sm">
                Periksa list material di bawah. Klik <strong>"Konfirmasi Sudah Diterima"</strong> jika semua bahan sudah sesuai.
            </div>
            @else
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded text-sm">
                Kiriman ini telah dikonfirmasi pada {{ $materialReceipt->confirmed_at?->format('d/m/Y H:i') }}.
            </div>
            @endif

            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <div class="text-gray-500 text-xs">Tanggal Kirim</div>
                    <div class="font-medium">{{ $materialReceipt->delivery_date?->format('d/m/Y') }}</div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs">No. Kendaraan</div>
                    <div class="font-medium">{{ $materialReceipt->vehicle_number ?? '-' }}</div>
                </div>
                <div>
                    <div class="text-gray-500 text-xs">Driver</div>
                    <div class="font-medium">{{ $materialReceipt->driver_name ?? '-' }}</div>
                </div>
                @if($materialReceipt->notes)
                <div class="col-span-2">
                    <div class="text-gray-500 text-xs">Catatan dari IPPI</div>
                    <div>{{ $materialReceipt->notes }}</div>
                </div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-5">
            <h3 class="font-semibold text-gray-700 mb-3">Daftar Material yang Dikirim</h3>
            <table class="w-full text-sm border-collapse">
                <thead class="bg-teal-700 text-white">
                    <tr>
                        <th class="px-4 py-2 text-left">Material</th>
                        <th class="px-4 py-2 text-right">Qty</th>
                        <th class="px-4 py-2 text-left">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($materialReceipt->items as $item)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-2">
                            <div class="font-mono text-xs text-teal-700">{{ $item->material?->code }}</div>
                            <div>{{ $item->material?->name }}</div>
                        </td>
                        <td class="px-4 py-2 text-right font-medium">
                            {{ number_format($item->quantity, 3) }} {{ $item->material?->unit_of_measure }}
                        </td>
                        <td class="px-4 py-2 text-gray-500 text-xs">{{ $item->notes ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-vendor-layout>
