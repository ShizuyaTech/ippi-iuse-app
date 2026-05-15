<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Purchase Order {{ $purchaseOrder->po_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; }
        .page { padding: 28px 32px; }
        .header { display: table; width: 100%; border-bottom: 2px solid #0f766e; padding-bottom: 10px; margin-bottom: 14px; }
        .header-left { display: table-cell; vertical-align: middle; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; }
        .company-name { font-size: 16px; font-weight: bold; color: #0f766e; }
        .company-sub { font-size: 8px; color: #555; margin-top: 1px; }
        .doc-title { font-size: 15px; font-weight: bold; color: #0f766e; }
        .po-number { font-size: 12px; font-weight: bold; font-family: monospace; color: #333; margin-top: 3px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 8px; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .badge-approved { background: #dbeafe; color: #1d4ed8; }
        .badge-partially_received { background: #fef9c3; color: #a16207; }
        .badge-completed { background: #dcfce7; color: #15803d; }
        .badge-cancelled { background: #fee2e2; color: #dc2626; }
        .info-grid { display: table; width: 100%; border: 1px solid #e5e7eb; border-radius: 3px; margin-bottom: 14px; }
        .info-row { display: table-row; }
        .info-cell { display: table-cell; padding: 6px 10px; vertical-align: top; width: 33%; border-right: 1px solid #f0f0f0; }
        .info-cell:last-child { border-right: none; }
        .info-label { font-size: 8px; color: #6b7280; text-transform: uppercase; margin-bottom: 2px; }
        .info-value { font-size: 10px; font-weight: bold; color: #111; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.items thead tr { background: #0f766e; color: #fff; }
        table.items thead th { padding: 6px 8px; text-align: left; font-size: 9px; text-transform: uppercase; }
        table.items thead th.right { text-align: right; }
        table.items tbody tr { border-bottom: 1px solid #e5e7eb; }
        table.items tbody tr:nth-child(even) { background: #f0fdfa; }
        table.items tbody td { padding: 5px 8px; font-size: 10px; }
        table.items tbody td.right { text-align: right; }
        .mat-code { font-family: monospace; font-size: 9px; color: #0f766e; }
        .footer { margin-top: 16px; border-top: 1px solid #e5e7eb; padding-top: 6px; text-align: center; font-size: 8px; color: #9ca3af; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="header-left">
            <div class="company-name">IPPI</div>
            <div class="company-sub">Integrated Production &amp; Inventory System</div>
        </div>
        <div class="header-right">
            <div class="doc-title">PURCHASE ORDER</div>
            <div class="po-number">{{ $purchaseOrder->po_number }}</div>
            <div style="margin-top:4px;">
                <span class="badge badge-{{ $purchaseOrder->status }}">{{ ucfirst(str_replace('_',' ',$purchaseOrder->status)) }}</span>
            </div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">Vendor</div>
                <div class="info-value">{{ $purchaseOrder->vendor?->name ?? '-' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Tanggal Order</div>
                <div class="info-value">{{ $purchaseOrder->order_date?->format('d M Y') ?? '-' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Lokasi Penerimaan</div>
                <div class="info-value">{{ $purchaseOrder->storageLocation?->name ?? '-' }}</div>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">Dicetak</div>
                <div class="info-value">{{ now()->format('d M Y, H:i') }}</div>
            </div>
            <div class="info-cell" colspan="2">
                <div class="info-label">Catatan</div>
                <div class="info-value">{{ $purchaseOrder->notes ?? '-' }}</div>
            </div>
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width:28px; text-align:center;">#</th>
                <th style="width:90px;">Kode</th>
                <th>Nama Material</th>
                <th class="right" style="width:80px;">Qty PO</th>
                <th class="right" style="width:80px;">Qty Terima</th>
                <th class="right" style="width:60px;">Sisa</th>
                <th style="width:30px;">Satuan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchaseOrder->items as $i => $item)
            @php $sisa = $item->quantity - ($item->quantity_received ?? 0); @endphp
            <tr>
                <td style="text-align:center; color:#9ca3af;">{{ $i + 1 }}</td>
                <td><span class="mat-code">{{ $item->material?->code }}</span></td>
                <td>{{ $item->material?->name }}</td>
                <td class="right">{{ fmt_qty($item->quantity) }}</td>
                <td class="right">{{ fmt_qty($item->quantity_received ?? 0) }}</td>
                <td class="right" style="{{ $sisa > 0 ? 'color:#d97706; font-weight:bold;' : 'color:#15803d;' }}">{{ fmt_qty($sisa) }}</td>
                <td style="color:#6b7280;">{{ $item->material?->unit_of_measure }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak dari IPPI iUse System &mdash; {{ now()->format('d/m/Y H:i') }} &mdash; {{ $purchaseOrder->po_number }}
    </div>
</div>
</body>
</html>
