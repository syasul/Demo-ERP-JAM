<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Penerimaan Barang (Goods Receipt)</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Pencatatan barang masuk dari supplier, surat jalan pabrik, dan penambahan persediaan.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('qr.scan') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:bg-gray-700">
                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    Scan Masuk
                </a>
                <a href="{{ route('receipts.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-lg text-white bg-blue-600 hover:bg-blue-700 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Input Penerimaan Manual
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Filter Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
            <form method="GET" action="{{ route('receipts.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nomor GR / No. SJ..."
                           class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                </div>
                <div>
                    <select name="status" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                        <option value="">Semua Status</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="posted" {{ request('status') === 'posted' ? 'selected' : '' }}>Posted</option>
                        <option value="void" {{ request('status') === 'void' ? 'selected' : '' }}>Void</option>
                    </select>
                </div>
                <div>
                    <select name="supplier_id" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                        <option value="">Semua Supplier</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="px-4 py-2 bg-gray-900 hover:bg-gray-800 dark:bg-gray-700 dark:hover:bg-gray-600 text-white rounded-xl text-sm font-medium w-full">
                        Filter
                    </button>
                    <a href="{{ route('receipts.index') }}" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl text-sm">Reset</a>
                </div>
            </form>
        </div>

        <!-- Receipts Table -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-6 py-3.5">Nomor GR</th>
                            <th class="px-6 py-3.5">Tanggal</th>
                            <th class="px-6 py-3.5">Supplier</th>
                            <th class="px-6 py-3.5">Gudang</th>
                            <th class="px-6 py-3.5 text-right">Total Biaya</th>
                            <th class="px-6 py-3.5 text-center">Status</th>
                            <th class="px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($receipts as $receipt)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750 transition-colors">
                                <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">
                                    <a href="{{ route('receipts.show', $receipt) }}" class="text-blue-600 hover:underline">
                                        {{ $receipt->number }}
                                    </a>
                                    @if($receipt->supplier_ref)
                                        <div class="text-xs text-gray-400">Ref: {{ $receipt->supplier_ref }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                    {{ $receipt->receipt_date->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 text-gray-900 dark:text-white font-medium">
                                    {{ $receipt->supplier->name }}
                                </td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                    {{ $receipt->warehouse->name }}
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">
                                    @rupiah($receipt->total_cost)
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <x-ui.badge :variant="$receipt->status->badgeVariant()">
                                        {{ $receipt->status->label() }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('receipts.show', $receipt) }}" class="text-sm font-semibold text-blue-600 hover:text-blue-800">
                                        Rincian &rarr;
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-gray-400">
                                    Belum ada transaksi Penerimaan Barang.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($receipts->hasPages())
                <div class="p-4 border-t border-gray-100 dark:border-gray-700">
                    {{ $receipts->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
