<x-vendor-layout>
    <x-slot name="title">Profil Saya</x-slot>

    <div class="max-w-2xl mx-auto space-y-6">

        {{-- Success flash --}}
        @if(session('status') === 'profile-updated')
            <div class="bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded text-sm">
                Profil berhasil diperbarui.
            </div>
        @endif

        {{-- Update Profile Information --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-1">Informasi Profil</h2>
            <p class="text-sm text-gray-500 mb-5">Perbarui nama dan alamat email akun Anda.</p>

            <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                @csrf
                @method('patch')

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                    <input id="name" name="name" type="text"
                           value="{{ old('name', $user->name) }}"
                           required autofocus autocomplete="name"
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-teal-500 focus:border-teal-500 @error('name') border-red-400 @enderror">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input id="email" name="email" type="email"
                           value="{{ old('email', $user->email) }}"
                           required autocomplete="username"
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-teal-500 focus:border-teal-500 @error('email') border-red-400 @enderror">
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-4 pt-2">
                    <button type="submit"
                            class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg transition">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>

        {{-- Update Password --}}
        <div id="password" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-1">Ganti Password</h2>
            <p class="text-sm text-gray-500 mb-5">Gunakan password yang panjang dan acak agar akun Anda tetap aman.</p>

            @if(session('status') === 'password-updated')
                <div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded text-sm">
                    Password berhasil diperbarui.
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
                @csrf
                @method('put')

                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Password Saat Ini</label>
                    <input id="current_password" name="current_password" type="password"
                           autocomplete="current-password"
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-teal-500 focus:border-teal-500 @error('current_password', 'updatePassword') border-red-400 @enderror">
                    @error('current_password', 'updatePassword')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                    <input id="new_password" name="password" type="password"
                           autocomplete="new-password"
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-teal-500 focus:border-teal-500 @error('password', 'updatePassword') border-red-400 @enderror">
                    @error('password', 'updatePassword')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru</label>
                    <input id="password_confirmation" name="password_confirmation" type="password"
                           autocomplete="new-password"
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-teal-500 focus:border-teal-500">
                </div>

                <div class="flex items-center gap-4 pt-2">
                    <button type="submit"
                            class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg transition">
                        Perbarui Password
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-vendor-layout>
