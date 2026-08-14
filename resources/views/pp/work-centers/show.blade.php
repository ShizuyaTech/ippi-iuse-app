<x-app-layout>
    <x-slot name="title">Detail Work Center</x-slot>
    <div class="bg-white rounded-lg shadow p-6 max-w-2xl">
        <div class="flex justify-between items-start mb-4">
            <div>
                <div class="text-xs text-gray-400">Kode Work Center</div>
                <div class="text-2xl font-bold text-blue-700 font-mono">{{ $workCenter->code }}</div>
                <div class="text-gray-600">{{ $workCenter->name }}</div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('pp.work-centers.edit', $workCenter) }}" class="bg-yellow-500 text-white px-4 py-2 rounded text-sm">Edit</a>
                <a href="{{ route('pp.work-centers.index') }}" data-back-key="back_pp_work_centers" class="bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm">Kembali</a>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div><span class="text-gray-500">Deskripsi:</span><p class="mt-1">{{ $workCenter->description ?? '-' }}</p></div>
            <div><span class="text-gray-500">Status:</span><p class="mt-1">
                <span class="px-2 py-0.5 rounded text-xs {{ $workCenter->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                    {{ $workCenter->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </p></div>
            <div><span class="text-gray-500">Kapasitas per Jam:</span><p class="mt-1 font-medium">{{ $workCenter->capacity_per_hour ?? '-' }}</p></div>
            <div><span class="text-gray-500">Biaya per Jam:</span><p class="mt-1 font-medium">{{ $workCenter->cost_per_hour ? 'Rp '.number_format($workCenter->cost_per_hour,0) : '-' }}</p></div>
        </div>
        @if($workCenter->routingOperations->count())
        <div class="mt-6">
            <h3 class="font-semibold text-gray-700 mb-2">Routing Operations</h3>
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100"><tr>
                    <th class="px-4 py-2 text-left">Routing</th>
                    <th class="px-4 py-2 text-left">Operasi</th>
                    <th class="px-4 py-2 text-right">Waktu (jam)</th>
                </tr></thead>
                <tbody>
                    @foreach($workCenter->routingOperations as $op)
                    <tr class="border-b">
                        <td class="px-4 py-2 font-mono text-blue-700">{{ $op->routing->routing_number ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $op->description }}</td>
                        <td class="px-4 py-2 text-right">{{ $op->standard_time ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</x-app-layout>
