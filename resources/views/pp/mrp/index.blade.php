<x-app-layout>
    <x-slot name="title">MRP - Material Requirements Planning</x-slot>
    <div class="space-y-6">

        {{-- â”€â”€ Demand Order Customer â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-700">Demand Order Customer</h2>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Import file Excel berisi daftar order FP/WIP dari customer. MRP akan mengeksplosi secara multi-level ke bahan baku (RM) via BOM.
                    </p>
                </div>
                @if($demands->isNotEmpty())
                <form method="POST" action="{{ route('pp.mrp.demands.clear') }}" onsubmit="return confirm('Hapus semua demand aktif?')">
                    @csrf @method('DELETE')
                    <button class="text-sm text-red-600 hover:underline whitespace-nowrap ml-4">Hapus Semua ({{ $demands->count() }})</button>
                </form>
                @endif
            </div>

            {{-- Import form --}}
            <div class="p-4 bg-blue-50 border border-blue-200 rounded mb-4">
                <div class="flex flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-[280px]">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Upload File Excel Demand (.xlsx / .xls)</label>
                        <form method="POST" action="{{ route('pp.mrp.demands.import') }}" enctype="multipart/form-data"
                              class="flex gap-2 items-center">
                            @csrf
                            <input type="file" name="file" accept=".xlsx,.xls" required
                                   class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm bg-white">
                            <button type="submit"
                                    class="bg-blue-700 text-white px-4 py-2 rounded text-sm hover:bg-blue-800 whitespace-nowrap">
                                Import
                            </button>
                        </form>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-400 mb-1">Belum punya template?</label>
                        <a href="{{ route('pp.mrp.demands.template') }}"
                           class="inline-block border border-blue-600 text-blue-700 px-4 py-2 rounded text-sm hover:bg-blue-700 hover:text-white transition">
                            Unduh Template
                        </a>
                    </div>
                </div>
                <p class="text-xs text-blue-600 mt-2">
                    Format: <b>Kolom A</b> = Kode Material FP/WIP &nbsp;|&nbsp;
                    <b>Kolom B</b> = Qty Order &nbsp;|&nbsp;
                    <b>Kolom C</b> = Customer (opsional) &nbsp;|&nbsp;
                    <b>Kolom D</b> = Notes (opsional). Baris 1 = header (dilewati otomatis).
                </p>
            </div>

            {{-- Demand table --}}
            @if($demands->isEmpty())
            <p class="text-sm text-gray-400 italic text-center py-4">Belum ada demand. Upload file Excel di atas.</p>
            @else
            <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100 text-gray-600 text-xs">
                    <tr>
                        <th class="px-3 py-2 text-left">#</th>
                        <th class="px-3 py-2 text-left">Kode</th>
                        <th class="px-3 py-2 text-left">Nama Material</th>
                        <th class="px-3 py-2 text-center">Tipe</th>
                        <th class="px-3 py-2 text-right">Qty Order</th>
                        <th class="px-3 py-2 text-left">Customer</th>
                        <th class="px-3 py-2 text-left">Catatan</th>
                        <th class="px-3 py-2 w-14"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($demands as $i => $d)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-3 py-1.5 text-gray-400 text-xs">{{ $i + 1 }}</td>
                        <td class="px-3 py-1.5 font-mono text-xs text-blue-700 font-semibold">{{ $d->material->code }}</td>
                        <td class="px-3 py-1.5 text-gray-700">{{ $d->material->name }}</td>
                        <td class="px-3 py-1.5 text-center">
                            <span class="px-1.5 py-0.5 rounded text-xs font-medium
                                {{ $d->material->type === 'FP' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                {{ $d->material->type }}
                            </span>
                        </td>
                        <td class="px-3 py-1.5 text-right font-medium">{{ number_format($d->order_quantity, 3) }}</td>
                        <td class="px-3 py-1.5 text-gray-600 text-xs">{{ $d->customer_name ?? '-' }}</td>
                        <td class="px-3 py-1.5 text-gray-400 text-xs">{{ $d->notes ?? '-' }}</td>
                        <td class="px-3 py-1.5 text-center">
                            <form method="POST" action="{{ route('pp.mrp.demands.destroy', $d) }}"
                                  onsubmit="return confirm('Hapus demand ini?')">
                                @csrf @method('DELETE')
                                <button class="text-red-500 hover:text-red-700 text-xs">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            @endif
        </div>

        {{-- â”€â”€ Run MRP â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-lg font-semibold text-gray-700">Jalankan MRP</h2>
                    <p class="text-xs text-gray-400 mt-1">
                        Formula: <b>Gross</b> = BOM explosion multi-level (FPâ†’WIPâ†’RM)
                        &rarr; <b>Net</b> = Gross &minus; Stok &minus; Sisa PO (approved/partial)
                        &rarr; <b>+Safety 20%</b>
                        &rarr; <b>Order</b> = round-up ke Qty/Case.
                    </p>
                </div>
                <form method="POST" action="{{ route('pp.mrp.run') }}" onsubmit="return confirm('Jalankan MRP Run sekarang dengan {{ $demands->count() }} demand?')">
                    @csrf
                    <button class="bg-blue-700 text-white px-6 py-2 rounded text-sm hover:bg-blue-800 disabled:opacity-50"
                            @disabled($demands->isEmpty())>
                        Jalankan MRP {{ $demands->isNotEmpty() ? '('.$demands->count().' item)' : '' }}
                    </button>
                </form>
            </div>
        </div>

        {{-- â”€â”€ Riwayat â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-700 mb-3">Riwayat MRP Run</h3>
            <table class="w-full text-sm border-collapse">
                <thead class="bg-blue-900 text-white">
                    <tr>
                        <th class="px-4 py-2 text-left">Tanggal Run</th>
                        <th class="px-4 py-2 text-right">Jml Hasil</th>
                        <th class="px-4 py-2 text-left">Dijalankan oleh</th>
                        <th class="px-4 py-2 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($runs as $run)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-2">{{ $run->created_at->format('d M Y H:i') }}</td>
                        <td class="px-4 py-2 text-right font-medium">{{ $run->results->count() }} material</td>
                        <td class="px-4 py-2">{{ $run->runBy->name ?? '-' }}</td>
                        <td class="px-4 py-2 text-center">
                            <div class="flex justify-center gap-3">
                                <a href="{{ route('pp.mrp.show', $run) }}" class="text-blue-600 hover:underline text-sm">Lihat Hasil</a>
                                <form method="POST" action="{{ route('pp.mrp.destroy', $run) }}"
                                      onsubmit="return confirm('Hapus MRP Run {{ $run->created_at->format('d M Y H:i') }}? Semua hasil akan dihapus.')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:text-red-700 text-sm">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-4 py-4 text-center text-gray-400">Belum ada MRP Run.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4">{{ $runs->links() }}</div>
        </div>
    </div>
</x-app-layout>
