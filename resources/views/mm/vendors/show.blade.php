<x-app-layout>
    <x-slot name="title">Detail Vendor</x-slot>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <div class="text-xs text-gray-400">Kode Vendor</div>
                    <div class="text-2xl font-bold text-blue-700 font-mono">{{ $vendor->code }}</div>
                </div>
                <span class="px-3 py-1 text-sm rounded-full {{ $vendor->is_active?'bg-green-100 text-green-700':'bg-red-100 text-red-700' }}">{{ $vendor->is_active?'Aktif':'Nonaktif' }}</span>
            </div>
            <div class="space-y-2 text-sm">
                <div><span class="text-gray-500">Nama:</span> <span class="font-medium">{{ $vendor->name }}</span></div>
                <div><span class="text-gray-500">Tipe:</span>
                    @php $typeColors = ['coil_center'=>'bg-blue-100 text-blue-700','process'=>'bg-purple-100 text-purple-700','general'=>'bg-gray-100 text-gray-600']; @endphp
                    <span class="px-2 py-0.5 rounded text-xs {{ $typeColors[$vendor->vendor_type ?? 'general'] ?? 'bg-gray-100 text-gray-600' }}">{{ $vendor->getTypeLabel() }}</span>
                </div>
                <div><span class="text-gray-500">Kontak:</span> {{ $vendor->contact_person ?? '-' }}</div>
                <div><span class="text-gray-500">Email:</span> {{ $vendor->email ?? '-' }}</div>
                <div><span class="text-gray-500">Telepon:</span> {{ $vendor->phone ?? '-' }}</div>
                <div><span class="text-gray-500">Alamat:</span> {{ $vendor->address ?? '-' }}</div>
            </div>
            <div class="mt-4 flex gap-2">
                <a href="{{ route('mm.vendors.edit', $vendor) }}" class="bg-yellow-500 text-white px-4 py-2 rounded text-sm">Edit</a>
                <a href="{{ route('mm.vendors.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm">Kembali</a>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-700 mb-3">Riwayat Purchase Order</h3>
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-3 py-2 text-left">No. PO</th>
                        <th class="px-3 py-2 text-left">Tanggal</th>
                        <th class="px-3 py-2 text-right">Total</th>
                        <th class="px-3 py-2 text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vendor->purchaseOrders as $po)
                    <tr class="border-b">
                        <td class="px-3 py-2"><a href="{{ route('mm.purchase-orders.show', $po) }}" class="text-blue-600 hover:underline font-mono">{{ $po->po_number }}</a></td>
                        <td class="px-3 py-2">{{ $po->order_date->format('d/m/Y') }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($po->total_amount,0) }}</td>
                        <td class="px-3 py-2 text-center"><span class="px-2 py-0.5 rounded text-xs bg-blue-50 text-blue-700">{{ $po->status }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-3 py-3 text-center text-gray-400">Belum ada PO.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
