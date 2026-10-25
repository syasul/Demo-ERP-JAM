<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Dokumen Resmi - ERP Toko Jam</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-gray-100 p-8 text-center space-y-6">
        <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        </div>

        <div>
            <span class="inline-block px-3 py-1 bg-emerald-50 text-emerald-700 font-semibold text-xs rounded-full uppercase tracking-wider mb-2">Dokumen Asli Terverifikasi</span>
            <h1 class="text-xl font-bold text-gray-900">{{ $docTitle }}</h1>
            <p class="text-sm font-mono text-gray-500 mt-1">{{ $number }}</p>
        </div>

        <div class="bg-gray-50 rounded-xl p-4 text-left space-y-2 text-sm border border-gray-100">
            <div class="flex justify-between">
                <span class="text-gray-500">Tanggal Dokumen:</span>
                <span class="font-medium text-gray-900">{{ $date }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Status Dokumen:</span>
                <span class="font-semibold text-emerald-600">{{ $status }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Jumlah Item Barang:</span>
                <span class="font-medium text-gray-900">{{ $itemsCount }} Produk</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Tanda Tangan Digital:</span>
                <span class="font-mono text-xs text-blue-600">VALID (HMAC-SHA256)</span>
            </div>
        </div>

        <p class="text-xs text-gray-400">Dokumen ini diterbitkan secara sah oleh sistem ERP Toko Jam. Data telah diverifikasi melalui tanda tangan digital QR Code.</p>
    </div>
</body>
</html>
