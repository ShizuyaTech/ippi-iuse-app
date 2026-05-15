<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Daftar Surat Jalan</title>
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
        tbody tr { border-bottom: 1px solid #e5e7eb; }
        tbody tr:nth-child(even) { background: #f0fdfa; }
        tbody td { padding: 4px 7px; font-size: 8.5px; }
        .badge { display: inline-block; padding: 1px 5px; border-radius: 8px; font-size: 7px; font-weight: bold; }
        .badge-pending   { background: #fef9c3; color: #a16207; }
        .badge-sent      { background: #dbeafe; color: #1d4ed8; }
        .badge-received  { background: #dcfce7; color: #15803d; }
        .badge-confirmed { background: #f0fdf4; color: #166534; }
        .badge-cancelled { background: #fee2e2; color: #dc2626; }
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
            <div class="doc-title">DAFTAR SURAT JALAN – VENDOR</div>
            <div class="doc-sub">Dicetak: {{ now()->format('d M Y, H:i') }}
                @if($filters['search'] ?? '') &nbsp;|&nbsp; Cari: <strong>{{ $filters['search'] }}</strong> @endif
                @if($filters['status'] ?? '') &nbsp;|&nbsp; Status: <strong>{{ ucfirst($filters['status']) }}</strong> @endif
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:22px; text-align:center;">#</th>
                <th style="width:100px;">No. SJ</th>
                <th style="width:90px;">Referensi PO</th>
                <th style="width:75px;">Est. Pengiriman</th>
                <th>No. Kendaraan</th>
                <th>Driver</th>
                <th style="width:70px;">Status</th>
                <th style="width:70px;">Dibuat</th>
            </tr>
        </thead>
        <tbody>
            @forelse($deliveryNotes as $dn)
            <tr>
                <td style="text-align:center; color:#9ca3af;">{{ $loop->iteration }}</td>
                <td style="font-family: monospace; font-size:8px; color:#0f766e; font-weight:bold;">{{ $dn->dn_number }}</td>
                <td style="font-family: monospace; font-size:8px; color:#6b7280;">{{ $dn->purchaseOrder?->po_number ?? '-' }}</td>
                <td>{{ $dn->estimated_delivery_date?->format('d/m/Y') ?? '-' }}</td>
                <td>{{ $dn->vehicle_number ?? '-' }}</td>
                <td>{{ $dn->driver_name ?? '-' }}</td>
                <td><span class="badge badge-{{ $dn->status }}">{{ $dn->statusLabel() }}</span></td>
                <td style="color:#6b7280;">{{ $dn->created_at?->format('d/m/Y') ?? '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="8" style="text-align:center; padding:12px; color:#9ca3af;">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak dari IPPI iUse System &mdash; {{ now()->format('d/m/Y H:i') }} &mdash; Total: {{ $deliveryNotes->count() }} surat jalan
    </div>
</div>
</body>
</html>
