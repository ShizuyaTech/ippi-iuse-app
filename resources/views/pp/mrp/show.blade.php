<x-app-layout>
    <x-slot name="title">Hasil MRP Run</x-slot>
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-start">
                <div>
                    <div class="text-xs text-gray-400">MRP Run</div>
                    <div class="text-xl font-bold text-blue-700">{{ $mrpRun->created_at->format('d M Y H:i') }}</div>
                    <div class="text-sm text-gray-500 mt-1">Dijalankan oleh: {{ $mrpRun->runBy->name ?? '-' }}</div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('pp.mrp.excel', $mrpRun) }}"
                       class="inline-flex items-center gap-1.5 bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Export Excel
                    </a>
                    <a href="{{ route('pp.mrp.pdf', $mrpRun) }}" target="_blank"
                       class="inline-flex items-center gap-1.5 bg-red-700 text-white px-4 py-2 rounded text-sm hover:bg-red-800">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Print PDF
                    </a>
                    <a href="{{ route('pp.mrp.index') }}" data-back-key="back_pp_mrp" class="bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm">Kembali</a>
                </div>
            </div>
            <div class="mt-3 grid grid-cols-3 gap-4 text-sm">
                <div class="bg-blue-50 p-3 rounded">
                    <div class="text-gray-500">Total Material</div>
                    <div class="text-2xl font-bold text-blue-700">{{ $mrpRun->results->count() }}</div>
                </div>
                <div class="bg-red-50 p-3 rounded">
                    <div class="text-gray-500">Perlu Pengadaan (PO)</div>
                    <div class="text-2xl font-bold text-red-600">{{ $mrpRun->results->where('recommendation_type','purchase')->count() }}</div>
                </div>
                <div class="bg-yellow-50 p-3 rounded">
                    <div class="text-gray-500">Perlu Produksi</div>
                    <div class="text-2xl font-bold text-yellow-600">{{ $mrpRun->results->where('recommendation_type','production')->count() }}</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-700 mb-1">Detail Hasil MRP</h3>
            <p class="text-xs text-gray-400 mb-1">
                Formula: <b>Gross</b> = BOM explosion multi-level (FP&rarr;WIP&rarr;RM) &nbsp;&rarr;&nbsp;
                <b>Net</b> = Gross &minus; Stok Tersedia &minus; Sisa PO &nbsp;&rarr;&nbsp;
                <b>+Safety 20%</b> &nbsp;&rarr;&nbsp;
                <b>Order</b> = round-up ke Qty/Case
            </p>
            <p class="text-xs text-amber-600 mb-3">* Stok Tersedia = Stok RM aktual + Stok FP/WIP dikonversi ke RM via BOM (stok FP &divide; base qty &times; qty komponen)</p>
            <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-blue-900 text-white text-xs">
                    <tr>
                        <th class="px-3 py-2 text-left">Material</th>
                        <th class="px-3 py-2 text-right">Gross Req.</th>
                        <th class="px-3 py-2 text-right">Sisa PO</th>
                        <th class="px-3 py-2 text-right">Net Req.</th>
                        <th class="px-3 py-2 text-right">Safety 20%</th>
                        <th class="px-3 py-2 text-right">Total + Safety</th>
                        <th class="px-3 py-2 text-right">Qty/Case</th>
                        <th class="px-3 py-2 text-right">Order ke Vendor</th>
                        <th class="px-3 py-2 text-right">Stok Tersedia*</th>
                        <th class="px-3 py-2 text-center">Rekomendasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mrpRun->results->sortBy(fn($r)=>$r->recommendation_type==='purchase'?0:1) as $result)
                    @php
                        $withSafety = (float)$result->net_requirement + (float)$result->safety_stock_qty;
                    @endphp
                    <tr class="border-b hover:bg-gray-50 {{ $result->recommendation_type==='purchase' ? 'bg-red-50/40' : '' }}">
                        <td class="px-3 py-2">
                            <div class="font-mono text-blue-700 text-xs font-semibold">{{ $result->material->code }}</div>
                            <div class="text-gray-700">{{ $result->material->name }}</div>
                            <div class="text-gray-400 text-xs">{{ $result->material->unit_of_measure }}</div>
                        </td>
                        <td class="px-3 py-2 text-right">{{ fmt_qty($result->gross_requirement) }}</td>
                        <td class="px-3 py-2 text-right text-green-700">
                            {{ (float)$result->open_po_qty > 0 ? fmt_qty($result->open_po_qty) : '-' }}
                        </td>
                        <td class="px-3 py-2 text-right font-medium">{{ fmt_qty($result->net_requirement) }}</td>
                        <td class="px-3 py-2 text-right text-amber-600">+{{ fmt_qty($result->safety_stock_qty) }}</td>
                        <td class="px-3 py-2 text-right font-medium text-blue-700">{{ fmt_qty($withSafety) }}</td>
                        <td class="px-3 py-2 text-right text-gray-500">
                            {{ (float)$result->qty_per_case > 0 ? number_format($result->qty_per_case, 0) : '-' }}
                        </td>
                        <td class="px-3 py-2 text-right font-bold text-blue-900 text-base">
                            {{ number_format($result->recommended_quantity, 0) }}
                        </td>
                        <td class="px-3 py-2 text-right {{ (float)$result->current_stock < (float)$result->gross_requirement ? 'text-red-500' : 'text-green-700' }}">
                            {{ fmt_qty($result->current_stock) }}
                        </td>
                        <td class="px-3 py-2 text-center">
                            @if($result->recommendation_type === 'purchase')
                            <span class="px-2 py-0.5 rounded text-xs bg-red-100 text-red-700 font-medium">Buat PO</span>
                            @else
                            <span class="px-2 py-0.5 rounded text-xs bg-yellow-100 text-yellow-700 font-medium">Produksi</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="px-4 py-4 text-center text-gray-400">Tidak ada hasil MRP.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
</x-app-layout>

