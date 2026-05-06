<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Vendor Portal' }} - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak]{display:none!important}
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        /* ── Mobile: table → card ── */
        @media (max-width: 767px) {
            .mobile-cards table,
            .mobile-cards thead,
            .mobile-cards tbody,
            .mobile-cards tfoot,
            .mobile-cards tr,
            .mobile-cards th,
            .mobile-cards td { display: block; width: 100% !important; }
            .mobile-cards thead tr { display: none; }
            .mobile-cards tfoot tr { display: flex; flex-wrap: wrap; gap: 0.25rem; padding: 0.5rem; }
            .mobile-cards tfoot td { width: auto !important; }
            .mobile-cards tbody tr {
                background: #fff;
                border-radius: 0.5rem;
                border: 1px solid #e5e7eb;
                box-shadow: 0 1px 3px rgba(0,0,0,.06);
                margin-bottom: 0.625rem;
                overflow: hidden;
            }
            .mobile-cards tbody td {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                padding: 0.4rem 0.75rem !important;
                border: none;
                border-bottom: 1px solid #f3f4f6;
                font-size: 0.8125rem;
                text-align: left !important;
                gap: 0.5rem;
            }
            .mobile-cards tbody td:last-child { border-bottom: none; }
            .mobile-cards tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                font-size: 0.7rem;
                color: #6b7280;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                white-space: nowrap;
                flex-shrink: 0;
                min-width: 38%;
                max-width: 50%;
                text-align: left;
                padding-top: 0.1rem;
            }
            .mobile-cards .overflow-x-auto { overflow-x: visible; }
            .mobile-cards table { table-layout: auto; }
        }
    </style>
</head>
<body class="font-sans antialiased bg-gray-100">

