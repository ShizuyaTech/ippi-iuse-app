<x-app-layout>
    <x-slot name="title">Manajemen User</x-slot>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-700">Daftar User</h2>
            <a href="{{ route('admin.users.create') }}" class="bg-blue-700 text-white px-4 py-2 rounded hover:bg-blue-800 text-sm">+ Buat User Baru</a>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-2 rounded text-sm">{{ session('error') }}</div>
        @endif

        <form method="GET" class="flex gap-2 mb-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama / email..."
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm flex-1 min-w-48">
            <select name="role_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">Semua Role</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                        {{ $role->display_name }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded text-sm">Filter</button>
            <a href="{{ route('admin.users.index') }}" class="bg-gray-100 text-gray-600 px-4 py-2 rounded text-sm border hover:bg-gray-200">Reset</a>
        </form>

        <table class="w-full text-sm border-collapse">
            <thead class="bg-blue-900 text-white">
                <tr>
                    <th class="px-4 py-2 text-left">Nama</th>
                    <th class="px-4 py-2 text-left">Email</th>
                    <th class="px-4 py-2 text-left">Role</th>
                    <th class="px-4 py-2 text-left">Vendor</th>
                    <th class="px-4 py-2 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-2 font-medium">{{ $user->name }}</td>
                    <td class="px-4 py-2 text-gray-600">{{ $user->email }}</td>
                    <td class="px-4 py-2">
                        @if($user->roleModel)
                            <span class="px-2 py-0.5 rounded text-xs bg-blue-100 text-blue-700">{{ $user->roleModel->display_name }}</span>
                        @elseif($user->role)
                            <span class="px-2 py-0.5 rounded text-xs bg-gray-100 text-gray-600">{{ $user->role }}</span>
                        @else
                            <span class="text-gray-400 text-xs">–</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-gray-600">{{ $user->vendor?->name ?? '–' }}</td>
                    <td class="px-4 py-2 text-center">
                        <div class="flex gap-2 justify-center flex-wrap">
                            <a href="{{ route('admin.users.edit', $user) }}" class="text-yellow-600 hover:underline text-xs">Edit</a>
                            <span class="text-gray-300 text-xs">|</span>
                            <a href="{{ route('admin.users.permissions', $user) }}" class="text-blue-600 hover:underline text-xs">Permission</a>
                            @if($user->id !== auth()->id())
                                <span class="text-gray-300 text-xs">|</span>
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline"
                                    onsubmit="return confirm('Hapus user {{ $user->name }}?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:underline text-xs">Hapus</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-4 text-center text-gray-400">Belum ada user.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">{{ $users->links() }}</div>
    </div>
</x-app-layout>
