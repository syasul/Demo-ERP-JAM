<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Posisi Stok & Valuasi Persediaan</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Monitoring saldo fisik jam tangan dan total nilai aset persediaan secara real-time.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('stock.card') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700">
                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Buka Kartu Stok
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <x-ui.card>
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Kuantitas Fisik</span>
                        <div class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ number_format($totalQty, 0) }} Pcs</div>
                    </div>
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Nilai Persediaan</span>
                        <div class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">@rupiah($totalValuation)</div>
                    </div>
                    <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Metode Valuasi</span>
                        <div class="text-xl font-bold text-gray-900 dark:text-white mt-1">Moving Average</div>
                    </div>
                    <div class="p-3 bg-amber-50 text-amber-600 rounded-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
            <form method="GET" action="{{ route('stock.position') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama jam atau SKU..."
                           class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                </div>
                <div>
                    <select name="warehouse_id" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                        <option value="">Semua Gudang</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="category_id" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-semibold w-full">
                        Filter
                    </button>
                    <a href="{{ route('stock.position') }}" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-xl text-sm">Reset</a>
                </div>
            </form>
        </div>

        <!-- Balances Table -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-6 py-3.5">SKU</th>
                            <th class="px-6 py-3.5">Nama Jam Tangan</th>
                            <th class="px-6 py-3.5">Gudang & Rak</th>
                            <th class="px-6 py-3.5 text-center">Stok Fisik</th>
                            <th class="px-6 py-3.5 text-right">Rata-rata Biaya (HPP)</th>
                            <th class="px-6 py-3.5 text-right">Total Nilai Aset</th>
                            <th class="px-6 py-3.5 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($balances as $b)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750 transition-colors">
                                <td class="px-6 py-4 font-mono text-xs text-gray-500">{{ $b->product->sku }}</td>
                                <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">
                                    {{ $b->product->name }}
                                    <div class="text-xs text-gray-400 font-normal">{{ $b->product->category->name ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-600 dark:text-gray-300">
                                    {{ $b->warehouse->name }}
                                    @if($b->location)
                                        <span class="inline-block px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-[11px] font-mono ml-1">Rak: {{ $b->location->code }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center font-bold text-base text-gray-900 dark:text-white">
                                    {{ number_format($b->qty, 0) }} {{ $b->product->unit->name ?? 'Pcs' }}
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-gray-600 dark:text-gray-300">
                                    @rupiah($b->avg_cost)
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-gray-900 dark:text-white">
                                    @rupiah(round($b->qty * $b->avg_cost))
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($b->qty <= $b->product->min_stock)
                                        <span class="px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 text-xs font-semibold rounded-full">Menipis</span>
                                    @else
                                        <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-semibold rounded-full">Aman</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-gray-400">
                                    Tidak ada data stok yang sesuai dengan filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($balances->hasPages())
                <div class="p-4 border-t border-gray-100 dark:border-gray-700">
                    {{ $balances->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