<div x-data="{ sidebarOpen: false }" class="flex h-screen overflow-hidden">
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
         class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

    {{-- Sidebar --}}
    <aside
        :class="sidebarOpen ? 'translate-x-0' : ''"
        class="fixed inset-y-0 left-0 z-40 w-72 max-w-[85vw] bg-teal-900 text-white flex flex-col flex-shrink-0 min-h-0 transform transition-transform duration-200 -translate-x-full lg:translate-x-0 lg:static lg:w-64 lg:max-w-none">
        {{-- Logo --}}
        <div class="flex items-center h-16 px-6 bg-teal-950 font-bold text-lg tracking-wide flex-shrink-0">
            <span class="text-yellow-400">VENDOR</span><span class="ml-1 text-white">Portal</span>
        </div>

        {{-- Vendor info --}}
        @php $vendor = auth()->user()->vendor; @endphp
        <div class="px-4 py-3 bg-teal-800 text-xs text-teal-200 flex-shrink-0">
            <div class="font-semibold text-white text-sm truncate">{{ $vendor?->name ?? '–' }}</div>
            <div class="text-teal-300 text-xs mt-0.5">{{ $vendor?->code ?? '' }}</div>
        </div>

        {{-- Nav --}}
        <nav class="hide-scrollbar flex-1 min-h-0 overflow-y-auto py-3 text-sm">
            {{-- Dashboard --}}
            <a href="{{ route('vendor.dashboard') }}"
               class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-md transition
                      {{ request()->routeIs('vendor.dashboard') ? 'bg-teal-700 text-white' : 'text-teal-200 hover:bg-teal-800 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>

            <div class="mx-4 my-3 border-t border-teal-700/50"></div>

            {{-- Purchase Orders --}}
            <a href="{{ route('vendor.purchase-orders.index') }}"
               class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-md transition
                      {{ request()->routeIs('vendor.purchase-orders*') ? 'bg-teal-700 text-white' : 'text-teal-200 hover:bg-teal-800 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Purchase Order
            </a>

            {{-- Surat Jalan --}}
            <a href="{{ route('vendor.delivery-notes.index') }}"
               class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-md transition
                      {{ request()->routeIs('vendor.delivery-notes*') ? 'bg-teal-700 text-white' : 'text-teal-200 hover:bg-teal-800 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Surat Jalan
            </a>

            {{-- Material Receipts (Kiriman dari IPPI) --}}
            <a href="{{ route('vendor.material-receipts.index') }}"
               class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-md transition
                      {{ request()->routeIs('vendor.material-receipts*') ? 'bg-teal-700 text-white' : 'text-teal-200 hover:bg-teal-800 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8m0 8l-6-3m6 3l6-3"/>
                </svg>
                Kiriman Bahan
            </a>

            {{-- Goods Receipts --}}
            <a href="{{ route('vendor.goods-receipts.index') }}"
               class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-md transition
                      {{ request()->routeIs('vendor.goods-receipts*') ? 'bg-teal-700 text-white' : 'text-teal-200 hover:bg-teal-800 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Goods Receipt
            </a>

            {{-- Vendor Production Orders --}}
            <a href="{{ route('vendor.production-orders.index') }}"
               class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-md transition
                      {{ request()->routeIs('vendor.production-orders*') ? 'bg-teal-700 text-white' : 'text-teal-200 hover:bg-teal-800 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2v8h6v-8c0-1.105-1.343-2-3-2zm0 0V5m-7 7h14M5 19h14"/>
                </svg>
                Production Order
            </a>

            {{-- Stock Overview --}}
            <a href="{{ route('vendor.stocks.index') }}"
               class="flex items-center gap-3 px-4 py-2.5 mx-2 rounded-md transition
                      {{ request()->routeIs('vendor.stocks*') ? 'bg-teal-700 text-white' : 'text-teal-200 hover:bg-teal-800 hover:text-white' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
                Stok Material
            </a>
        </nav>

        {{-- User --}}
        <div class="px-5 py-4 bg-teal-950 text-xs text-teal-300 flex-shrink-0">
            <div class="font-semibold text-white text-sm">{{ auth()->user()->name ?? '-' }}</div>
            <div class="capitalize mt-0.5">{{ auth()->user()->roleModel?->display_name ?? '' }}</div>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button type="submit" class="text-red-300 hover:text-red-100 transition">Logout</button>
            </form>
        </div>
    </aside>

    {{-- Main Content --}}
    <div class="flex-1 flex flex-col overflow-hidden min-w-0">
        {{-- Topbar --}}
        <header class="bg-white shadow-sm h-16 flex items-center px-4 md:px-6 justify-between flex-shrink-0 gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <button @click="sidebarOpen = true" class="lg:hidden inline-flex items-center justify-center rounded-md p-2 text-gray-600 hover:bg-gray-100" aria-label="Open menu">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <h1 class="text-base md:text-lg font-semibold text-gray-800 truncate">{{ $title ?? 'Dashboard' }}</h1>
            </div>
            {{-- User dropdown --}}
            <div class="flex items-center gap-3" x-data="{ userMenuOpen: false }">
                <span class="hidden md:inline text-sm text-gray-400">{{ now()->format('d M Y') }}</span>
                <div class="relative">
                    <button @click="userMenuOpen = !userMenuOpen"
                            @keydown.escape="userMenuOpen = false"
                            class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-gray-100 transition">
                        <div class="w-8 h-8 rounded-full bg-teal-600 flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </div>
                        <span class="hidden sm:inline text-sm font-medium text-gray-700 max-w-[120px] truncate">{{ auth()->user()->name }}</span>
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="userMenuOpen" x-cloak @click.away="userMenuOpen = false"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 top-full mt-1 w-52 bg-white rounded-lg shadow-lg border border-gray-200 z-50 py-1">
                        <div class="px-4 py-2 border-b border-gray-100">
                            <div class="text-sm font-semibold text-gray-800 truncate">{{ auth()->user()->name }}</div>
                            <div class="text-xs text-gray-500 capitalize mt-0.5">{{ auth()->user()->roleModel?->display_name ?? '' }}</div>
                        </div>
                        <a href="{{ route('profile.edit') }}"
                           class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Profil Saya
                        </a>
                        <a href="{{ route('profile.edit') }}#password"
                           class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                            Ganti Password
                        </a>
                        <hr class="my-1 border-gray-100">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- Page Body --}}
        <main class="flex-1 overflow-y-auto p-3 md:p-6 mobile-cards">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-800 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</div>

<script>
(function() {
    function initMobileCards() {
        document.querySelectorAll('.mobile-cards table').forEach(function(table) {
            var headers = Array.from(table.querySelectorAll('thead th')).map(function(th) {
                return th.textContent.trim();
            });
            table.querySelectorAll('tbody tr').forEach(function(row) {
                Array.from(row.querySelectorAll('td')).forEach(function(td, i) {
                    if (headers[i] && !td.getAttribute('data-label')) {
                        td.setAttribute('data-label', headers[i]);
                    }
                });
            });
        });
    }
    document.addEventListener('DOMContentLoaded', initMobileCards);
})();
</script>

</body>
</html>
