<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Daftar Production Order</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1a1a1a; }
        .page { padding: 22px 28px; }
        .header { display: table; width: 100%; border-bottom: 2px solid #0f766e; padding-bottom: 8px; margin-bottom: 12px; }
        .header-left { display: table-cell; vertical-align: middle; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; }
        .company-name { font-size: 14px; font-weight: bold; color: #0f766e; }
        .doc-title { font-size: 13px; font-weight: bold; color: #0f766e; }
        .doc-sub { font-size: 7.5px; color: #555; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: #0f766e; color: #fff; }
        thead th { padding: 5px 7px; text-align: left; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        thead th.right { text-align: right; }
        tbody tr { border-bottom: 1px solid #e5e7eb; }
        tbody tr:nth-child(even) { background: #f0fdfa; }
        tbody td { padding: 4px 7px; font-size: 8.5px; }
        .badge { display: inline-block; padding: 1px 5px; border-radius: 8px; font-size: 7px; font-weight: bold; }
        .badge-draft       { background: #e5e7eb; color: #374151; }
        .badge-released    { background: #dbeafe; color: #1d4ed8; }
        .badge-in_progress { background: #fef9c3; color: #a16207; }
        .badge-completed   { background: #dcfce7; color: #15803d; }
        .badge-cancelled   { background: #fee2e2; color: #dc2626; }
        .footer { margin-top: 12px; border-top: 1px solid #e5e7eb; padding-top: 5px; text-align: center; font-size: 7.5px; color: #9ca3af; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="header-left">
            <div class="company-name">IPPI</div>
            <div style="font-size:7.5px; color:#555;">Integrated Production &amp; Inventory System</div>
        </div>
        <div class="header-right">
            <div class="doc-title">DAFTAR VENDOR PRODUCTION ORDER</div>
            <div class="doc-sub">Dicetak: {{ now()->format('d M Y, H:i') }}
                @if($filters['search'] ?? '') &nbsp;|&nbsp; Cari: <strong>{{ $filters['search'] }}</strong> @endif
                @if($filters['status'] ?? '') &nbsp;|&nbsp; Status: <strong>{{ strtoupper($filters['status']) }}</strong> @endif
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:22px; text-align:center;">#</th>
                <th style="width:95px;">No. Order</th>
                <th style="width:80px;">Referensi PO</th>
                <th>Material</th>
                <th style="width:65px;">Kode</th>
                <th class="right" style="width:55px;">Planned</th>
                <th class="right" style="width:42px;">OK</th>
                <th class="right" style="width:42px;">NG</th>
                <th style="width:68px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $o)
            <tr>
                <td style="text-align:center; color:#9ca3af;">{{ $loop->iteration }}</td>
                <td style="font-family: monospace; font-size:8px; color:#0f766e; font-weight:bold;">{{ $o->order_number }}</td>
                <td style="font-family: monospace; font-size:8px; color:#6b7280;">{{ $o->purchaseOrderItem?->purchaseOrder?->po_number ?? '-' }}</td>
                <td>{{ $o->material?->name ?? '-' }}</td>
                <td style="font-family: monospace; font-size:8px; color:#6b7280;">{{ $o->material?->code ?? '-' }}</td>
                <td style="text-align:right; font-weight:bold; color:#0f766e;">{{ fmt_qty($o->quantity) }}</td>
                <td style="text-align:right; color:#15803d; font-weight:bold;">{{ fmt_qty($o->qty_ok ?? 0) }}</td>
                <td style="text-align:right; color:#dc2626; font-weight:bold;">{{ fmt_qty($o->qty_ng ?? 0) }}</td>
                <td><span class="badge badge-{{ str_replace(' ','_',$o->status) }}">{{ ucfirst(str_replace('_',' ',$o->status)) }}</span></td>
            </tr>
            @empty
            <tr><td colspan="9" style="text-align:center; padding:12px; color:#9ca3af;">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak dari IPPI iUse System &mdash; {{ now()->format('d/m/Y H:i') }} &mdash; Total: {{ $orders->count() }} order
    </div>
</div>
</body>
</html>
