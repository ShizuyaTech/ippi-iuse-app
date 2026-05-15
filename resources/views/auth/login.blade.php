<x-guest-layout>
    <div class="max-w-md mx-auto">
        <div class="mb-4">
            <p class="text-xs uppercase tracking-[0.18em] text-slate-400 font-semibold">Secure Access</p>
            <h1 class="mt-1 text-xl sm:text-3xl font-bold text-slate-800">Masuk ke Sistem</h1>
            <p class="mt-1 text-sm text-slate-500">Gunakan akun Anda untuk melanjutkan proses operasional.</p>
        </div>

        <x-auth-session-status class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="space-y-3">
        @csrf

            <div>
                <label for="email" class="block text-sm font-semibold text-slate-700">Email</label>
                <input id="email"
                       type="email"
                       name="email"
                       value="{{ old('email') }}"
                       required
                       autofocus
                       autocomplete="username"
                       class="mt-1 block w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-cyan-600 focus:ring-cyan-600" />
                <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs text-red-600" />
            </div>

            <div>
                <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                <input id="password"
                       type="password"
                       name="password"
                       required
                       autocomplete="current-password"
                       class="mt-1 block w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:border-cyan-600 focus:ring-cyan-600" />
                <x-input-error :messages="$errors->get('password')" class="mt-1 text-xs text-red-600" />
            </div>

            {{-- <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pt-1">
                <label for="remember_me" class="inline-flex items-center text-sm text-slate-600">
                    <input id="remember_me" type="checkbox" name="remember" class="rounded border-slate-300 text-cyan-700 shadow-sm focus:ring-cyan-600">
                    <span class="ms-2">Remember me</span>
                </label>

                @if (Route::has('password.request'))
                    <a class="text-sm text-cyan-700 hover:text-cyan-800 font-medium" href="{{ route('password.request') }}">
                        Forgot password?
                    </a>
                @endif
            </div> --}}

            <button type="submit"
                    class="w-full inline-flex items-center justify-center rounded-lg bg-cyan-700 px-4 py-2 text-sm font-semibold text-white hover:bg-cyan-800 focus:outline-none focus:ring-2 focus:ring-cyan-600 focus:ring-offset-2 transition">
                Log in
            </button>

            {{-- <p class="text-[11px] text-slate-400 text-center pt-1">
                Pastikan perangkat scanner terhubung dengan jaringan internal sebelum login.
            </p> --}}
        </form>
    </div>

    <script>
        // Prevent accidental horizontal scroll on auth screen in small scanner devices.
        document.documentElement.style.overflowX = 'hidden';
        document.body.style.overflowX = 'hidden';
    </script>
</x-guest-layout>
