<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('opnames.index') }}" class="text-gray-500 hover:text-gray-700">&larr;</a>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $opname->number }}</h1>
                        <x-ui.badge :variant="$opname->status->badgeVariant()">
                            {{ $opname->status->label() }}
                        </x-ui.badge>
                    </div>
                    <p class="text-xs text-gray-500">{{ $opname->warehouse->name }} • Tanggal: {{ $opname->opname_date->format('d/m/Y') }}</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @if($opname->status->value === 'counting')
                    <form method="POST" action="{{ route('opnames.submit', $opname) }}">
                        @csrf
                        <button type="submit" onclick="return confirm('Ajukan hasil stock opname ini ke manajemen untuk disetujui?')"
                                class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-semibold shadow-sm">
                            Ajukan Persetujuan
                        </button>
                    </form>
                @endif

                @if($opname->status->value === 'submitted')
                    <form method="POST" action="{{ route('opnames.approve', $opname) }}">
                        @csrf
                        <button type="submit" onclick="return confirm('Setujui hasil stock opname ini? Selisih stok fisik akan otomatis disesuaikan di saldo gudang.')"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold shadow-sm">
                            Setujui & Buat Adjustment
                        </button>
                    </form>

                    <form method="POST" action="{{ route('opnames.reject', $opname) }}">
                        @csrf
                        <button type="submit" onclick="return confirm('Tolak hasil opname ini?')"
                                class="px-3 py-2 bg-rose-50 text-rose-600 border border-rose-200 hover:bg-rose-100 rounded-lg text-sm font-semibold">
                            Tolak
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="space-y-6 max-w-5xl mx-auto">
        <form method="POST" action="{{ route('opnames.counts', $opname) }}" class="space-y-6">
            @csrf

            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white text-base">Lembar Hitung Fisik</h3>
                        <p class="text-xs text-gray-500">Periksa fisik jam tangan di rak, lalu sesuaikan kolom Hitung Fisik.</p>
                    </div>
                    @if($opname->status->value === 'counting')
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-sm">
                            Simpan Perhitungan
                        </button>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900 text-xs text-gray-500 uppercase">
                            <tr>
                                <th class="px-6 py-3.5">SKU</th>
                                <th class="px-6 py-3.5">Nama Produk Jam</th>
                                <th class="px-6 py-3.5 text-center">Stok Sistem</th>
                                <th class="px-6 py-3.5 text-center w-36">Hitung Fisik</th>
                                <th class="px-6 py-3.5 text-center">Selisih (Diff)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($opname->items as $item)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750 transition-colors">
                                    <td class="px-6 py-4 font-mono text-xs text-gray-500">{{ $item->product->sku }}</td>
                                    <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">{{ $item->product->name }}</td>
                                    <td class="px-6 py-4 text-center font-bold text-gray-900 dark:text-white">
                                        {{ number_format($item->system_qty, 0) }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($opname->status->value === 'counting')
                                            <input type="number" step="1" name="counts[{{ $item->id }}]" value="{{ (int)$item->counted_qty }}"
                                                   class="w-24 text-center py-1 border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-transparent font-bold">
                                        @else
                                            <span class="font-bold text-gray-900 dark:text-white">{{ number_format($item->counted_qty, 0) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold">
                                        @if($item->diff_qty > 0)
                                            <span class="text-emerald-600">+{{ number_format($item->diff_qty, 0) }} (Lebih)</span>
                                        @elseif($item->diff_qty < 0)
                                            <span class="text-rose-600">{{ number_format($item->diff_qty, 0) }} (Kurang)</span>
                                        @else
                                            <span class="text-gray-400">0 (Cocok)</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
