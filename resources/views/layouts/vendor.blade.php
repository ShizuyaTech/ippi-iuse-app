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
            <span class="hidden sm:inline text-sm text-gray-500">{{ now()->format('d M Y') }}</span>
        </header>

        {{-- Page Body --}}
        <main class="flex-1 overflow-y-auto p-3 md:p-6">
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

</body>
</html>
