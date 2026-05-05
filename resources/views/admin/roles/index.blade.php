<x-app-layout>
    <x-slot name="title">Manajemen Role</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-700">Daftar Role</h2>
            <a href="{{ route('admin.roles.create') }}" class="bg-blue-700 text-white px-4 py-2 rounded text-sm hover:bg-blue-800">+ Buat Role Baru</a>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-2 rounded text-sm">{{ session('error') }}</div>
        @endif

        <table class="w-full text-sm border-collapse">
            <thead class="bg-blue-900 text-white">
                <tr>
                    <th class="px-4 py-2 text-left">Nama Role</th>
                    <th class="px-4 py-2 text-left">Slug</th>
                    <th class="px-4 py-2 text-left">Deskripsi</th>
                    <th class="px-4 py-2 text-center">Jumlah Permission</th>
                    <th class="px-4 py-2 text-center">Tipe</th>
                    <th class="px-4 py-2 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-2 font-medium">{{ $role->display_name }}</td>
                    <td class="px-4 py-2 font-mono text-xs text-gray-500">{{ $role->name }}</td>
                    <td class="px-4 py-2 text-gray-600">{{ $role->description ?? '-' }}</td>
                    <td class="px-4 py-2 text-center">{{ $role->permissions_count }}</td>
                    <td class="px-4 py-2 text-center">
                        @if($role->is_system)
                            <span class="px-2 py-0.5 rounded text-xs bg-yellow-100 text-yellow-700">Sistem</span>
                        @else
                            <span class="px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-700">Kustom</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-center">
                        <div class="flex gap-2 justify-center">
                            <a href="{{ route('admin.roles.edit', $role) }}" class="text-yellow-600 hover:underline text-xs">Edit / Kelola Permission</a>
                            @if(!$role->is_system)
                                <span class="text-gray-300 text-xs">|</span>
                                <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="inline"
                                    onsubmit="return confirm('Hapus role {{ $role->display_name }}?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline text-xs">Hapus</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-4 text-center text-gray-400">Belum ada role.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>
