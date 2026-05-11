<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Daftar Storage Location</title>
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
            <div class="doc-title">DAFTAR STORAGE LOCATION</div>
            <div class="doc-sub">Dicetak: {{ user_now()->format('d M Y, H:i') }} {{ user_tz_label() }} &nbsp;|&nbsp; Oleh: {{ auth()->user()->name ?? '-' }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:4%">No</th>
                <th style="width:15%">Kode</th>
                <th style="width:28%">Nama Lokasi</th>
                <th style="width:12%">Tipe Material</th>
                <th>Deskripsi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($locations as $i => $loc)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td style="font-family: monospace; color: #1d4ed8; font-weight: bold;">{{ $loc->code }}</td>
                <td style="font-weight: bold;">{{ $loc->name }}</td>
                <td class="center">{{ $loc->material_type ?? '-' }}</td>
                <td>{{ $loc->description ?? '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="5" style="text-align:center; color:#9ca3af; padding:16px;">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div style="font-size:8px; color:#6b7280; margin-bottom:4px;">Total {{ count($locations) }} storage location ditampilkan.</div>
    <div class="footer">Dokumen ini dihasilkan secara otomatis oleh sistem IPPI &mdash; {{ user_now()->format('d M Y H:i') }} {{ user_tz_label() }}</div>
</div>
</body>
</html>
