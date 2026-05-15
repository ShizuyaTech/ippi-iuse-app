<x-app-layout>
    <x-slot name="title">Riwayat Mutasi Stok</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-wrap justify-between items-center gap-2 mb-4">
            <h2 class="text-lg font-semibold text-gray-700">Riwayat Mutasi Stok</h2>
            <a href="{{ route('mm.stocks.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded text-sm print:hidden">Stock Overview</a>
        </div>
        <form method="GET" class="flex flex-wrap gap-2 mb-4 print:hidden">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Kode/nama material..." class="border border-gray-300 rounded-lg px-3 py-2 text-sm flex-1 min-w-[180px]">
            <input type="date" name="date_from" value="{{ request('date_from') }}" title="Dari tanggal" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <input type="date" name="date_to"   value="{{ request('date_to') }}"   title="Sampai tanggal" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <select name="location" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Semua Lokasi</option>
                @foreach($locations as $loc)
                <option value="{{ $loc->id }}" {{ request('location')==$loc->id?'selected':'' }}>{{ $loc->code }} - {{ $loc->name }}</option>
                @endforeach
            </select>
            <select name="type" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Semua Tipe</option>
                <option value="GR"     {{ request('type')=='GR'    ?'selected':'' }}>GR (Goods Receipt)</option>
                <option value="GI"     {{ request('type')=='GI'    ?'selected':'' }}>GI (Goods Issue)</option>
                <option value="PP_GI"  {{ request('type')=='PP_GI' ?'selected':'' }}>PP GI (ke Produksi)</option>
                <option value="PP_GR"  {{ request('type')=='PP_GR' ?'selected':'' }}>PP GR (dari Produksi)</option>
            </select>
            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded text-sm">Filter</button>
            <a href="{{ route('mm.stocks.movements') }}" class="bg-gray-100 text-gray-600 px-4 py-2 rounded text-sm border hover:bg-gray-200">Reset</a>
        </form>
        <table class="w-full text-sm border-collapse">
            <thead class="bg-blue-900 text-white">
                <tr>
                    <th class="px-4 py-2 text-left">Tanggal</th>
                    <th class="px-4 py-2 text-left">Material</th>
                    <th class="px-4 py-2 text-left">Lokasi</th>
                    <th class="px-4 py-2 text-center">Tipe</th>
                    <th class="px-4 py-2 text-right">Qty</th>
                    <th class="px-4 py-2 text-left">Referensi</th>
                    <th class="px-4 py-2 text-left">User</th>
                </tr>
            </thead>
            <tbody>
                @forelse($movements as $m)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-2">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-2">
                        <div class="font-mono text-blue-700 text-xs">{{ $m->material->code }}</div>
                        <div>{{ $m->material->name }}</div>
                    </td>
                    <td class="px-4 py-2">{{ $m->storageLocation->name ?? '-' }}</td>
                    <td class="px-4 py-2 text-center">
                        <span class="px-2 py-0.5 rounded text-xs
                            {{ in_array($m->movement_type, ['GR','PP_GR']) ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700' }}
                        ">{{ $m->movement_type }}</span>
                    </td>
                    <td class="px-4 py-2 text-right font-medium
                        {{ in_array($m->movement_type, ['GR','PP_GR']) ? 'text-green-700' : 'text-orange-600' }}">
                        {{ in_array($m->movement_type, ['GR','PP_GR']) ? '+' : '-' }}{{ fmt_qty(abs($m->quantity)) }}
                    </td>
                    <td class="px-4 py-2 text-xs text-gray-500">{{ $m->reference ?? '-' }}</td>
                    <td class="px-4 py-2 text-xs">{{ $m->createdBy->name ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-4 text-center text-gray-400">Tidak ada riwayat mutasi.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $movements->links() }}</div>
    </div>
</x-app-layout>
