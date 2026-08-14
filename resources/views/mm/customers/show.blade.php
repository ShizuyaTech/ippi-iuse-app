<x-app-layout>
    <x-slot name="title">Detail Customer</x-slot>
    <div class="bg-white rounded-lg shadow p-6 max-w-xl">
        <div class="flex justify-between items-start mb-4">
            <div>
                <div class="text-xs text-gray-400">Kode Customer</div>
                <div class="text-2xl font-bold text-blue-700 font-mono">{{ $customer->code }}</div>
            </div>
            <span class="px-3 py-1 text-sm rounded-full {{ $customer->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $customer->is_active ? 'Aktif' : 'Nonaktif' }}</span>
        </div>
        <div class="space-y-2 text-sm">
            <div><span class="text-gray-500">Nama:</span> <span class="font-medium">{{ $customer->name }}</span></div>
            <div><span class="text-gray-500">Kontak:</span> {{ $customer->contact_person ?? '-' }}</div>
            <div><span class="text-gray-500">Email:</span> {{ $customer->email ?? '-' }}</div>
            <div><span class="text-gray-500">Telepon:</span> {{ $customer->phone ?? '-' }}</div>
            <div><span class="text-gray-500">Alamat:</span> {{ $customer->address ?? '-' }}</div>
        </div>
        <div class="mt-4 flex gap-2">
            <a href="{{ route('mm.customers.edit', $customer) }}" class="bg-yellow-500 text-white px-4 py-2 rounded text-sm">Edit</a>
            <a href="{{ route('mm.customers.index') }}" data-back-key="back_mm_customers" class="bg-gray-200 text-gray-700 px-4 py-2 rounded text-sm">Kembali</a>
        </div>
    </div>
</x-app-layout>
