<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'SAP Mini' }} - {{ config('app.name') }}</title>
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

            /* Opt-out: wrapper .no-mobile-cards tetap tampil sebagai tabel normal */
            .mobile-cards .no-mobile-cards table,
            .mobile-cards .no-mobile-cards thead,
            .mobile-cards .no-mobile-cards tbody,
            .mobile-cards .no-mobile-cards tfoot { display: table; width: 100% !important; }
            .mobile-cards .no-mobile-cards tr { display: table-row; width: auto !important; }
            .mobile-cards .no-mobile-cards th,
            .mobile-cards .no-mobile-cards td { display: table-cell; width: auto !important; }
            .mobile-cards .no-mobile-cards thead tr { display: table-row; }
            .mobile-cards .no-mobile-cards tbody tr { background: none; border-radius: 0; border: none; border-bottom: 1px solid #e5e7eb; box-shadow: none; margin-bottom: 0; overflow: visible; }
            .mobile-cards .no-mobile-cards tbody td { display: table-cell; justify-content: initial; align-items: initial; border-bottom: none; }
            .mobile-cards .no-mobile-cards tbody td::before { display: none; }
            .mobile-cards .no-mobile-cards .overflow-x-auto { overflow-x: auto; }
            /* Tetap sembunyikan elemen .hidden di dalam .no-mobile-cards */
            .mobile-cards .no-mobile-cards .hidden { display: none !important; }
        }

        /* SKM stats grid: 2 kolom mobile, 3 tablet, 6 desktop */
        .skm-stats-grid { display: grid; gap: 0.75rem; grid-template-columns: repeat(2, minmax(0,1fr)); }
        @media (min-width: 640px) { .skm-stats-grid { grid-template-columns: repeat(3, minmax(0,1fr)); } }
        @media (min-width: 768px) { .skm-stats-grid { grid-template-columns: repeat(6, minmax(0,1fr)); } }
    </style>
</head>
<body class="font-sans antialiased bg-gray-100">

