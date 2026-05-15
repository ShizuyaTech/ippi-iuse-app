<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Stock Overview Vendor</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1a1a1a; }
        .page { padding: 24px 30px; }
        .header { display: table; width: 100%; border-bottom: 2px solid #0f766e; padding-bottom: 10px; margin-bottom: 14px; }
        .header-left { display: table-cell; vertical-align: middle; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; }
        .company-name { font-size: 15px; font-weight: bold; color: #0f766e; }
        .company-sub { font-size: 8px; color: #555; margin-top: 1px; }
        .doc-title { font-size: 14px; font-weight: bold; color: #0f766e; }
        .doc-sub { font-size: 8px; color: #555; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        thead tr { background: #0f766e; color: #fff; }
        thead th { padding: 5px 7px; text-align: left; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        thead th.right { text-align: right; }
        tbody tr { border-bottom: 1px solid #e5e7eb; }
        tbody tr:nth-child(even) { background: #f0fdfa; }
        tbody td { padding: 5px 7px; font-size: 9px; }
        tbody td.right { text-align: right; font-weight: bold; }
        .badge { display: inline-block; padding: 1px 5px; border-radius: 8px; font-size: 7.5px; font-weight: bold; }
        .badge-RM  { background: #e5e7eb; color: #374151; }
        .badge-WIP { background: #fef9c3; color: #a16207; }
        .badge-FP  { background: #dcfce7; color: #15803d; }
        .footer { margin-top: 14px; border-top: 1px solid #e5e7eb; padding-top: 6px; text-align: center; font-size: 7.5px; color: #9ca3af; }
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
            <div class="doc-title">STOCK OVERVIEW – VENDOR</div>
            <div class="doc-sub">Dicetak: {{ now()->format('d M Y, H:i') }} &nbsp;|&nbsp; Oleh: {{ auth()->user()->name ?? '-' }}</div>
        </div>
    </div>

    @if(($filters['search'] ?? '') || ($filters['type'] ?? ''))
    <div style="background:#f0fdfa; border:1px solid #99f6e4; border-radius:3px; padding:4px 10px; margin-bottom:10px; font-size:8px; color:#0f766e;">
        Filter:
        @if($filters['search'] ?? '') Pencarian: <strong>{{ $filters['search'] }}</strong> @endif
        @if($filters['type'] ?? '') | Tipe: <strong>{{ $filters['type'] }}</strong> @endif
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width:24px; text-align:center;">#</th>
                <th style="width:90px;">Kode</th>
                <th>Nama Material</th>
                <th style="width:40px;">Tipe</th>
                <th class="right" style="width:70px;">Qty Stok</th>
                <th style="width:40px;">Satuan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stocks as $i => $s)
            @php $m = $s->material; @endphp
            <tr>
                <td style="text-align:center; color:#9ca3af;">{{ $loop->iteration }}</td>
                <td style="font-family: monospace; font-size:8px; color:#0f766e;">{{ $m?->code }}</td>
                <td>{{ $m?->name }}</td>
                <td><span class="badge badge-{{ $m?->type }}">{{ $m?->type ?? '—' }}</span></td>
                <td class="right" style="color:#0f766e;">{{ fmt_qty($s->quantity) }}</td>
                <td style="color:#6b7280;">{{ $m?->unit_of_measure }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak dari IPPI iUse System &mdash; {{ now()->format('d/m/Y H:i') }} &mdash; Total: {{ $stocks->count() }} item
    </div>
</div>
</body>
</html>
