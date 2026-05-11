<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #111; }
        .page { padding: 26px 30px; }

        /* ── Items table ── */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .items-table thead tr { background-color: #1e3a5f; color: #fff; }
        .items-table thead th { padding: 6px 8px; text-align: left; font-size: 10px; }
        .items-table tbody tr:nth-child(even) { background-color: #f4f7fb; }
        .items-table tbody td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; font-size: 10px; }

        /* ── Footer ── */
        .footer { border-top: 1px solid #ddd; margin-top: 16px; padding-top: 6px;
                  font-size: 9px; color: #aaa; text-align: center; }
    </style>
</head>
<body>
<div class="page">

@php
    $poNumber = $deliveryNote->purchaseOrder?->po_number ?? 'N/A';
    $barcodeImg = '';
    try {
        $gen = new \Picqer\Barcode\BarcodeGeneratorPNG();
        $png = $gen->getBarcode($poNumber, $gen::TYPE_CODE_128, 2, 45);
        $barcodeImg = 'data:image/png;base64,' . base64_encode($png);
    } catch (\Throwable $e) { /* barcode unavailable */ }
@endphp

{{-- ═══════════════════════ HEADER ═══════════════════════ --}}
<table width="100%" cellpadding="0" cellspacing="0"
       style="border-bottom: 2px solid #1e3a5f; padding-bottom: 10px; margin-bottom: 12px;">
    <tr>
        {{-- Kiri: Info perusahaan --}}
        <td width="33%" style="vertical-align: middle;">
            <div style="font-size:15px; font-weight:bold; color:#1e3a5f;">PT. INTI PANTJA PRESS INDUSTRI</div>
            <div style="font-size:9px; color:#666; margin-top:2px;">Sistem Manajemen Produksi &amp; Logistik</div>
        </td>

        {{-- Tengah: Judul dokumen --}}
        <td width="34%" style="vertical-align: middle; text-align: center;">
            <div style="font-size:22px; font-weight:bold; color:#1e3a5f; letter-spacing:3px;">SURAT JALAN</div>
            <div style="font-size:13px; font-weight:bold; color:#c0392b; margin-top:4px;">
                {{ $deliveryNote->dn_number }}
            </div>
        </td>

        {{-- Kanan: Barcode No. PO --}}
        <td width="33%" style="vertical-align: middle; text-align: right;">
            @if($barcodeImg)
                <img src="{{ $barcodeImg }}" style="height:42px; max-width:170px;" />
                <div style="font-size:8px; color:#555; margin-top:2px;">{{ $poNumber }}</div>
            @else
                <div style="font-size:10px; font-weight:bold; color:#1e3a5f;">{{ $poNumber }}</div>
            @endif
            <div style="font-size:8px; color:#999; margin-top:1px;">No. Purchase Order</div>
        </td>
    </tr>
</table>

{{-- ═══════════════════════ INFO ═══════════════════════ --}}
<table width="100%" cellpadding="0" cellspacing="0"
       style="margin-bottom: 16px; border: 1px solid #e2e8f0; border-radius:4px;">
    <tr style="background-color:#f8fafc;">
        <td width="48%" style="padding: 10px 12px; vertical-align:top;">
            {{-- Kolom kiri info --}}
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="width:130px; color:#555; padding: 4px 0;">No. Purchase Order</td>
                    <td style="width:12px; color:#555; padding: 4px 0;">:</td>
                    <td style="font-weight:bold; padding: 4px 0;">{{ $poNumber }}</td>
                </tr>
                <tr>
                    <td style="width:130px; color:#555; padding: 4px 0;">Vendor / Pengirim</td>
                    <td style="width:12px; color:#555; padding: 4px 0;">:</td>
                    <td style="font-weight:bold; padding: 4px 0;">{{ $deliveryNote->vendor?->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="width:130px; color:#555; padding: 4px 0;">Tujuan Pengiriman</td>
                    <td style="width:12px; color:#555; padding: 4px 0;">:</td>
                    <td style="font-weight:bold; padding: 4px 0;">PT. INTI PANTJA PRESS INDUSTRI</td>
                </tr>
                @if($deliveryNote->notes)
                <tr>
                    <td style="width:130px; color:#555; padding: 4px 0; vertical-align:top;">Catatan</td>
                    <td style="width:12px; color:#555; padding: 4px 0; vertical-align:top;">:</td>
                    <td style="font-style:italic; color:#444; padding: 4px 0;">{{ $deliveryNote->notes }}</td>
                </tr>
                @endif
            </table>
        </td>

        <td width="4%" style="border-left: 1px solid #e2e8f0;"></td>

        <td width="48%" style="padding: 10px 12px; vertical-align:top;">
            {{-- Kolom kanan info --}}
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="width:130px; color:#555; padding: 4px 0;">Est. Tgl Pengiriman</td>
                    <td style="width:12px; color:#555; padding: 4px 0;">:</td>
                    <td style="font-weight:bold; padding: 4px 0;">{{ $deliveryNote->estimated_delivery_date?->format('d F Y') ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="width:130px; color:#555; padding: 4px 0;">No. Kendaraan</td>
                    <td style="width:12px; color:#555; padding: 4px 0;">:</td>
                    <td style="font-weight:bold; padding: 4px 0;">{{ $deliveryNote->vehicle_number ?? '' }}</td>
                </tr>
                <tr>
                    <td style="width:130px; color:#555; padding: 4px 0;">Nama Driver</td>
                    <td style="width:12px; color:#555; padding: 4px 0;">:</td>
                    <td style="font-weight:bold; padding: 4px 0;">{{ $deliveryNote->driver_name ?? '' }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- ═══════════════════════ ITEMS ═══════════════════════ --}}
<table class="items-table">
    <thead>
        <tr>
            <th style="width:30px; text-align:center;">No</th>
            <th style="width:100px;">Kode Material</th>
            <th>Nama Material</th>
            <th>Deskripsi</th>
            <th style="width:90px; text-align:right;">Qty Kirim</th>
            <th style="width:55px; text-align:center;">Satuan</th>
        </tr>
    </thead>
    <tbody>
        @foreach($deliveryNote->items as $i => $item)
        @php $material = $item->purchaseOrderItem?->material; @endphp
        <tr>
            <td style="text-align:center;">{{ $i + 1 }}</td>
            <td style="font-family:monospace; color:#1e3a5f;">{{ $material?->code ?? '-' }}</td>
            <td>{{ $material?->name ?? '-' }}</td>
            <td>{{ $material?->description ?? '-' }}</td>
            <td style="text-align:right; font-weight:bold;">{{ number_format($item->quantity, 3) }}</td>
            <td style="text-align:center;">{{ $material?->unit_of_measure ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- ═══════════════════════ TANDA TANGAN ═══════════════════════ --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 30px;">
    <tr>
        <td width="33%" style="text-align:center; padding: 0 10px; vertical-align:bottom;">
            <div style="height:60px;"></div>
            <div style="border-top:1px solid #888; padding-top:6px;">
                <div style="font-size:10px; color:#555;">Disiapkan oleh</div>
                <div style="font-weight:bold; font-size:11px; margin-top:3px;">Gudang Vendor</div>
            </div>
        </td>
        <td width="33%" style="text-align:center; padding: 0 10px; vertical-align:bottom;">
            <div style="height:60px;"></div>
            <div style="border-top:1px solid #888; padding-top:6px;">
                <div style="font-size:10px; color:#555;">Pengemudi / Driver</div>
                <div style="font-weight:bold; font-size:11px; margin-top:3px;">
                    {{ $deliveryNote->driver_name ?? '(.................................)' }}
                </div>
            </div>
        </td>
        <td width="33%" style="text-align:center; padding: 0 10px; vertical-align:bottom;">
            <div style="height:60px;"></div>
            <div style="border-top:1px solid #888; padding-top:6px;">
                <div style="font-size:10px; color:#555;">Diterima oleh</div>
                <div style="font-weight:bold; font-size:11px; margin-top:3px;">Gudang IPPI</div>
            </div>
        </td>
    </tr>
</table>

{{-- ═══════════════════════ FOOTER ═══════════════════════ --}}
<div class="footer">
    Dicetak pada {{ now()->format('d/m/Y H:i') }} &bull; IUse IPPI &bull; Dokumen ini adalah surat jalan resmi
</div>

</div>
</body>
</html>
