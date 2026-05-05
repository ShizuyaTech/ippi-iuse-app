@if ($paginator->hasPages())
@php
    $currentPage = $paginator->currentPage();
    $lastPage    = $paginator->lastPage();

    if ($lastPage <= 5) {
        // Show all pages, no dots needed
        $middlePages         = range(1, $lastPage);
        $showSeparateFirst   = false;
        $showSeparateLast    = false;
        $showStartDots       = false;
        $showEndDots         = false;
    } else {
        // Always show page 1 and lastPage separately.
        // Show 3 "middle" pages around current (within 2..lastPage-1).
        $showSeparateFirst = true;
        $showSeparateLast  = true;

        $midStart = max(2, $currentPage - 1);
        $midEnd   = min($lastPage - 1, $currentPage + 1);

        // Ensure we always have exactly 3 middle pages when possible
        if (($midEnd - $midStart) < 2) {
            if ($midStart === 2) {
                $midEnd = min($lastPage - 1, $midStart + 2);
            } else {
                $midStart = max(2, $midEnd - 2);
            }
        }

        $middlePages   = ($midStart <= $midEnd) ? range($midStart, $midEnd) : [];
        $showStartDots = ($midStart > 2);
        $showEndDots   = ($midEnd < $lastPage - 1);
    }
@endphp
<nav role="navigation" aria-label="Pagination Navigation" class="flex items-center justify-between">

    {{-- Mobile: prev/next only --}}
    <div class="flex justify-between flex-1 sm:hidden">
        @if ($paginator->onFirstPage())
            <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-400 bg-white border border-gray-300 cursor-default rounded-md">
                &laquo; Sebelumnya
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                &laquo; Sebelumnya
            </a>
        @endif
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                Berikutnya &raquo;
            </a>
        @else
            <span class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-400 bg-white border border-gray-300 cursor-default rounded-md">
                Berikutnya &raquo;
            </span>
        @endif
    </div>

    {{-- Desktop --}}
    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">

        {{-- Info teks: menampilkan X-Y dari Z --}}
        <div>
            <p class="text-sm text-gray-600 leading-5">
                Menampilkan
                <span class="font-medium">{{ $paginator->firstItem() }}</span>
                &ndash;
                <span class="font-medium">{{ $paginator->lastItem() }}</span>
                dari
                <span class="font-medium">{{ $paginator->total() }}</span>
                data
            </p>
        </div>

        {{-- Tombol halaman --}}
        <div>
            <span class="relative z-0 inline-flex shadow-sm rounded-md">

                {{-- Prev --}}
                @if ($paginator->onFirstPage())
                    <span class="relative inline-flex items-center px-2 py-2 text-sm text-gray-400 bg-white border border-gray-300 cursor-default rounded-l-md" aria-disabled="true">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya"
                       class="relative inline-flex items-center px-2 py-2 text-sm text-gray-500 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50 transition">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    </a>
                @endif

                @if ($showSeparateFirst)
                    {{-- Halaman 1 --}}
                    @if ($currentPage === 1)
                        <span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-semibold text-white bg-blue-600 border border-blue-600 cursor-default" aria-current="page">1</span>
                    @else
                        <a href="{{ $paginator->url(1) }}" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition">1</a>
                    @endif

                    {{-- Titik awal --}}
                    @if ($showStartDots)
                        <span class="relative inline-flex items-center px-3 py-2 -ml-px text-sm text-gray-500 bg-white border border-gray-300 cursor-default select-none">&hellip;</span>
                    @endif

                    {{-- Halaman tengah --}}
                    @foreach ($middlePages as $page)
                        @if ($page === $currentPage)
                            <span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-semibold text-white bg-blue-600 border border-blue-600 cursor-default" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $paginator->url($page) }}" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition">{{ $page }}</a>
                        @endif
                    @endforeach

                    {{-- Titik akhir --}}
                    @if ($showEndDots)
                        <span class="relative inline-flex items-center px-3 py-2 -ml-px text-sm text-gray-500 bg-white border border-gray-300 cursor-default select-none">&hellip;</span>
                    @endif

                    {{-- Halaman terakhir --}}
                    @if ($currentPage === $lastPage)
                        <span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-semibold text-white bg-blue-600 border border-blue-600 cursor-default" aria-current="page">{{ $lastPage }}</span>
                    @else
                        <a href="{{ $paginator->url($lastPage) }}" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition">{{ $lastPage }}</a>
                    @endif

                @else
                    {{-- Semua halaman (≤5) --}}
                    @foreach ($middlePages as $page)
                        @if ($page === $currentPage)
                            <span class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-semibold text-white bg-blue-600 border border-blue-600 cursor-default" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $paginator->url($page) }}" class="relative inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Berikutnya"
                       class="relative inline-flex items-center px-2 py-2 -ml-px text-sm text-gray-500 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50 transition">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                    </a>
                @else
                    <span class="relative inline-flex items-center px-2 py-2 -ml-px text-sm text-gray-400 bg-white border border-gray-300 cursor-default rounded-r-md" aria-disabled="true">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                    </span>
                @endif

            </span>
        </div>
    </div>
</nav>
@endif
