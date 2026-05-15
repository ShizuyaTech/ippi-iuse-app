<x-app-layout>
    <x-slot name="title">Detail BOM: {{ $bom->bom_number }}</x-slot>
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-start">
                <div>
                    <div class="text-xs text-gray-400">Nomor BOM</div>
                    <div class="text-2xl font-bold text-blue-700 font-mono">{{ $bom->bom_number }}</div>
                    <div class="text-gray-600 mt-1">{{ $bom->material->code }} — {{ $bom->material->name }}</div>
                </div>
                <div class="flex gap-2">
                    <span class="px-3 py-1 text-sm rounded-full {{ $bom->status==='active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $bom->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                    </span>
                    <a href="{{ route('pp.boms.edit', $bom) }}" class="bg-yellow-500 text-white px-4 py-2 rounded text-sm">Edit</a>
                    <a href="{{ route('pp.boms.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm">Kembali</a>
                </div>
            </div>
            <div class="mt-3 text-sm"><span class="text-gray-500">Qty Base:</span> <span class="font-medium">{{ fmt_qty($bom->base_quantity) }} {{ $bom->material->unit_of_measure }}</span></div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-700 mb-3">Komponen BOM</h3>
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left">#</th>
                        <th class="px-4 py-2 text-left">Kode</th>
                        <th class="px-4 py-2 text-left">Nama Material</th>
                        <th class="px-4 py-2 text-left">Tipe</th>
                        <th class="px-4 py-2 text-right">Qty</th>
                        <th class="px-4 py-2 text-left">UoM</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bom->items as $i => $item)
                    <tr class="border-b">
                        <td class="px-4 py-2 text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-4 py-2 font-mono text-blue-700 text-xs">{{ $item->material->code }}</td>
                        <td class="px-4 py-2">{{ $item->material->name }}</td>
                        <td class="px-4 py-2 text-xs"><span class="px-2 py-0.5 rounded bg-gray-100">{{ $item->material->type }}</span></td>
                        <td class="px-4 py-2 text-right font-medium">{{ fmt_qty($item->quantity) }}</td>
                        <td class="px-4 py-2">{{ $item->unit ?? $item->material->unit_of_measure }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
