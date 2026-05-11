<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $productionOrder->order_number }}</title>
    <style>
        @page {
            size: 58mm auto;
            margin: 2mm 2mm 3mm 2mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 7pt;
            width: 54mm;
            background: #fff;
            color: #000;
        }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .title {
            font-size: 7pt;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .order-number {
            font-size: 9pt;
            font-weight: bold;
            margin: 0.5mm 0;
        }
        .barcode-wrap {
            margin: 1mm 0 0.5mm 0;
        }
        .barcode-wrap svg {
            width: 100% !important;
            height: 14mm !important;
            display: block;
        }
        .divider {
            border: none;
            border-top: 1px dashed #000;
            margin: 1.5mm 0;
        }
        .divider-solid {
            border: none;
            border-top: 1px solid #000;
            margin: 1.5mm 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.8mm;
            line-height: 1.3;
        }
        .info-label {
            color: #444;
            min-width: 18mm;
        }
        .info-value {
            text-align: right;
            font-weight: bold;
        }
        .material-name {
            font-size: 6.5pt;
            margin-bottom: 1mm;
            line-height: 1.3;
        }
        .section-title {
            font-size: 6.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 1mm;
        }
        .comp-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0.5mm;
        }
        .comp-table td {
            padding: 0.4mm 0;
            font-size: 6.5pt;
            vertical-align: top;
            line-height: 1.3;
        }
        .comp-code {
            font-weight: bold;
            width: 22mm;
        }
        .comp-qty {
            text-align: right;
            white-space: nowrap;
        }
        .comp-name {
            font-size: 6pt;
            color: #444;
        }
        .footer {
            font-size: 6pt;
            color: #555;
            margin-top: 1mm;
        }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="center">
        <div class="title">Production Order</div>
        <div class="order-number">{{ $productionOrder->order_number }}</div>
        <div class="barcode-wrap">{!! $barcode !!}</div>
    </div>

    <hr class="divider-solid">

    {{-- Material & Qty --}}
    <div class="info-row">
        <span class="info-label">Item</span>
        <span class="info-value">{{ $productionOrder->material->code }}</span>
    </div>
    <div class="material-name">{{ $productionOrder->material->name }}</div>
    <div class="info-row">
        <span class="info-label">Tgl Mulai</span>
        <span class="info-value">{{ $productionOrder->planned_start_date?->format('d/m/Y') ?? '-' }}</span>
    </div>
    <div class="info-row">
        <span class="info-label">Tgl Selesai</span>
        <span class="info-value">{{ $productionOrder->planned_end_date?->format('d/m/Y') ?? '-' }}</span>
    </div>

    {{-- Components --}}
    @if($productionOrder->components->isNotEmpty())
    <hr class="divider">
    <div class="section-title">Komponen Bahan</div>
    @foreach($productionOrder->components as $comp)
    <table class="comp-table">
        <tr>
            <td class="comp-code">{{ $comp->material->code }}</td>
            <td class="comp-qty">{{ number_format($comp->quantity_issued, 3) }} {{ $comp->material->unit_of_measure }}</td>
        </tr>
        <tr>
            <td colspan="2" class="comp-name">{{ $comp->material->name }}</td>
        </tr>
    </table>
    @endforeach
    @endif

    <hr class="divider">
    <div class="center footer">Dicetak: {{ user_now()->format('d/m/Y H:i') }} {{ user_tz_label() }}</div>

    <script>
        window.onload = function () { window.print(); };
    </script>
</body>
</html>
