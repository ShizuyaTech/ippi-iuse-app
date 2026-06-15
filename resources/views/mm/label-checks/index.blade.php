<x-app-layout>
    <x-slot name="title">Label Check — Traceability</x-slot>

    <div class="space-y-5">

        {{-- ── Scan Panel ── --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center gap-2 mb-1">
                <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                <h2 class="text-lg font-semibold text-gray-700">Pengecekan Label (Traceability)</h2>
            </div>
            <p class="text-xs text-gray-500 mb-4">Scan label part hasil produksi dan label order customer. Setiap pengecekan dicatat untuk keperluan traceability saat ada masalah di customer.</p>

            {{-- Result Banner --}}
            <div id="result-banner" class="hidden rounded-lg px-4 py-4 mb-4 flex items-start gap-3 text-sm font-medium"></div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1 uppercase tracking-wide">Label Part (Hasil Produksi)</label>
                    <input type="text" id="inp-part"
                           placeholder="Scan atau ketik label part..."
                           autocomplete="off"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-orange-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1 uppercase tracking-wide">Label Order Customer</label>
                    <input type="text" id="inp-customer"
                           placeholder="Scan atau ketik label order customer..."
                           autocomplete="off"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-orange-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1 uppercase tracking-wide">No. Referensi (opsional)</label>
                    <input type="text" id="inp-ref"
                           placeholder="No. GI / No. PO Customer..."
                           autocomplete="off"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400">
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3 mb-1">
                <button onclick="doCheck()" id="btn-check"
                        class="bg-orange-600 text-white px-5 py-2 rounded text-sm font-semibold hover:bg-orange-700 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Cek Label
                </button>
                <button onclick="resetForm()" class="text-sm text-gray-400 hover:text-gray-600 underline">Reset</button>
                <span id="spinner" class="hidden text-xs text-gray-400">Menyimpan...</span>
            </div>
            <p class="text-xs text-gray-400">Tip: Tekan <kbd class="border border-gray-300 rounded px-1 py-0.5 text-xs bg-gray-50">Enter</kbd> setelah scan untuk langsung melakukan pengecekan.</p>
        </div>

        {{-- ── Log / Riwayat ── --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex flex-wrap justify-between items-center gap-2 mb-4">
                <h2 class="text-base font-semibold text-gray-700">Riwayat Pengecekan</h2>
                <form method="GET" class="flex flex-wrap gap-2">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Cari label / ref dokumen..."
                           class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm flex-1 min-w-48">
                    <select name="result" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                        <option value="">Semua Hasil</option>
                        <option value="ok" {{ request('result')==='ok' ? 'selected' : '' }}>OK</option>
                        <option value="ng" {{ request('result')==='ng' ? 'selected' : '' }}>NG</option>
                    </select>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" title="Dari tanggal"
                           class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" title="Sampai tanggal"
                           class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm">
                    <button type="submit" class="bg-gray-600 text-white px-4 py-1.5 rounded text-sm">Filter</button>
                    <a href="{{ route('mm.label-checks.index') }}" class="bg-gray-100 text-gray-600 px-4 py-1.5 rounded text-sm border hover:bg-gray-200">Reset</a>
                </form>
            </div>

            @if(session('success'))
            <div class="mb-3 bg-green-50 border border-green-200 text-green-800 px-4 py-2 rounded text-sm">{{ session('success') }}</div>
            @endif

            <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead class="bg-gray-100 text-gray-600 text-xs uppercase tracking-wide">
                    <tr>
                        <th class="px-3 py-2 text-center w-16">Hasil</th>
                        <th class="px-3 py-2 text-left">Label Part</th>
                        <th class="px-3 py-2 text-left">Label Order Customer</th>
                        <th class="px-3 py-2 text-left">Ref. Dokumen</th>
                        <th class="px-3 py-2 text-left">Catatan</th>
                        <th class="px-3 py-2 text-left">Waktu</th>
                        <th class="px-3 py-2 text-left">Dicek Oleh</th>
                        <th class="px-3 py-2 w-10"></th>
                    </tr>
                </thead>
                <tbody id="log-table-body">
                    @forelse($checks as $c)
                    <tr class="border-b hover:bg-gray-50 {{ $c->result === 'ng' ? 'bg-red-50' : '' }}">
                        <td class="px-3 py-2 text-center">
                            @if($c->result === 'ok')
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold bg-green-100 text-green-700">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>OK
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-700">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>NG
                            </span>
                            @endif
                        </td>
                        <td class="px-3 py-2 font-mono text-xs max-w-xs truncate" title="{{ $c->part_label }}">{{ $c->part_label }}</td>
                        <td class="px-3 py-2 font-mono text-xs max-w-xs truncate" title="{{ $c->customer_label }}">{{ $c->customer_label }}</td>
                        <td class="px-3 py-2 text-xs text-gray-500">{{ $c->reference_doc ?? '—' }}</td>
                        <td class="px-3 py-2 text-xs text-gray-500">{{ $c->notes ?? '—' }}</td>
                        <td class="px-3 py-2 text-xs whitespace-nowrap">{{ $c->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="px-3 py-2 text-xs text-gray-600">{{ $c->checkedBy?->name ?? '—' }}</td>
                        <td class="px-3 py-2 text-center">
                            <form method="POST" action="{{ route('mm.label-checks.destroy', $c) }}"
                                  onsubmit="return confirm('Hapus record ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-400 hover:text-red-600" title="Hapus">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-gray-400">Belum ada riwayat pengecekan label.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
            <div class="mt-4">{{ $checks->links() }}</div>
        </div>
    </div>

    <script>
        const STORE_URL = "{{ route('mm.label-checks.store') }}";
        const CSRF      = "{{ csrf_token() }}";

        function norm(val) { return val.trim().replace(/\s+/g, ' ').toUpperCase(); }

        async function doCheck() {
            const part     = norm(document.getElementById('inp-part').value);
            const customer = norm(document.getElementById('inp-customer').value);
            const ref      = document.getElementById('inp-ref').value.trim();
            const banner   = document.getElementById('result-banner');
            const spinner  = document.getElementById('spinner');

            if (!part || !customer) {
                showBanner('warn', 'Isi kedua label terlebih dahulu.');
                return;
            }

            spinner.classList.remove('hidden');
            document.getElementById('btn-check').disabled = true;

            try {
                const res = await fetch(STORE_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({ part_label: part, customer_label: customer, reference_doc: ref }),
                });
                const data = await res.json();

                if (data.result === 'ok') {
                    showBanner('ok',
                        `<strong>OK</strong> — Label sesuai. Dicatat pada ${data.checked_at} oleh ${data.checked_by}.`);
                } else {
                    showBanner('ng',
                        `<strong>NG</strong> — Label <u>tidak sesuai</u>. <strong>Periksa kembali label</strong> sebelum delivery. Dicatat pada ${data.checked_at} oleh ${data.checked_by}.`);
                }

                // Prepend new row to table
                prependRow(data, part, customer, ref);

            } catch (e) {
                showBanner('warn', 'Terjadi kesalahan saat menyimpan. Periksa koneksi.');
            } finally {
                spinner.classList.add('hidden');
                document.getElementById('btn-check').disabled = false;
            }
        }

        function showBanner(type, html) {
            const banner = document.getElementById('result-banner');
            const styles = {
                ok:   'bg-green-50 border border-green-400 text-green-700',
                ng:   'bg-red-50 border border-red-400 text-red-700',
                warn: 'bg-yellow-50 border border-yellow-300 text-yellow-700',
            };
            const icons = {
                ok:   '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                ng:   '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                warn: '<svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/></svg>',
            };
            banner.className = `rounded-lg px-4 py-4 mb-4 flex items-start gap-3 text-sm font-medium ${styles[type]}`;
            banner.innerHTML = `${icons[type]}<span>${html}</span>`;
            banner.classList.remove('hidden');
            banner.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function prependRow(data, part, customer, ref) {
            const tbody = document.getElementById('log-table-body');
            const isOk  = data.result === 'ok';
            const tr    = document.createElement('tr');
            tr.className = `border-b ${isOk ? '' : 'bg-red-50'}`;
            tr.innerHTML = `
                <td class="px-3 py-2 text-center">
                    ${isOk
                        ? `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold bg-green-100 text-green-700"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>OK</span>`
                        : `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-700"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>NG</span>`
                    }
                </td>
                <td class="px-3 py-2 font-mono text-xs max-w-xs truncate" title="${part}">${part}</td>
                <td class="px-3 py-2 font-mono text-xs max-w-xs truncate" title="${customer}">${customer}</td>
                <td class="px-3 py-2 text-xs text-gray-500">${ref || '—'}</td>
                <td class="px-3 py-2 text-xs text-gray-500">—</td>
                <td class="px-3 py-2 text-xs whitespace-nowrap">${data.checked_at}</td>
                <td class="px-3 py-2 text-xs text-gray-600">${data.checked_by}</td>
                <td class="px-3 py-2"></td>
            `;
            tbody.insertBefore(tr, tbody.firstChild);

            // Remove "no data" row if present
            const empty = tbody.querySelector('td[colspan]');
            if (empty) empty.closest('tr').remove();
        }

        function resetForm() {
            ['inp-part','inp-customer','inp-ref'].forEach(id => document.getElementById(id).value = '');
            const banner = document.getElementById('result-banner');
            banner.classList.add('hidden');
            document.getElementById('inp-part').focus();
        }

        // Enter on any input triggers check
        ['inp-part','inp-customer','inp-ref'].forEach(id => {
            document.getElementById(id)?.addEventListener('keydown', e => {
                if (e.key === 'Enter') { e.preventDefault(); doCheck(); }
            });
        });

        // Auto-focus part input on load
        document.getElementById('inp-part')?.focus();
    </script>
</x-app-layout>
