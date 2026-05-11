<x-app-layout>
    <x-slot name="title">Purchase Order</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-700">Daftar Purchase Order</h2>
            <div class="flex gap-2 print:hidden">
                <a href="{{ route('mm.purchase-orders.export', request()->query()) }}" class="inline-flex items-center gap-1.5 bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export Excel
                </a>
                <a href="{{ route('mm.purchase-orders.export-pdf', request()->query()) }}" target="_blank" class="inline-flex items-center gap-1.5 bg-red-700 text-white px-4 py-2 rounded text-sm hover:bg-red-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print PDF
                </a>
                <a href="{{ route('mm.purchase-orders.create') }}" class="bg-blue-700 text-white px-4 py-2 rounded text-sm hover:bg-blue-800">+ Buat PO</a>
            </div>
        </div>
        <form method="GET" class="flex flex-wrap gap-2 mb-4 print:hidden">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="No. PO / Nama Vendor..." class="border border-gray-300 rounded-lg px-3 py-2 text-sm flex-1 min-w-48">
            <input type="date" name="date_from" value="{{ request('date_from') }}" title="Dari tanggal order" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <input type="date" name="date_to" value="{{ request('date_to') }}" title="Sampai tanggal order" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <select name="vendor_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Semua Vendor</option>
                @foreach($vendors as $v)
                <option value="{{ $v->id }}" {{ request('vendor_id')==$v->id?'selected':'' }}>{{ $v->name }}</option>
                @endforeach
            </select>
            <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Semua Status</option>
                <option value="draft" {{ request('status')=='draft'?'selected':'' }}>Draft</option>
                <option value="approved" {{ request('status')=='approved'?'selected':'' }}>Approved</option>
                <option value="partially_received" {{ request('status')=='partially_received'?'selected':'' }}>Partial Received</option>
                <option value="received" {{ request('status')=='received'?'selected':'' }}>Received</option>
                <option value="cancelled" {{ request('status')=='cancelled'?'selected':'' }}>Cancelled</option>
            </select>
            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded text-sm">Cari</button>
            <a href="{{ route('mm.purchase-orders.index') }}" class="bg-gray-100 text-gray-600 px-4 py-2 rounded text-sm border hover:bg-gray-200">Reset</a>
        </form>
        <div class="mobile-cards overflow-x-auto">
        <table id="data-table" class="w-full text-sm border-collapse">
            <thead class="bg-blue-900 text-white">
                <tr>
                    <th class="px-4 py-2 text-left">No. PO</th>
                    <th class="px-4 py-2 text-left">Vendor</th>
                    <th class="px-4 py-2 text-left">Tgl Order</th>
                    <th class="px-4 py-2 text-left">Est. Terima</th>
                    <th class="px-4 py-2 text-right">Total</th>
                    <th class="px-4 py-2 text-center">Status</th>
                    <th class="px-4 py-2 text-center print:hidden">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pos as $po)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-2 font-mono text-blue-700 font-medium" data-label="No. PO">{{ $po->po_number }}</td>
                    <td class="px-4 py-2" data-label="Vendor">{{ $po->vendor->name ?? '-' }}</td>
                    <td class="px-4 py-2" data-label="Tgl Order">{{ $po->order_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-2" data-label="Est. Terima">{{ $po->expected_delivery_date?->format('d/m/Y') ?? '-' }}</td>
                    <td class="px-4 py-2 text-right" data-label="Total">{{ number_format($po->total_amount,0) }}</td>
                    <td class="px-4 py-2 text-center" data-label="Status">
                        <span class="px-2 py-0.5 rounded text-xs
                            {{ $po->status==='draft'?'bg-gray-100 text-gray-600':'' }}
                            {{ $po->status==='approved'?'bg-blue-100 text-blue-700':'' }}
                            {{ $po->status==='received'?'bg-green-100 text-green-700':'' }}
                            {{ $po->status==='cancelled'?'bg-red-100 text-red-700':'' }}
                            {{ $po->status==='partially_received'?'bg-yellow-100 text-yellow-700':'' }}
                        ">{{ ucfirst(str_replace('_',' ',$po->status)) }}</span>
                    </td>
                    <td class="px-4 py-2 text-center print:hidden">
                        <a href="{{ route('mm.purchase-orders.show', $po) }}" class="text-blue-600 hover:underline">Detail</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-4 text-center text-gray-400">Belum ada Purchase Order.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="mt-4 print:hidden">{{ $pos->links() }}</div>
    </div>
</x-app-layout>
