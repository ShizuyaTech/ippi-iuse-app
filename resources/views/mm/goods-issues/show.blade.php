<x-app-layout>
    <x-slot name="title">Detail GI: {{ $goodsIssue->gi_number }}</x-slot>
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-start">
                <div>
                    <div class="text-xs text-gray-400">Nomor GI</div>
                    <div class="text-2xl font-bold text-orange-600 font-mono">{{ $goodsIssue->gi_number }}</div>
                </div>
                <div class="flex flex-wrap gap-2 items-center">
                    <a href="{{ route('mm.goods-issues.edit', $goodsIssue) }}" class="bg-yellow-500 text-white px-4 py-2 rounded text-sm hover:bg-yellow-600">Edit</a>
                    <form method="POST" action="{{ route('mm.goods-issues.destroy', $goodsIssue) }}" onsubmit="return confirm('Hapus GI {{ $goodsIssue->gi_number }}? Stok akan dibalik.')">
                        @csrf @method('DELETE')
                        <button class="bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700">Hapus</button>
                    </form>
                    <a href="{{ route('mm.goods-issues.excel', $goodsIssue) }}"
                       class="inline-flex items-center gap-1.5 bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Export Excel
                    </a>
                    <a href="{{ route('mm.goods-issues.pdf', $goodsIssue) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 bg-red-700 text-white px-4 py-2 rounded text-sm hover:bg-red-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print PDF
                    </a>
                    <a href="{{ route('mm.goods-issues.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-300">Kembali</a>
                </div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 text-sm">
                <div>
                    <span class="text-gray-500">Tanggal:</span><br>
                    <span class="font-medium">{{ $goodsIssue->issue_date->format('d M Y') }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Dibuat Pada:</span><br>
                    <span class="font-medium">{{ $goodsIssue->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Dari Lokasi:</span><br>
                    <span class="font-medium">{{ $goodsIssue->storageLocation->name ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-gray-500">Tipe Issue:</span><br>
                    @php
                        $typeLabel = ['internal' => 'Pemakaian Internal', 'to_vendor' => 'Kirim ke Vendor', 'to_customer' => 'Kirim ke Customer'];
                        $typeColor = ['internal' => 'bg-gray-100 text-gray-700', 'to_vendor' => 'bg-blue-100 text-blue-700', 'to_customer' => 'bg-green-100 text-green-700'];
                        $t = $goodsIssue->issue_type ?? 'internal';
                    @endphp
                    <span class="px-2 py-0.5 rounded text-xs font-medium {{ $typeColor[$t] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ $typeLabel[$t] ?? $t }}
                    </span>
                </div>
                @if($goodsIssue->destination_name)
                <div>
                    <span class="text-gray-500">{{ $t === 'to_vendor' ? 'Vendor Tujuan' : 'Customer Tujuan' }}:</span><br>
                    <span class="font-medium text-blue-700">{{ $goodsIssue->destination_name }}</span>
                </div>
                @elseif($goodsIssue->destinationStorageLocation)
                <div>
                    <span class="text-gray-500">Lokasi Tujuan:</span><br>
                    <span class="font-medium text-gray-800">
                        {{ $goodsIssue->destinationStorageLocation->code }} — {{ $goodsIssue->destinationStorageLocation->name }}
                    </span>
                </div>
                @endif
            </div>
            @if($goodsIssue->notes)
            <div class="mt-3 text-sm">
                <span class="text-gray-500">Catatan:</span>
                <span class="text-gray-600 ml-1">{{ $goodsIssue->notes }}</span>
            </div>
            @endif
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-700 mb-3">Item Dikeluarkan</h3>
            <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left">Material</th>
                        <th class="px-4 py-2 text-right">Qty Keluar</th>
                        <th class="px-4 py-2 text-left">UoM</th>
                        <th class="px-4 py-2 text-left">Note / ID Packing</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($goodsIssue->items as $item)
                    <tr class="border-b">
                        <td class="px-4 py-2" data-label="Material">
                            <div>
                                <div class="font-mono text-blue-700 text-xs">{{ $item->material->code }}</div>
                                <div class="font-medium">{{ $item->material->name }}</div>
                            </div>
                        </td>
                        <td class="px-4 py-2 text-right font-medium text-orange-600" data-label="Qty Keluar">{{ number_format($item->quantity_issued, 3) }}</td>
                        <td class="px-4 py-2 text-gray-500" data-label="UoM">{{ $item->material->unit_of_measure ?? '-' }}</td>
                        <td class="px-4 py-2" data-label="Note / ID Packing">
                            @if($item->note)
                            <span class="font-mono text-xs bg-yellow-50 border border-yellow-200 text-yellow-800 px-2 py-0.5 rounded">{{ $item->note }}</span>
                            @else
                            <span class="text-gray-300">-</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
    </div>
</x-app-layout>

