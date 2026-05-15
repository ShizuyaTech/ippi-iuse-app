<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Material Receipt {{ $materialReceipt->vmd_number }}</title>
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
        .vmd-number { font-size: 12px; font-weight: bold; font-family: monospace; color: #333; margin-top: 3px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 8px; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .badge-sent      { background: #dbeafe; color: #1d4ed8; }
        .badge-confirmed { background: #dcfce7; color: #15803d; }
        .badge-draft     { background: #e5e7eb; color: #374151; }
        .info-grid { display: table; width: 100%; border: 1px solid #e5e7eb; border-radius: 3px; margin-bottom: 14px; }
        .info-row { display: table-row; }
        .info-cell { display: table-cell; padding: 6px 10px; vertical-align: top; width: 33%; border-right: 1px solid #f0f0f0; }
        .info-cell:last-child { border-right: none; }
        .info-label { font-size: 8px; color: #6b7280; text-transform: uppercase; margin-bottom: 2px; }
        .info-value { font-size: 10px; font-weight: bold; color: #111; }
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
            <div class="doc-title">VENDOR MATERIAL DELIVERY</div>
            <div class="vmd-number">{{ $materialReceipt->vmd_number }}</div>
            <div style="margin-top:4px;">
                <span class="badge badge-{{ $materialReceipt->status }}">{{ $materialReceipt->statusLabel() }}</span>
            </div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">Tanggal Kirim</div>
                <div class="info-value">{{ $materialReceipt->delivery_date?->format('d M Y') ?? '-' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">No. Kendaraan</div>
                <div class="info-value">{{ $materialReceipt->vehicle_number ?? '-' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Driver</div>
                <div class="info-value">{{ $materialReceipt->driver_name ?? '-' }}</div>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <div class="info-label">Dibuat Oleh</div>
                <div class="info-value">{{ $materialReceipt->createdBy?->name ?? '-' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Dikonfirmasi</div>
                <div class="info-value">{{ $materialReceipt->confirmed_at?->format('d M Y, H:i') ?? '-' }}</div>
            </div>
            <div class="info-cell">
                <div class="info-label">Dicetak</div>
                <div class="info-value">{{ now()->format('d M Y, H:i') }}</div>
            </div>
        </div>
        @if($materialReceipt->notes)
        <div class="info-row">
            <div class="info-cell" style="width:100%; border-right:none;" colspan="3">
                <div class="info-label">Catatan</div>
                <div class="info-value" style="font-weight:normal;">{{ $materialReceipt->notes }}</div>
            </div>
        </div>
        @endif
    </div>

    <table class="items">
        <thead>
            <tr>
                <th style="width:28px; text-align:center;">#</th>
                <th style="width:90px;">Kode</th>
                <th>Nama Material</th>
                <th style="width:80px;">Lokasi</th>
                <th class="right" style="width:70px;">Qty Kirim</th>
                <th class="right" style="width:70px;">Qty Terima</th>
                <th class="right" style="width:55px;">Selisih</th>
                <th style="width:35px;">Sat</th>
            </tr>
        </thead>
        <tbody>
            @foreach($materialReceipt->items as $item)
            @php $selisih = $item->quantity - ($item->quantity_confirmed ?? $item->quantity); @endphp
            <tr>
                <td style="text-align:center; color:#9ca3af;">{{ $loop->iteration }}</td>
                <td style="font-family: monospace; font-size:9px; color:#0f766e;">{{ $item->material?->code }}</td>
                <td>{{ $item->material?->name }}</td>
                <td style="font-size:9px; color:#6b7280;">{{ $item->storageLocation?->name ?? '-' }}</td>
                <td class="right" style="color:#6b7280;">{{ fmt_qty($item->quantity) }}</td>
                <td class="right" style="color:#0f766e; font-weight:bold;">{{ fmt_qty($item->quantity_confirmed ?? $item->quantity) }}</td>
                <td class="right" style="{{ $selisih > 0 ? 'color:#d97706; font-weight:bold;' : 'color:#d1d5db;' }}">
                    {{ $selisih > 0 ? '-'.fmt_qty($selisih) : '—' }}
                </td>
                <td style="color:#6b7280;">{{ $item->material?->unit_of_measure }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak dari IPPI iUse System &mdash; {{ now()->format('d/m/Y H:i') }} &mdash; {{ $materialReceipt->vmd_number }}
    </div>
</div>
</body>
</html>
