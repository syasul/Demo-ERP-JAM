<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Kartu Stok (Stock Card Ledger)</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Mutasi historis masuk, keluar, dan saldo berjalan per produk & gudang.</p>
            </div>
            <a href="{{ route('stock.position') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400">
                &larr; Lihat Posisi Stok
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Filter Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
            <form method="GET" action="{{ route('stock.card') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Pilih Produk Jam *</label>
                    <select name="product_id" required class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ $product && $product->id == $p->id ? 'selected' : '' }}>
                                {{ $p->name }} ({{ $p->sku }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Gudang *</label>
                    <select name="warehouse_id" required class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ $warehouse && $warehouse->id == $wh->id ? 'selected' : '' }}>
                                {{ $wh->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Rentang Tanggal</label>
                    <div class="flex items-center gap-1.5">
                        <input type="date" name="start_date" value="{{ $startDate }}" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-xs py-2">
                        <span class="text-gray-400 text-xs">s/d</span>
                        <input type="date" name="end_date" value="{{ $endDate }}" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-xs py-2">
                    </div>
                </div>

                <div>
                    <button type="submit" class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold shadow-sm">
                        Tampilkan Kartu Stok
                    </button>
                </div>
            </form>
        </div>

        @if($product && $warehouse)
            <!-- Summary Banner -->
            <div class="bg-blue-50 dark:bg-blue-950/40 border border-blue-100 dark:border-blue-900/50 rounded-2xl p-4 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <span class="text-xs text-blue-600 dark:text-blue-400 font-semibold uppercase tracking-wider">Produk Terpilih</span>
                    <h3 class="font-bold text-gray-900 dark:text-white text-base">{{ $product->name }} <span class="font-mono text-sm text-gray-500">({{ $product->sku }})</span></h3>
                    <p class="text-xs text-gray-500">Lokasi: {{ $warehouse->name }} • Satuan: {{ $product->unit->name ?? 'Pcs' }}</p>
                </div>
                <div class="flex items-center gap-6">
                    <div class="text-right">
                        <span class="text-xs text-gray-500 block">Saldo Awal Periode</span>
                        <span class="font-bold text-gray-900 dark:text-white text-base">{{ number_format($openingBalance, 0) }} Pcs</span>
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-blue-600 block font-semibold">Saldo Akhir Periode</span>
                        <span class="font-black text-blue-600 dark:text-blue-400 text-xl">{{ number_format($closingBalance, 0) }} Pcs</span>
                    </div>
                </div>
            </div>

            <!-- Ledger Movements Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900 text-xs text-gray-500 uppercase">
                            <tr>
                                <th class="px-6 py-3.5">Waktu Transaksi</th>
                                <th class="px-6 py-3.5">Dokumen Referensi</th>
                                <th class="px-6 py-3.5">Jenis Mutasi</th>
                                <th class="px-6 py-3.5 text-center text-emerald-600">Masuk (+)</th>
                                <th class="px-6 py-3.5 text-center text-rose-600">Keluar (-)</th>
                                <th class="px-6 py-3.5 text-center font-bold">Saldo Akhir</th>
                                <th class="px-6 py-3.5 text-right">Biaya Satuan (HPP)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <!-- Opening Balance Row -->
                            <tr class="bg-gray-50/50 dark:bg-gray-900/30 font-medium italic text-gray-500">
                                <td class="px-6 py-3" colspan="3">Saldo Awal per {{ date('d/m/Y', strtotime($startDate)) }}</td>
                                <td class="px-6 py-3 text-center">-</td>
                                <td class="px-6 py-3 text-center">-</td>
                                <td class="px-6 py-3 text-center font-bold text-gray-900 dark:text-white">{{ number_format($openingBalance, 0) }}</td>
                                <td class="px-6 py-3 text-right">-</td>
                            </tr>

                            @php $running = $openingBalance; @endphp
                            @forelse($movements as $m)
                                @php
                                    if ($m->direction === 'in') {
                                        $running += (float) $m->qty;
                                    } else {
                                        $running -= (float) $m->qty;
                                    }
                                @endphp
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750 transition-colors">
                                    <td class="px-6 py-3.5 text-xs text-gray-500 font-mono">
                                        {{ $m->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-6 py-3.5">
                                        @if($m->reference)
                                            <span class="font-mono text-xs font-semibold text-blue-600">{{ $m->reference->number ?? '-' }}</span>
                                        @else
                                            <span class="text-xs text-gray-400">-</span>
                                        @endif
                                        @if($m->note)
                                            <div class="text-[11px] text-gray-400">{{ $m->note }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3.5 text-xs font-medium text-gray-700 dark:text-gray-300">
                                        {{ $m->type->label() }}
                                    </td>
                                    <td class="px-6 py-3.5 text-center font-bold text-emerald-600">
                                        {{ $m->direction === 'in' ? number_format($m->qty, 0) : '-' }}
                                    </td>
                                    <td class="px-6 py-3.5 text-center font-bold text-rose-600">
                                        {{ $m->direction === 'out' ? number_format($m->qty, 0) : '-' }}
                                    </td>
                                    <td class="px-6 py-3.5 text-center font-black text-gray-900 dark:text-white">
                                        {{ number_format($running, 0) }}
                                    </td>
                                    <td class="px-6 py-3.5 text-right font-medium text-gray-600 dark:text-gray-300">
                                        @rupiah($m->unit_cost)
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-gray-400 text-sm">
                                        Tidak ada mutasi pergerakan stok dalam rentang tanggal ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