<div x-data="{ sidebarOpen: false }" class="flex h-screen overflow-hidden">
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
         class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

    {{-- Sidebar --}}
    <aside
        :class="sidebarOpen ? 'translate-x-0' : ''"
        class="fixed inset-y-0 left-0 z-40 w-72 max-w-[85vw] bg-blue-900 text-white flex flex-col flex-shrink-0 min-h-0 transform transition-transform duration-200 -translate-x-full lg:translate-x-0 lg:static lg:w-64 lg:max-w-none">
        {{-- Logo --}}
        <div class="flex items-center h-16 px-6 bg-blue-950 font-bold text-lg tracking-wide flex-shrink-0">
            <span class="text-yellow-400">IUse</span><span class="ml-1">IPPI</span>
        </div>

        {{-- Nav --}}
        {{-- Accordion: openGroup = 'mm' | 'pp' | '' --}}
        <nav class="hide-scrollbar flex-1 min-h-0 overflow-y-auto py-3 text-sm"
             x-data="{ openGroup: '{{ request()->routeIs('mm.*') ? 'mm' : (request()->routeIs('pp.*') ? 'pp' : (request()->routeIs('admin.*') ? 'admin' : '')) }}' }">

            {{-- Dashboard --}}
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-3 py-2.5 mx-2 rounded-md transition
                      {{ request()->routeIs('dashboard') ? 'bg-blue-700 text-white' : 'text-blue-100 hover:bg-blue-800' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span class="text-sm font-bold uppercase tracking-widest">Dashboard</span>
            </a>

            {{-- Divider --}}
            <div class="mx-4 mt-5 mb-4 border-t border-blue-700/50"></div>

            {{-- ── SAP MM ── --}}
            @php
                $canMm = auth()->user()->isAdmin()
                    || collect(['mm.materials','mm.vendors','mm.customers','mm.storage_locations','mm.purchase_orders','mm.goods_receipts','mm.goods_issues','mm.stocks','mm.skm','mm.delivery_notes','mm.vendor_deliveries','mm.business_event_logs'])
                        ->contains(fn($p) => auth()->user()->hasPermission($p . '.view'));
            @endphp
            @if($canMm)
            <div class="mb-1">
                <button @click="openGroup = openGroup === 'mm' ? '' : 'mm'"
                        class="w-full flex items-center justify-between px-3 py-2.5 mx-2 rounded-md transition
                               {{ request()->routeIs('mm.*') ? 'bg-blue-800/60 text-white' : 'text-blue-300 hover:text-white hover:bg-blue-800/40' }}"
                        style="width: calc(100% - 1rem)">
                    <div class="flex items-center gap-3">
                        {{-- Box / Material Management icon --}}
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <span class="text-sm font-bold uppercase tracking-widest">SAP MM</span>
                    </div>
                    <svg :class="openGroup === 'mm' ? 'rotate-180' : ''"
                         class="w-4 h-4 flex-shrink-0 transition-transform duration-200"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="openGroup === 'mm'"
                     @if(!request()->routeIs('mm.*')) style="display:none" @endif
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-2"
                     class="mt-0.5 space-y-0.5 pb-1">

                    {{-- Master Material --}}
                    @if(auth()->user()->hasPermission('mm.materials.view'))
                    <a href="{{ route('mm.materials.index') }}"
                       class="flex items-center gap-3 pl-7 pr-4 py-2 mx-2 rounded-md transition text-base
                              {{ request()->routeIs('mm.materials*') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        Master Material
                    </a>
                    @endif

                    {{-- Vendor --}}
                    @if(auth()->user()->hasPermission('mm.vendors.view'))
                    <a href="{{ route('mm.vendors.index') }}"
                       class="flex items-center gap-3 pl-7 pr-4 py-2 mx-2 rounded-md transition text-base
                              {{ request()->routeIs('mm.vendors*') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        Vendor
                    </a>
                    @endif

                    {{-- Customer --}}
                    @if(auth()->user()->hasPermission('mm.customers.view'))
                    <a href="{{ route('mm.customers.index') }}"
                       class="flex items-center gap-3 pl-7 pr-4 py-2 mx-2 rounded-md transition text-base
                              {{ request()->routeIs('mm.customers*') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Customer
                    </a>
                    @endif

                    {{-- Storage Location --}}
                    @if(auth()->user()->hasPermission('mm.storage_locations.view'))
                    <a href="{{ route('mm.storage-locations.index') }}"
                       class="flex items-center gap-3 pl-7 pr-4 py-2 mx-2 rounded-md transition text-base
                              {{ request()->routeIs('mm.storage-locations*') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Storage Location
                    </a>
                    @endif

                    {{-- Purchase Order --}}
                    @if(auth()->user()->hasPermission('mm.purchase_orders.view'))
                    <a href="{{ route('mm.purchase-orders.index') }}"
                       class="flex items-center gap-3 pl-7 pr-4 py-2 mx-2 rounded-md transition text-base
                              {{ request()->routeIs('mm.purchase-orders*') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        Purchase Order
                    </a>
                    @endif

                    {{-- Goods Receipt --}}
                    @if(auth()->user()->hasPermission('mm.goods_receipts.view'))
                    <a href="{{ route('mm.goods-receipts.index') }}"
                       class="flex items-center gap-3 pl-7 pr-4 py-2 mx-2 rounded-md transition text-base
                              {{ request()->routeIs('mm.goods-receipts*') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        Goods Receipt
                    </a>
                    @endif

                    {{-- Goods Issue --}}
                    @if(auth()->user()->hasPermission('mm.goods_issues.view'))
                    <a href="{{ route('mm.goods-issues.index') }}"
                       class="flex items-center gap-3 pl-7 pr-4 py-2 mx-2 rounded-md transition text-base
                              {{ request()->routeIs('mm.goods-issues*') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                        Goods Issue
                    </a>
                    @endif

                    {{-- Stock Overview --}}
                    @if(auth()->user()->hasPermission('mm.stocks.view'))
                    <a href="{{ route('mm.stocks.index') }}"
                       class="flex items-center gap-3 pl-7 pr-4 py-2 mx-2 rounded-md transition text-base
                              {{ request()->routeIs('mm.stocks*') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Stock Overview
                    </a>
                    @endif

                    {{-- Summary Kanban --}}
                    @if(auth()->user()->hasPermission('mm.skm.view'))
                    <a href="{{ route('mm.skm.index') }}"
                       class="flex items-center gap-3 pl-7 pr-4 py-2 mx-2 rounded-md transition text-base
                              {{ request()->routeIs('mm.skm*') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        Summary Kanban
                    </a>
                    @endif

                    {{-- Business Event Logs --}}
                    @if(auth()->user()->hasPermission('mm.business_event_logs.view'))
                    <a href="{{ route('mm.business-event-logs.index') }}"
                       class="flex items-center gap-3 pl-7 pr-4 py-2 mx-2 rounded-md transition text-base
                              {{ request()->routeIs('mm.business-event-logs*') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 3h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z"/>
                        </svg>
                        Business Logs
                    </a>
                    @endif
                </div>
            </div>
            @endif

            {{-- ── SAP PP ── --}}
            @php
                $canPp = auth()->user()->isAdmin()
                    || collect(['pp.work_centers','pp.boms','pp.routings','pp.production_orders','pp.mrp'])
                        ->contains(fn($p) => auth()->user()->hasPermission($p . '.view'));
            @endphp
            @if($canPp)
            <div class="mb-1">
                <button @click="openGroup = openGroup === 'pp' ? '' : 'pp'"
                        class="w-full flex items-center justify-between px-3 py-2.5 mx-2 rounded-md transition
                               {{ request()->routeIs('pp.*') ? 'bg-blue-800/60 text-white' : 'text-blue-300 hover:text-white hover:bg-blue-800/40' }}"
                        style="width: calc(100% - 1rem)">
                    <div class="flex items-center gap-3">
                        {{-- Gear / Production Planning icon --}}
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="text-sm font-bold uppercase tracking-widest">SAP PP</span>
                    </div>
                    <svg :class="openGroup === 'pp' ? 'rotate-180' : ''"
                         class="w-4 h-4 flex-shrink-0 transition-transform duration-200"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="openGroup === 'pp'"
                     @if(!request()->routeIs('pp.*')) style="display:none" @endif
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-2"
                     class="mt-0.5 space-y-0.5 pb-1">

                    {{-- Work Center --}}
                    @if(auth()->user()->hasPermission('pp.work_centers.view'))
                    <a href="{{ route('pp.work-centers.index') }}"
                       class="flex items-center gap-3 pl-7 pr-4 py-2 mx-2 rounded-md transition text-base
                              {{ request()->routeIs('pp.work-centers*') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                        Work Center
                    </a>
                    @endif

                    {{-- Bill of Materials --}}
                    @if(auth()->user()->hasPermission('pp.boms.view'))
                    <a href="{{ route('pp.boms.index') }}"
                       class="flex items-center gap-3 pl-7 pr-4 py-2 mx-2 rounded-md transition text-base
                              {{ request()->routeIs('pp.boms*') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                        Bill of Materials
                    </a>
                    @endif

                    {{-- Routing --}}
                    @if(auth()->user()->hasPermission('pp.routings.view'))
                    <a href="{{ route('pp.routings.index') }}"
                       class="flex items-center gap-3 pl-7 pr-4 py-2 mx-2 rounded-md transition text-base
                              {{ request()->routeIs('pp.routings*') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                        </svg>
                        Routing
                    </a>
                    @endif

                    {{-- Production Order --}}
                    @if(auth()->user()->hasPermission('pp.production_orders.view'))
                    <a href="{{ route('pp.production-orders.index') }}"
                       class="flex items-center gap-3 pl-7 pr-4 py-2 mx-2 rounded-md transition text-base
                              {{ request()->routeIs('pp.production-orders*') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        Production Order
                    </a>
                    @endif

                    {{-- MRP --}}
                    @if(auth()->user()->hasPermission('pp.mrp.view'))
                    <a href="{{ route('pp.mrp.index') }}"
                       class="flex items-center gap-3 pl-7 pr-4 py-2 mx-2 rounded-md transition text-base
                              {{ request()->routeIs('pp.mrp*') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        MRP
                    </a>
                    @endif
                </div>
            </div>
            @endif

            @if(auth()->user()->isAdmin())
            {{-- ── Admin ── --}}
            <div class="mx-4 mt-5 mb-4 border-t border-blue-700/50"></div>
            <div>
                <button @click="openGroup = openGroup === 'admin' ? '' : 'admin'"
                    class="flex items-center w-full px-3 py-2.5 mx-0 rounded-md transition text-left
                           {{ request()->routeIs('admin.*') ? 'bg-blue-700' : 'hover:bg-blue-800' }} text-white">
                    <div class="flex items-center gap-3 flex-1">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="text-sm font-bold uppercase tracking-widest">Admin</span>
                    </div>
                    <svg :class="openGroup === 'admin' ? 'rotate-180' : ''"
                        class="w-4 h-4 flex-shrink-0 transition-transform duration-200"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="openGroup === 'admin'"
                     @if(!request()->routeIs('admin.*')) style="display:none" @endif
                     class="mt-1 space-y-0.5 pb-2">

                    {{-- Role Management --}}
                    <a href="{{ route('admin.roles.index') }}"
                       class="flex items-center gap-3 pl-7 pr-4 py-2 mx-2 rounded-md transition text-base
                              {{ request()->routeIs('admin.roles*') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        Manajemen Role
                    </a>

                    {{-- User Management --}}
                    <a href="{{ route('admin.users.index') }}"
                       class="flex items-center gap-3 pl-7 pr-4 py-2 mx-2 rounded-md transition text-base
                              {{ request()->routeIs('admin.users*') ? 'bg-blue-700 text-white' : 'text-blue-200 hover:bg-blue-800 hover:text-white' }}">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        Manajemen User
                    </a>
                </div>
            </div>
            @endif

        </nav>

        {{-- User --}}
        {{-- <div class="px-5 py-4 bg-blue-950 text-xs text-blue-300 flex-shrink-0">
            <div class="font-semibold text-white text-sm">{{ auth()->user()->name ?? '-' }}</div>
            <div class="capitalize mt-0.5">{{ auth()->user()->roleModel?->display_name ?? auth()->user()->role ?? '' }}</div>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button type="submit" class="text-red-300 hover:text-red-100 transition">Logout</button>
            </form>
        </div> --}}
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
                <span class="hidden md:inline text-sm text-gray-400">{{ user_now()->format('d M Y') }}</span>
                <div class="relative">
                    <button @click="userMenuOpen = !userMenuOpen"
                            @keydown.escape="userMenuOpen = false"
                            class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-gray-100 transition">
                        <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
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
                            <div class="text-xs text-gray-500 capitalize mt-0.5">{{ auth()->user()->roleModel?->display_name ?? auth()->user()->role ?? '' }}</div>
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
            {{-- Flash Messages --}}
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
            @if($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-800 px-4 py-3 rounded">
                    <ul class="list-disc list-inside text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
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
