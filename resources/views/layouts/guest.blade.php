<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SAP Mini ERP') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-gray-900 antialiased min-h-screen bg-slate-100">
    <div class="relative min-h-screen overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_#e2e8f0,_#f8fafc_45%,_#e0f2fe)]"></div>
        <div class="absolute -top-16 -left-16 w-72 h-72 rounded-full bg-cyan-200/35 blur-3xl"></div>
        <div class="absolute -bottom-20 -right-20 w-80 h-80 rounded-full bg-sky-300/25 blur-3xl"></div>

        <div class="relative min-h-screen flex items-center justify-center p-4 md:p-8">
            <div class="w-full max-w-5xl rounded-2xl shadow-2xl border border-slate-200/70 bg-white/90 backdrop-blur-sm overflow-hidden">
                <div class="grid md:grid-cols-2 min-h-[640px]">
                    <section class="hidden md:flex flex-col justify-between p-10 bg-gradient-to-br from-teal-900 via-cyan-900 to-slate-900 text-white">
                        <div>
                            <a href="/" class="inline-flex items-center gap-2">
                                <span class="font-extrabold tracking-wide text-xl">
                                    <span class="text-yellow-300">SAP</span> Mini ERP
                                </span>
                            </a>
                            <p class="mt-6 text-cyan-100/90 text-sm leading-relaxed max-w-sm">
                                Portal operasional terintegrasi untuk mengelola proses procurement, produksi, dan kolaborasi vendor dengan alur yang cepat dan akurat.
                            </p>
                        </div>
                        <div class="space-y-3 text-sm text-cyan-100/90">
                            <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-cyan-300"></span> SAP MM / PP Workflow</div>
                            <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-cyan-300"></span> Vendor Portal Operations</div>
                            <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-cyan-300"></span> Mobile Scanner Friendly</div>
                        </div>
                    </section>

                    <section class="p-6 sm:p-8 md:p-10 bg-white">
                        {{ $slot }}
                    </section>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
