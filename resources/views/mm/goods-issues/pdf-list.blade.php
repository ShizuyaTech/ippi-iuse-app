<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Daftar Goods Issue</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1a1a1a; }
        .page { padding: 24px 30px; }
        .header { display: table; width: 100%; border-bottom: 2px solid #1d4ed8; padding-bottom: 10px; margin-bottom: 14px; }
        .header-left { display: table-cell; vertical-align: middle; }
        .header-right { display: table-cell; vertical-align: middle; text-align: right; }
        .company-name { font-size: 15px; font-weight: bold; color: #1d4ed8; }
        .company-sub { font-size: 8px; color: #555; margin-top: 1px; }
        .doc-title { font-size: 14px; font-weight: bold; color: #1d4ed8; }
        .doc-sub { font-size: 8px; color: #555; margin-top: 2px; }
        .filter-bar { background: #f0f4ff; border: 1px solid #c7d2fe; border-radius: 3px; padding: 5px 10px; margin-bottom: 12px; font-size: 8px; color: #374151; }
        .filter-bar span { font-weight: bold; color: #1d4ed8; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        thead tr { background: #1d4ed8; color: #fff; }
        thead th { padding: 5px 7px; text-align: left; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        thead th.center { text-align: center; }
        tbody tr { border-bottom: 1px solid #e5e7eb; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody td { padding: 5px 7px; font-size: 9px; }
        tbody td.center { text-align: center; }
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
            <div class="doc-title">DAFTAR GOODS ISSUE</div>
            <div class="doc-sub">Dicetak: {{ now()->format('d M Y, H:i') }} WIB &nbsp;|&nbsp; Oleh: {{ auth()->user()->name ?? '-' }}</div>
        </div>
    </div>

    <div class="filter-bar">
        Filter aktif:
        @if($filters['search'] ?? null) <span>Pencarian:</span> "{{ $filters['search'] }}" &nbsp; @endif
        @if($filters['date_from'] ?? null) <span>Dari:</span> {{ \Carbon\Carbon::parse($filters['date_from'])->format('d M Y') }} &nbsp; @endif
        @if($filters['date_to'] ?? null) <span>Sampai:</span> {{ \Carbon\Carbon::parse($filters['date_to'])->format('d M Y') }} &nbsp; @endif
        @if(!($filters['search'] ?? null) && !($filters['date_from'] ?? null) && !($filters['date_to'] ?? null)) Semua data @endif
        &nbsp;|&nbsp; <span>Total:</span> {{ count($issues) }} GI
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:4%">No</th>
                <th style="width:18%">No. GI</th>
                <th style="width:12%">Tanggal</th>
                <th>Lokasi</th>
                <th>Keterangan</th>
                <th class="center" style="width:10%">Jumlah Item</th>
            </tr>
        </thead>
        <tbody>
            @forelse($issues as $i => $gi)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td style="font-family: monospace; color: #1d4ed8; font-weight: bold;">{{ $gi->gi_number }}</td>
                <td>{{ $gi->issue_date->format('d/m/Y') }}</td>
                <td>{{ $gi->storageLocation->name ?? '-' }}</td>
                <td>{{ \Str::limit($gi->notes, 50) ?? '-' }}</td>
                <td class="center">{{ $gi->items->count() }}</td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center; color:#9ca3af; padding:16px;">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div style="font-size:8px; color:#6b7280;">Total {{ count($issues) }} Goods Issue ditampilkan.</div>
    <div class="footer">Dokumen ini dihasilkan secara otomatis oleh sistem IPPI &mdash; {{ now()->format('d M Y H:i') }}</div>
</div>
</body>
</html>
