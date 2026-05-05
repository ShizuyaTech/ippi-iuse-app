<x-app-layout>
    <x-slot name="title">Detail Production Order: {{ $productionOrder->order_number }}</x-slot>
    <div class="space-y-6">
        {{-- Header --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-start">
                <div>
                    <div class="text-xs text-gray-400">Nomor Production Order</div>
                    <div class="text-2xl font-bold text-blue-700 font-mono">{{ $productionOrder->order_number }}</div>
                    <div class="text-gray-600 mt-1">{{ $productionOrder->material->code }} — {{ $productionOrder->material->name }}</div>
                </div>
                <div class="flex flex-col items-end gap-2">
                    <span class="px-3 py-1 text-sm rounded-full font-medium
                        {{ $productionOrder->status==='created'?'bg-gray-100 text-gray-600':''}}
                        {{ $productionOrder->status==='released'?'bg-blue-100 text-blue-700':'' }}
                        {{ $productionOrder->status==='in_progress'?'bg-yellow-100 text-yellow-700':'' }}
                        {{ $productionOrder->status==='completed'?'bg-green-100 text-green-700':'' }}
                        {{ $productionOrder->status==='cancelled'?'bg-red-100 text-red-700':'' }}
                    ">{{ ucfirst(str_replace('_',' ',$productionOrder->status)) }}</span>

                    <div class="flex gap-2 flex-wrap">
                        @if($productionOrder->status === 'created')
                        <form method="POST" action="{{ route('pp.production-orders.release', $productionOrder) }}">
                            @csrf
                            <button class="bg-blue-700 text-white px-4 py-2 rounded text-sm">Release</button>
                        </form>
                        <a href="{{ route('pp.production-orders.edit', $productionOrder) }}" class="bg-yellow-500 text-white px-4 py-2 rounded text-sm">Edit</a>
                        @endif

                        @if(in_array($productionOrder->status, ['released', 'in_progress']))
                        @php
                            $hasRemaining = $productionOrder->components->contains(
                                fn($c) => ($c->quantity_issued ?? 0) < $c->quantity_required - 0.001
                            );
                        @endphp
                        @endif

                        @if(in_array($productionOrder->status, ['released','in_progress']))
                        {{-- Konfirmasi Selesai --}}
                        <form method="POST" action="{{ route('pp.production-orders.confirm', $productionOrder) }}" onsubmit="return confirm('Konfirmasi selesai produksi dan posting GR?')" class="border rounded p-2 bg-green-50">
                            @csrf
                            <div class="text-xs text-gray-500 font-semibold mb-1">Konfirmasi Selesai</div>
                            <div class="flex gap-1 items-end flex-wrap">
                                <div>
                                    <div class="text-xs text-gray-400 mb-0.5">Qty OK:</div>
                                    <input type="number" name="quantity_ok" id="confirm-qty-ok" value="{{ $maxConfirmQty }}" min="0" step="0.001" class="border rounded px-2 py-1 text-sm w-24 text-right" required>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-400 mb-0.5">Qty NG:</div>
                                    <input type="number" name="quantity_ng" value="0" min="0" step="0.001" class="border rounded px-2 py-1 text-sm w-20 text-right" required>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-400 mb-0.5">Lokasi Tujuan:</div>
                                    <select name="storage_location_id" class="border rounded px-2 py-1 text-sm" required>
                                        @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" {{ $defaultFgLocation && $loc->id === $defaultFgLocation->id ? 'selected' : '' }}>{{ $loc->code }} — {{ $loc->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button class="bg-green-700 text-white px-3 py-1 rounded text-sm">Konfirmasi Selesai</button>
                            </div>
                        </form>
                        @endif

                        <a href="{{ route('pp.production-orders.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm self-end">Kembali</a>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 text-sm">
                <div><span class="text-gray-500">Qty Planned:</span><br><span class="font-medium">{{ number_format($productionOrder->quantity_planned, 3) }}</span></div>
                <div><span class="text-gray-500">Qty Produced:</span><br><span class="font-medium">{{ number_format($productionOrder->quantity_produced ?? 0, 3) }}</span></div>
                <div><span class="text-gray-500">Qty OK:</span><br><span class="font-medium text-green-700">{{ number_format($productionOrder->quantity_ok ?? 0, 3) }}</span></div>
                <div><span class="text-gray-500">Qty NG:</span><br><span class="font-medium text-red-600">{{ number_format($productionOrder->quantity_ng ?? 0, 3) }}</span></div>
                <div><span class="text-gray-500">BOM:</span><br>
                    @if($productionOrder->bom)
                    <a href="{{ route('pp.boms.show', $productionOrder->bom) }}" class="font-mono text-blue-600 hover:underline">{{ $productionOrder->bom->bom_number }}</a>
                    @else<span>-</span>@endif
                </div>
                <div><span class="text-gray-500">Routing:</span><br>
                    @if($productionOrder->routing)
                    <a href="{{ route('pp.routings.show', $productionOrder->routing) }}" class="font-mono text-blue-600 hover:underline">{{ $productionOrder->routing->routing_number }}</a>
                    @else<span>-</span>@endif
                </div>
                <div><span class="text-gray-500">Tgl Mulai:</span><br><span class="font-medium">{{ $productionOrder->planned_start_date?->format('d/m/Y') ?? '-' }}</span></div>
                <div><span class="text-gray-500">Tgl Selesai:</span><br><span class="font-medium">{{ $productionOrder->planned_end_date?->format('d/m/Y') ?? '-' }}</span></div>
            </div>
        </div>

        {{-- Components / GI Input --}}
        <div class="bg-white rounded-lg shadow p-6" id="gi-section">
            @if($errors->hasBag('default') && $errors->has('quantities'))
            <div class="mb-4 p-3 bg-red-50 border border-red-300 text-red-700 rounded text-sm">
                @foreach((array) $errors->get('quantities') as $e)
                    <div>• {{ $e }}</div>
                @endforeach
            </div>
            @endif

            @if(in_array($productionOrder->status, ['released', 'in_progress']) && $productionOrder->components->isNotEmpty())
            <form method="POST" action="{{ route('pp.production-orders.goods-issue', $productionOrder) }}"
                  onsubmit="return confirm('Post Goods Issue dengan qty yang diinput? Stok gudang akan dikurangi sesuai qty.')">
            @csrf
            @endif

            <div class="flex justify-between items-center mb-3">
                <h3 class="font-semibold text-gray-700">Komponen Produksi</h3>
                @if(in_array($productionOrder->status, ['released', 'in_progress']) && $productionOrder->components->isNotEmpty())
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-400">Sumber: RM → WH-01 &nbsp;|&nbsp; WIP → WH-02</span>
                    <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded text-sm hover:bg-orange-700">
                        {{ $productionOrder->status === 'in_progress' ? 'Top-up GI' : 'Post GI ke Produksi' }}
                    </button>
                </div>
                @endif
            </div>

            <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left">Material</th>
                        <th class="px-4 py-2 text-right">Qty Required</th>
                        <th class="px-4 py-2 text-right">Qty Issued</th>
                        @if(in_array($productionOrder->status, ['released', 'in_progress']))
                        <th class="px-4 py-2 text-right">Stok Tersedia</th>
                        <th class="px-4 py-2 text-right w-36">Qty GI <span class="font-normal text-gray-400">(input)</span></th>
                        @endif
                        <th class="px-4 py-2 text-left">Lokasi Sumber</th>
                        <th class="px-4 py-2 text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($productionOrder->components as $comp)
                    @php
                        $remaining   = round((float) $comp->quantity_required - (float) ($comp->quantity_issued ?? 0), 3);
                        $isPending   = $remaining > 0.001;
                        $stockInfo   = $componentStocks[$comp->id] ?? ['location_code' => '-', 'available' => 0];
                    @endphp
                    <tr class="border-b {{ $isPending ? '' : 'bg-green-50' }}">
                        <td class="px-4 py-2">
                            <div class="font-mono text-blue-700 text-xs">{{ $comp->material->code }}</div>
                            <div>{{ $comp->material->name }}</div>
                        </td>
                        <td class="px-4 py-2 text-right">{{ number_format($comp->quantity_required, 3) }}</td>
                        <td class="px-4 py-2 text-right {{ !$isPending ? 'text-green-700 font-medium' : 'text-gray-500' }}">
                            {{ number_format($comp->quantity_issued ?? 0, 3) }}
                        </td>
                        @if(in_array($productionOrder->status, ['released', 'in_progress']))
                        <td class="px-4 py-2 text-right {{ $stockInfo['available'] < $remaining ? 'text-red-600' : 'text-gray-600' }}">
                            {{ number_format($stockInfo['available'], 3) }}
                            <div class="text-xs text-gray-400">{{ $stockInfo['location_code'] }}</div>
                        </td>
                        <td class="px-4 py-2 text-right">
                            @if($isPending)
                            <input type="number"
                                   name="quantities[{{ $comp->id }}]"
                                   value="{{ old('quantities.' . $comp->id, $remaining) }}"
                                   min="0"
                                   max="{{ $remaining }}"
                                   step="0.001"
                                   class="w-32 border rounded px-2 py-1 text-sm text-right focus:border-orange-400 focus:ring-1 focus:ring-orange-300 gi-qty-input"
                                   data-required="{{ $comp->quantity_required }}"
                                   data-issued="{{ $comp->quantity_issued ?? 0 }}"
                                   data-planned="{{ $productionOrder->quantity_planned }}">
                            @else
                            <span class="text-xs text-green-600">Selesai</span>
                            <input type="hidden" name="quantities[{{ $comp->id }}]" value="0">
                            @endif
                        </td>
                        @endif
                        <td class="px-4 py-2 text-gray-500 text-xs">
                            {{ $comp->storageLocation->code ?? ($stockInfo['location_code'] ?? '-') }}
                            @if($comp->storageLocation)
                            <div>{{ $comp->storageLocation->name }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-center">
                            @if(!$isPending)
                            <span class="px-2 py-0.5 rounded text-xs bg-green-100 text-green-700">Issued</span>
                            @else
                            <span class="px-2 py-0.5 rounded text-xs bg-yellow-100 text-yellow-700">Pending</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>

            @if(in_array($productionOrder->status, ['released', 'in_progress']) && $productionOrder->components->isNotEmpty())
            </form>
            @endif
        </div>
    </div>

    @if(in_array($productionOrder->status, ['released', 'in_progress']))
    <script>
        // When GI qty inputs change, recompute max-confirm-qty suggestion
        const qtyOkInput = document.getElementById('confirm-qty-ok');
        const planned = {{ (float) $productionOrder->quantity_planned }};

        function recomputeConfirmQty() {
            let minRatio = 1;
            document.querySelectorAll('.gi-qty-input').forEach(input => {
                const required = parseFloat(input.dataset.required) || 0;
                if (required <= 0) return;
                const issued  = parseFloat(input.dataset.issued) || 0;
                const adding  = parseFloat(input.value) || 0;
                const totalIssued = issued + adding;
                const ratio = totalIssued / required;
                if (ratio < minRatio) minRatio = ratio;
            });
            if (qtyOkInput) {
                qtyOkInput.value = Math.round(Math.min(planned, minRatio * planned) * 1000) / 1000;
            }
        }

        document.querySelectorAll('.gi-qty-input').forEach(input => {
            input.addEventListener('input', recomputeConfirmQty);
        });
    </script>
    @endif
</x-app-layout>
