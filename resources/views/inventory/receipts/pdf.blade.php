<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tanda Terima Barang - {{ $receipt->number }}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: #111827;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 8px;
            margin-bottom: 16px;
        }
        .company-title {
            font-size: 14pt;
            font-weight: bold;
            color: #1e3a8a;
        }
        .doc-title {
            font-size: 13pt;
            font-weight: bold;
            text-align: right;
            color: #111;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 16px;
        }
        .meta-table td {
            vertical-align: top;
            padding: 2px 4px;
        }
        .table-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table-items th {
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            font-size: 8.5pt;
            text-align: left;
        }
        .table-items td {
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
            font-size: 8.5pt;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer-signatures {
            width: 100%;
            margin-top: 30px;
        }
        .footer-signatures td {
            text-align: center;
            width: 33.33%;
            vertical-align: bottom;
        }
        .sign-line {
            border-bottom: 1px solid #333;
            width: 80%;
            margin: 50px auto 4px auto;
        }
        .qr-box {
            float: right;
            text-align: center;
        }
        .qr-img {
            width: 85px;
            height: 85px;
        }
    </style>
</head>
<body>
    <table style="width: 100%;" class="header">
        <tr>
            <td>
                <div class="company-title">ERP TOKO JAM INDONESIA</div>
                <div style="font-size: 8pt; color: #4b5563;">Distributor & Retail Jam Tangan Mewah & Kasual</div>
                <div style="font-size: 8pt; color: #4b5563;">Jl. Thamrin No. 88, Jakarta Pusat • Telp: (021) 555-0199</div>
            </td>
            <td class="text-right">
                <div class="doc-title">BUKTI PENERIMAAN BARANG</div>
                <div style="font-size: 10pt; font-weight: bold; color: #1e3a8a;">{{ $receipt->number }}</div>
                <div style="font-size: 8pt; color: #6b7280;">Status: {{ strtoupper($receipt->status->value) }}</div>
            </td>
        </tr>
    </table>

    <table class="meta-table">
        <tr>
            <td style="width: 50%;">
                <strong>Diterima Dari (Supplier):</strong><br>
                <span style="font-size: 10pt; font-weight: bold;">{{ $receipt->supplier->name }}</span><br>
                {{ $receipt->supplier->address ?? '-' }}<br>
                Telp: {{ $receipt->supplier->phone ?? '-' }}
            </td>
            <td style="width: 30%;">
                <strong>Gudang Tujuan:</strong> {{ $receipt->warehouse->name }}<br>
                <strong>Tanggal Terima:</strong> {{ $receipt->receipt_date->format('d/m/Y') }}<br>
                <strong>No. Surat Jalan:</strong> {{ $receipt->supplier_ref ?? '-' }}<br>
                <strong>Penerima:</strong> {{ $receipt->postedBy->name ?? 'Staff Gudang' }}
            </td>
            <td style="width: 20%;" class="text-right">
                <div class="qr-box">
                    <img src="{{ $qrDataUri }}" class="qr-img" alt="QR Signature">
                    <div style="font-size: 6.5pt; color: #6b7280; font-family: monospace;">VERIFIKASI RESMI</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="table-items">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 18%;">Kode / SKU</th>
                <th style="width: 37%;">Nama Produk Jam</th>
                <th style="width: 12%;">Lokasi Rak</th>
                <th style="width: 10%;" class="text-center">Jumlah</th>
                <th style="width: 18%;" class="text-right">Harga Satuan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($receipt->items as $idx => $item)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td style="font-family: monospace;">{{ $item->product->sku }}</td>
                    <td><strong>{{ $item->product->name }}</strong></td>
                    <td>{{ $item->location->code ?? 'Gudang' }}</td>
                    <td class="text-center font-bold">{{ number_format($item->qty, 0) }}</td>
                    <td class="text-right">@rupiah($item->unit_cost)</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #f9fafb; font-weight: bold;">
                <td colspan="4" class="text-right">Total Biaya Masuk:</td>
                <td class="text-center">{{ number_format($receipt->items->sum('qty'), 0) }} Pcs</td>
                <td class="text-right" style="color: #1e3a8a; font-size: 10pt;">@rupiah($receipt->total_cost)</td>
            </tr>
        </tfoot>
    </table>

    @if($receipt->note)
        <div style="font-size: 8pt; margin-bottom: 20px; background: #f9fafb; padding: 6px; border-left: 3px solid #1e3a8a;">
            <strong>Catatan:</strong> {{ $receipt->note }}
        </div>
    @endif

    <table class="footer-signatures">
        <tr>
            <td>
                Diterima Oleh,
                <div class="sign-line"></div>
                ( Staff Gudang )
            </td>
            <td>
                Diperiksa Oleh,
                <div class="sign-line"></div>
                ( Quality Control )
            </td>
            <td>
                Diserahkan Oleh,
                <div class="sign-line"></div>
                ( Pengirim / Supplier )
            </td>
        </tr>
    </table>
</body>
</html>
