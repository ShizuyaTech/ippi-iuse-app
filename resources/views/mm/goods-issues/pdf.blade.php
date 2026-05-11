<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Goods Issue {{ $goodsIssue->gi_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; background: #fff; }
        .page { padding: 28px 34px; }

        /* Header */
        .header { display: table; width: 100%; border-bottom: 2px solid #ea580c; padding-bottom: 12px; margin-bottom: 16px; }
        .header-left  { display: table-cell; vertical-align: middle; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; }
        .company-name { font-size: 16px; font-weight: bold; color: #ea580c; }
        .company-sub  { font-size: 8px; color: #6b7280; margin-top: 2px; }
        .doc-title    { font-size: 18px; font-weight: bold; color: #ea580c; letter-spacing: 1px; }
        .gi-number    { font-size: 13px; font-weight: bold; font-family: monospace; color: #374151; margin-top: 3px; }

        /* Status badge */
        .badge { display: inline-block; padding: 2px 10px; border-radius: 10px; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .badge-posted   { background: #dcfce7; color: #15803d; }
        .badge-reversed { background: #fee2e2; color: #dc2626; }

        /* Type badge */
        .type-internal   { background: #f3f4f6; color: #374151; }
        .type-to_vendor  { background: #dbeafe; color: #1d4ed8; }
        .type-to_customer { background: #dcfce7; color: #15803d; }

        /* Info grid */
        .info-section { border: 1px solid #e5e7eb; border-radius: 4px; margin-bottom: 14px; }
        .info-row { display: table; width: 100%; border-bottom: 1px solid #f3f4f6; }
        .info-row:last-child { border-bottom: none; }
        .info-cell { display: table-cell; padding: 7px 12px; vertical-align: top; width: 50%; border-right: 1px solid #f3f4f6; }
        .info-cell:last-child { border-right: none; }
        .info-label { font-size: 8px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px; }
        .info-value { font-size: 10px; font-weight: bold; color: #111; }

        /* Destination box (vendor / customer) */
        .dest-box { border-radius: 4px; padding: 9px 12px; margin-bottom: 14px; }
        .dest-box.vendor   { background: #eff6ff; border: 1px solid #bfdbfe; }
        .dest-box.customer { background: #f0fdf4; border: 1px solid #bbf7d0; }
        .dest-title { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px; }
        .dest-title.vendor   { color: #1d4ed8; }
        .dest-title.customer { color: #15803d; }
        .dest-name { font-size: 12px; font-weight: bold; }

        /* Items table */
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        thead tr { background: #ea580c; color: #fff; }
        thead th { padding: 6px 8px; text-align: left; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3px; }
        thead th.right  { text-align: right; }
        thead th.center { text-align: center; }
        tbody tr { border-bottom: 1px solid #e5e7eb; }
        tbody tr:nth-child(even) { background: #fff7ed; }
        tbody td { padding: 6px 8px; font-size: 10px; vertical-align: middle; }
        tbody td.right  { text-align: right; }
        tbody td.center { text-align: center; }
        .mat-code { font-family: monospace; font-size: 9px; color: #1d4ed8; }
        .note-chip { display: inline-block; background: #fef9c3; border: 1px solid #fde68a; color: #92400e; font-family: monospace; font-size: 9px; padding: 1px 5px; border-radius: 3px; }

        /* Total row */
        .tfoot-row td { background: #fff7ed; font-weight: bold; border-top: 2px solid #ea580c; font-size: 10px; }

        /* Notes box */
        .notes-box { border: 1px solid #e5e7eb; border-radius: 4px; padding: 8px 12px; background: #fffbeb; margin-bottom: 16px; }
        .notes-label { font-size: 8px; color: #92400e; text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold; margin-bottom: 3px; }
        .notes-text  { font-size: 10px; color: #374151; }

        /* Signature */
        .sig-section { display: table; width: 100%; margin-top: 20px; border-top: 1px solid #e5e7eb; padding-top: 14px; }
        .sig-col { display: table-cell; width: 33.33%; text-align: center; padding: 0 8px; }
        .sig-title { font-size: 8px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 38px; }
        .sig-line { border-top: 1px solid #374151; padding-top: 4px; }
        .sig-name { font-size: 10px; font-weight: bold; }
        .sig-role { font-size: 8px; color: #6b7280; }

        /* Footer */
        .footer { margin-top: 16px; border-top: 1px solid #f3f4f6; padding-top: 6px; text-align: center; font-size: 8px; color: #9ca3af; }
    </style>
</head>
<body>
<div class="page">

    {{-- ===== HEADER ===== --}}
    <div class="header">
        <div class="header-left">
            <div class="company-name">IPPI</div>
            <div class="company-sub">Integrated Production &amp; Inventory System</div>
        </div>
        <div class="header-right">
            <div class="doc-title">GOODS ISSUE</div>
            <div class="gi-number">{{ $goodsIssue->gi_number }}</div>
            <div style="margin-top:4px;">
                <span class="badge {{ 'badge-' . ($goodsIssue->status ?? 'posted') }}">
                    {{ strtoupper($goodsIssue->status ?? 'posted') }}
                </span>
            </div>
        </div>
    </div>

    {{-- ===== INFO GRID ===== --}}
    @php
        $typeLabel = ['internal' => 'Pemakaian Internal', 'to_vendor' => 'Kirim ke Vendor (Proses)', 'to_customer' => 'Kirim ke Customer'];
        $t = $goodsIssue->issue_type ?? 'internal';
    @endphp
    <div class="info-section">
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">Tanggal Issue</div>
                <div class="info-value">{{ $goodsIssue->issue_date->format('d F Y') }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Dari Storage Location</div>
                <div class="info-value">
                    {{ $goodsIssue->storageLocation->code ?? '-' }} — {{ $goodsIssue->storageLocation->name ?? '-' }}
                </div>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">Tipe Issue</div>
                <div class="info-value">
                    <span class="badge {{ 'type-' . $t }}">{{ $typeLabel[$t] ?? $t }}</span>
                </div>
            </div>
            <div class="info-cell">
                <div class="info-label">Dibuat oleh</div>
                <div class="info-value">{{ $goodsIssue->createdBy->name ?? '-' }}</div>
            </div>
        </div>
    </div>

    {{-- ===== DESTINATION BOX ===== --}}
    @if($goodsIssue->destination_name)
    @if($t === 'to_vendor')
    <div class="dest-box vendor">
        <div class="dest-title vendor">&#9656; Dikirim ke Vendor</div>
        <div class="dest-name" style="color:#1e3a8a;">{{ $goodsIssue->destination_name }}</div>
        <div style="font-size:9px;color:#374151;margin-top:3px;">Material akan diproses oleh vendor lalu dikembalikan via Goods Receipt.</div>
    </div>
    @else
    <div class="dest-box customer">
        <div class="dest-title customer">&#9656; Dikirim ke Customer</div>
        <div class="dest-name" style="color:#14532d;">{{ $goodsIssue->destination_name }}</div>
    </div>
    @endif
    @endif

    {{-- ===== ITEMS TABLE ===== --}}
    @php $totalQty = $goodsIssue->items->sum('quantity_issued'); @endphp
    <table>
        <thead>
            <tr>
                <th style="width:28px;" class="center">#</th>
                <th>Kode Material</th>
                <th>Nama Material</th>
                <th style="width:40px;">UoM</th>
                <th class="right" style="width:72px;">Qty Keluar</th>
                <th style="width:130px;">Note / ID Packing</th>
            </tr>
        </thead>
        <tbody>
            @foreach($goodsIssue->items as $i => $item)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td><span class="mat-code">{{ $item->material->code ?? '-' }}</span></td>
                <td>{{ $item->material->name ?? '-' }}</td>
                <td class="center">{{ $item->material->unit_of_measure ?? '-' }}</td>
                <td class="right" style="font-weight:bold;color:#c2410c;">{{ number_format($item->quantity_issued, 3) }}</td>
                <td>
                    @if($item->note)
                    <span class="note-chip">{{ $item->note }}</span>
                    @else
                    <span style="color:#d1d5db;">—</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="tfoot-row">
                <td colspan="4" style="text-align:right;padding-right:8px;">Total Qty Keluar:</td>
                <td class="right">{{ number_format($totalQty, 3) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    {{-- ===== NOTES ===== --}}
    @if($goodsIssue->notes)
    <div class="notes-box">
        <div class="notes-label">Keterangan</div>
        <div class="notes-text">{{ $goodsIssue->notes }}</div>
    </div>
    @endif

    {{-- ===== SIGNATURE ===== --}}
    <div class="sig-section">
        <div class="sig-col">
            <div class="sig-title">Dikeluarkan oleh</div>
            <div class="sig-line">
                <div class="sig-name">{{ $goodsIssue->createdBy->name ?? '___________________' }}</div>
                <div class="sig-role">Warehouse / Inventory</div>
            </div>
        </div>
        <div class="sig-col">
            <div class="sig-title">Diperiksa oleh</div>
            <div class="sig-line">
                <div class="sig-name">___________________</div>
                <div class="sig-role">Supervisor</div>
            </div>
        </div>
        <div class="sig-col">
            <div class="sig-title">Disetujui oleh</div>
            <div class="sig-line">
                <div class="sig-name">___________________</div>
                <div class="sig-role">Manager</div>
            </div>
        </div>
    </div>

    {{-- ===== FOOTER ===== --}}
    <div class="footer">
        Dicetak pada: {{ user_now()->format('d M Y H:i') }} {{ user_tz_label() }} &nbsp;|&nbsp; {{ $goodsIssue->gi_number }} &nbsp;|&nbsp; IPPI - Integrated Production &amp; Inventory System
    </div>

</div>
</body>
</html>
