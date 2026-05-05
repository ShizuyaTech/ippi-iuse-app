<x-app-layout>
    <x-slot name="title">Detail Routing: {{ $routing->routing_number }}</x-slot>
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-start">
                <div>
                    <div class="text-xs text-gray-400">Nomor Routing</div>
                    <div class="text-2xl font-bold text-blue-700 font-mono">{{ $routing->routing_number }}</div>
                    <div class="text-gray-600 mt-1">{{ $routing->material->code }} — {{ $routing->material->name }}</div>
                </div>
                <div class="flex gap-2">
                    <span class="px-3 py-1 text-sm rounded-full {{ $routing->status==='active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $routing->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                    </span>
                    <a href="{{ route('pp.routings.edit', $routing) }}" class="bg-yellow-500 text-white px-4 py-2 rounded text-sm">Edit</a>
                    <a href="{{ route('pp.routings.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm">Kembali</a>
                </div>
            </div>
            @if($routing->description)
            <div class="mt-2 text-sm text-gray-500">{{ $routing->description }}</div>
            @endif
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-700 mb-3">Urutan Operasi</h3>
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-right w-16">No.</th>
                        <th class="px-4 py-2 text-left">Deskripsi Operasi</th>
                        <th class="px-4 py-2 text-left">Work Center</th>
                        <th class="px-4 py-2 text-right">Std. Time (jam)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($routing->operations as $op)
                    <tr class="border-b">
                        <td class="px-4 py-2 text-right font-mono text-gray-500">{{ $op->operation_number }}</td>
                        <td class="px-4 py-2 font-medium">{{ $op->description }}</td>
                        <td class="px-4 py-2">{{ $op->workCenter->name ?? '-' }}</td>
                        <td class="px-4 py-2 text-right">{{ $op->standard_time }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
