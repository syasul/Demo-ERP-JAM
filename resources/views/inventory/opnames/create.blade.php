<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Mulai Sesi Stock Opname</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Sistem akan mengambil snapshot seluruh saldo stok sistem saat sesi dimulai.</p>
            </div>
            <a href="{{ route('opnames.index') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400">
                &larr; Kembali
            </a>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <form method="POST" action="{{ route('opnames.store') }}" class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Gudang yang Di-Opname *</label>
                <select name="warehouse_id" required class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->code }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tanggal Opname *</label>
                <input type="date" name="opname_date" value="{{ date('Y-m-d') }}" required
                       class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Catatan</label>
                <input type="text" name="note" placeholder="Opname berkala akhir bulan..."
                       class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
            </div>

            <div class="p-4 bg-amber-50 dark:bg-amber-950/40 rounded-xl border border-amber-200 dark:border-amber-800 text-xs text-amber-800 dark:text-amber-200">
                <strong>Catatan Penting:</strong> Setelah Anda menekan tombol di bawah, sistem secara otomatis mengunci snapshot stok saat ini. Lakukan penghitungan fisik jam tangan di rak gudang kemudian inputkan hasil fisik.
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-sm">
                    Kunci Snapshot & Mulai Hitung
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
