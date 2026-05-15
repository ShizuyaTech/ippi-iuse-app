<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Production Order {{ $productionOrder->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; }
        .page { padding: 28px 32px; }
        .header { display: table; width: 100%; border-bottom: 2px solid #0f766e; padding-bottom: 10px; margin-bottom: 14px; }
        .header-left { display: table-cell; vertical-align: middle; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; }
        .company-name { font-size: 16px; font-weight: bold; color: #0f766e; }
        .company-sub { font-size: 8px; color: #555; margin-top: 1px; }
        .doc-title { font-size: 14px; font-weight: bold; color: #0f766e; }
        .order-number { font-size: 12px; font-weight: bold; font-family: monospace; color: #333; margin-top: 3px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 8px; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .badge-draft   { background: #e5e7eb; color: #374151; }
        .badge-released { background: #dbeafe; color: #1d4ed8; }
        .badge-in_progress { background: #fef9c3; color: #a16207; }
        .badge-completed { background: #dcfce7; color: #15803d; }
        .badge-cancelled { background: #fee2e2; color: #dc2626; }
        .info-grid { display: table; width: 100%; border: 1px solid #e5e7eb; border-radius: 3px; margin-bottom: 14px; }
        .info-row { display: table-row; }
        .info-cell { display: table-cell; padding: 6px 10px; vertical-align: top; width: 33%; border-right: 1px solid #f0f0f0; }
        .info-cell:last-child { border-right: none; }
        .info-label { font-size: 8px; color: #6b7280; text-transform: uppercase; margin-bottom: 2px; }
        .info-value { font-size: 10px; font-weight: bold; color: #111; }
        .section-title { font-size: 9px; font-weight: bold; color: #0f766e; text-transform: uppercase; margin-bottom: 5px; border-bottom: 1px solid #99f6e4; padding-bottom: 3px; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.items thead tr { background: #0f766e; color: #fff; }
        table.items thead th { padding: 5px 8px; text-align: left; font-size: 8.5px; text-transform: uppercase; }
        table.items thead th.right { text-align: right; }
        table.items tbody tr { border-bottom: 1px solid #e5e7eb; }
        table.items tbody tr:nth-child(even) { background: #f0fdfa; }
        table.items tbody td { padding: 5px 8px; font-size: 9.5px; }
        table.items tbody td.right { text-align: right; }
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
            <div class="doc-title">VENDOR PRODUCTION ORDER</div>
            <div class="order-number">{{ $productionOrder->order_number }}</div>
            <div style="margin-top:4px;">
                <span class="badge badge-{{ str_replace(' ','_',$productionOrder->status) }}">{{ ucfirst(str_replace('_',' ',$productionOrder->status)) }}</span>
            </div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">Material</div>
                <div class="info-value">{{ $productionOrder->material?->name ?? '-' }}</div>
                @if($productionOrder->material?->code)
                <div style="font-size:8px; color:#6b7280; font-family: monospace;">{{ $productionOrder->material->code }}</div>
                @endif
            </div>
            <div class="info-cell">
                <div class="info-label">Qty Order</div>
                <div class="info-value" style="color:#0f766e;">{{ fmt_qty($productionOrder->quantity) }} {{ $productionOrder->material?->unit_of_measure }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Qty OK / NG</div>
                <div class="info-value">
                    <span style="color:#15803d;">{{ fmt_qty($productionOrder->qty_ok ?? 0) }}</span>
                    / <span style="color:#dc2626;">{{ fmt_qty($productionOrder->qty_ng ?? 0) }}</span>
                    {{ $productionOrder->material?->unit_of_measure }}
                </div>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">PO Referensi</div>
                <div class="info-value">{{ $productionOrder->purchaseOrderItem?->purchaseOrder?->po_number ?? '-' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Tanggal Mulai</div>
                <div class="info-value">{{ $productionOrder->start_date?->format('d M Y') ?? '-' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Tanggal Selesai</div>
                <div class="info-value">{{ $productionOrder->end_date?->format('d M Y') ?? '-' }}</div>
            </div>
        </div>
        @if($productionOrder->notes)
        <div class="info-row">
            <div class="info-cell" style="width:100%; border-right:none;" colspan="3">
                <div class="info-label">Catatan</div>
                <div class="info-value" style="font-weight:normal;">{{ $productionOrder->notes }}</div>
            </div>
        </div>
        @endif
    </div>

    @if($productionOrder->reports && $productionOrder->reports->count())
    <div class="section-title">Laporan Produksi ({{ $productionOrder->reports->count() }} entri)</div>
    <table class="items">
        <thead>
            <tr>
                <th style="width:28px; text-align:center;">#</th>
                <th style="width:80px;">Tanggal</th>
                <th class="right" style="width:65px;">Qty OK</th>
                <th class="right" style="width:65px;">Qty NG</th>
                <th>Catatan</th>
                <th style="width:90px;">Dilaporkan Oleh</th>
            </tr>
        </thead>
        <tbody>
            @foreach($productionOrder->reports as $r)
            <tr>
                <td style="text-align:center; color:#9ca3af;">{{ $loop->iteration }}</td>
                <td style="font-size:9px;">{{ $r->reported_at?->format('d M Y') ?? $r->created_at?->format('d M Y') }}</td>
                <td class="right" style="color:#15803d; font-weight:bold;">{{ fmt_qty($r->qty_ok ?? 0) }}</td>
                <td class="right" style="color:#dc2626; font-weight:bold;">{{ fmt_qty($r->qty_ng ?? 0) }}</td>
                <td style="font-size:9px; color:#6b7280;">{{ $r->notes ?? '-' }}</td>
                <td style="font-size:9px;">{{ $r->createdBy?->name ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        Dicetak dari IPPI iUse System &mdash; {{ now()->format('d/m/Y H:i') }} &mdash; {{ $productionOrder->order_number }}
    </div>
</div>
</body>
</html>
