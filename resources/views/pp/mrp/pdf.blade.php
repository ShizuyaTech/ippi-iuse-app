<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Hasil MRP Run — {{ $mrpRun->created_at->format('d M Y H:i') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8pt; color: #1a1a1a; }
        .header { margin-bottom: 10px; border-bottom: 2px solid #1e3a5f; padding-bottom: 6px; }
        .header h1 { font-size: 13pt; font-weight: bold; color: #1e3a5f; }
        .header p { font-size: 8pt; color: #555; margin-top: 2px; }
        .meta { display: flex; gap: 30px; font-size: 8pt; color: #444; margin-bottom: 10px; }
        .meta span b { color: #1e3a5f; }
        .summary { display: flex; gap: 16px; margin-bottom: 12px; }
        .summary-card { flex: 1; border: 1px solid #ddd; border-radius: 4px; padding: 6px 10px; text-align: center; }
        .summary-card .val { font-size: 16pt; font-weight: bold; }
        .summary-card .lbl { font-size: 7pt; color: #666; }
        .blue { color: #1e3a5f; }
        .red { color: #c0392b; }
        .yellow { color: #d97706; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        thead tr { background-color: #1e3a5f; color: #fff; }
        thead th { padding: 5px 4px; text-align: right; font-size: 7.5pt; font-weight: bold; border: 1px solid #1e3a5f; }
        thead th:first-child, thead th:nth-child(2) { text-align: left; }
        tbody tr:nth-child(even) { background-color: #f4f7fb; }
        tbody tr.purchase { background-color: #fff5f5; }
        tbody td { padding: 4px 4px; font-size: 7.5pt; border: 1px solid #e0e0e0; text-align: right; vertical-align: middle; }
        tbody td:first-child { text-align: left; font-family: DejaVu Sans Mono, monospace; font-weight: bold; color: #1e3a5f; }
        tbody td:nth-child(2) { text-align: left; }
        .badge-po { background: #fee2e2; color: #b91c1c; border-radius: 3px; padding: 1px 5px; font-size: 7pt; font-weight: bold; }
        .badge-prod { background: #fef9c3; color: #92400e; border-radius: 3px; padding: 1px 5px; font-size: 7pt; font-weight: bold; }
        .order-qty { font-size: 10pt; font-weight: bold; color: #1e3a5f; }
        .note { font-size: 7pt; color: #888; margin-top: 10px; border-top: 1px solid #eee; padding-top: 4px; }
        .footer { margin-top: 14px; font-size: 7pt; color: #aaa; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Hasil MRP Run &mdash; {{ $mrpRun->created_at->format('d F Y, H:i') }}</h1>
        <p>Dijalankan oleh: <b>{{ $mrpRun->runBy->name ?? '-' }}</b> &nbsp;|&nbsp; Total: <b>{{ $results->count() }}</b> material</p>
    </div>

    <div class="summary">
        <div class="summary-card">
            <div class="val blue">{{ $results->count() }}</div>
            <div class="lbl">Total Material</div>
        </div>
        <div class="summary-card">
            <div class="val red">{{ $results->where('recommendation_type','purchase')->count() }}</div>
            <div class="lbl">Perlu Buat PO</div>
        </div>
        <div class="summary-card">
            <div class="val yellow">{{ $results->where('recommendation_type','production')->count() }}</div>
            <div class="lbl">Perlu Produksi</div>
        </div>
        <div class="summary-card">
            <div class="val blue">{{ $results->where('net_requirement',0)->count() }}</div>
            <div class="lbl">Stok Cukup</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="text-align:left">Kode</th>
                <th style="text-align:left">Nama Material</th>
                <th>Satuan</th>
                <th>Gross Req.</th>
                <th>Stok*</th>
                <th>Sisa PO</th>
                <th>Net Req.</th>
                <th>Safety 20%</th>
                <th>Total+Safety</th>
                <th>Qty/Case</th>
                <th>Order Vendor</th>
                <th>Rek.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($results as $r)
            @php $withSafety = (float)$r->net_requirement + (float)$r->safety_stock_qty; @endphp
            <tr class="{{ $r->recommendation_type === 'purchase' ? 'purchase' : '' }}">
                <td>{{ $r->material->code ?? '-' }}</td>
                <td style="text-align:left; font-family: inherit; font-weight:normal; color:#1a1a1a;">{{ $r->material->name ?? '-' }}</td>
                <td>{{ $r->material->unit_of_measure ?? '-' }}</td>
                <td>{{ number_format($r->gross_requirement, 2) }}</td>
                <td style="{{ (float)$r->current_stock < (float)$r->gross_requirement ? 'color:#c0392b' : 'color:#16a34a' }}">{{ number_format($r->current_stock, 2) }}</td>
                <td style="color:#16a34a">{{ (float)$r->open_po_qty > 0 ? number_format($r->open_po_qty, 2) : '-' }}</td>
                <td style="font-weight:bold">{{ number_format($r->net_requirement, 2) }}</td>
                <td style="color:#d97706">+{{ number_format($r->safety_stock_qty, 2) }}</td>
                <td style="color:#1e3a5f; font-weight:bold">{{ number_format($withSafety, 2) }}</td>
                <td style="color:#666">{{ (float)$r->qty_per_case > 0 ? number_format($r->qty_per_case, 0) : '-' }}</td>
                <td class="order-qty">{{ number_format($r->recommended_quantity, 0) }}</td>
                <td style="text-align:center">
                    @if($r->recommendation_type === 'purchase')
                        <span class="badge-po">Buat PO</span>
                    @else
                        <span class="badge-prod">Produksi</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="note">
        * Stok Tersedia = Stok RM aktual + Stok FP/WIP dikonversi ke RM via BOM. Stok lokasi scrap tidak dihitung.<br>
        Formula: Net = Gross &minus; Stok Tersedia &minus; Sisa PO &nbsp;&rarr;&nbsp; +Safety 20% &nbsp;&rarr;&nbsp; Order = round-up ke Qty/Case
    </div>
    <div class="footer">Dicetak: {{ now()->format('d M Y H:i') }}</div>
</body>
</html>
