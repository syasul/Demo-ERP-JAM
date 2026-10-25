<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Label QR</title>
    <style>
        @page {
            margin: 5mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 8pt;
            color: #111827;
        }
        .grid-container {
            width: 100%;
        }
        .label-box {
            display: inline-block;
            vertical-align: top;
            width: {{ $config['w_mm'] }}mm;
            height: {{ $config['h_mm'] }}mm;
            box-sizing: border-box;
            border: 1px dashed #d1d5db;
            padding: 2mm;
            margin: 1mm;
            overflow: hidden;
            page-break-inside: avoid;
        }
        .label-content {
            width: 100%;
            height: 100%;
        }
        .qr-img {
            width: {{ $config['qr_mm'] }}mm;
            height: {{ $config['qr_mm'] }}mm;
            float: left;
            margin-right: 2mm;
        }
        .label-info {
            overflow: hidden;
        }
        .label-sku {
            font-weight: bold;
            font-size: 9pt;
            color: #000;
        }
        .label-name {
            font-size: 7.5pt;
            line-height: 1.1;
            margin-top: 1mm;
            height: 6mm;
            overflow: hidden;
        }
        .label-price {
            font-weight: bold;
            font-size: 8pt;
            color: #1e3a8a;
            margin-top: 1mm;
        }
        .label-loc {
            font-size: 7pt;
            background: #f3f4f6;
            padding: 1px 3px;
            border-radius: 2px;
            display: inline-block;
            margin-top: 1mm;
        }
        .thermal-label {
            text-align: center;
            width: 100%;
            height: 100%;
        }
        .thermal-qr {
            width: {{ $config['qr_mm'] }}mm;
            height: {{ $config['qr_mm'] }}mm;
            display: block;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    @if(str_contains($template, 'thermal'))
        @foreach($labels as $label)
            <div style="page-break-after: always; text-align: center; padding-top: 1mm;">
                <img src="{{ $label['qr_data_uri'] }}" class="thermal-qr" alt="QR">
                <div style="font-weight: bold; font-size: 8pt; margin-top: 1mm;">{{ $label['sku'] }}</div>
                <div style="font-size: 7pt; height: 4mm; overflow: hidden;">{{ Str::limit($label['name'], 24) }}</div>
                @if($label['price'])
                    <div style="font-weight: bold; font-size: 8pt;">@rupiah($label['price'])</div>
                @endif
            </div>
        @endforeach
    @else
        <div class="grid-container">
            @foreach($labels as $label)
                <div class="label-box">
                    <img src="{{ $label['qr_data_uri'] }}" class="qr-img" alt="QR">
                    <div class="label-info">
                        <div class="label-sku">{{ $label['sku'] }}</div>
                        <div class="label-name">{{ Str::limit($label['name'], 38) }}</div>
                        @if($label['price'])
                            <div class="label-price">@rupiah($label['price'])</div>
                        @endif
                        @if($label['loc_code'])
                            <div class="label-loc">Rak: {{ $label['loc_code'] }}</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</body>
</html>
