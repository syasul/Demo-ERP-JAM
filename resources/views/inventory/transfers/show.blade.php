<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('transfers.index') }}" class="text-gray-500 hover:text-gray-700">&larr;</a>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $transfer->number }}</h1>
                        <x-ui.badge :variant="$transfer->status->badgeVariant()">
                            {{ $transfer->status->label() }}
                        </x-ui.badge>
                    </div>
                    <p class="text-xs text-gray-500">{{ $transfer->fromWarehouse->name }} &rarr; {{ $transfer->toWarehouse->name }}</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @if($transfer->status->value === 'draft')
                    <form method="POST" action="{{ route('transfers.send', $transfer) }}">
                        @csrf
                        <button type="submit" onclick="return confirm('Kirim transfer barang ini sekarang?')"
                                class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-semibold shadow-sm">
                            Kirim Barang (In-Transit)
                        </button>
                    </form>
                @endif

                @if($transfer->status->value === 'in_transit')
                    <form method="POST" action="{{ route('transfers.receive', $transfer) }}">
                        @csrf
                        <button type="submit" onclick="return confirm('Konfirmasi penerimaan barang di gudang tujuan? Stok tujuan akan bertambah.')"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold shadow-sm">
                            Terima Barang di Tujuan
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="space-y-6 max-w-4xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="text-xs text-gray-400 block">Gudang Pengirim</span>
                    <span class="font-bold text-gray-900 dark:text-white">{{ $transfer->fromWarehouse->name }}</span>
                </div>
                <div>
                    <span class="text-xs text-gray-400 block">Gudang Penerima</span>
                    <span class="font-bold text-gray-900 dark:text-white">{{ $transfer->toWarehouse->name }}</span>
                </div>
                <div>
                    <span class="text-xs text-gray-400 block">Waktu Kirim</span>
                    <span class="font-bold text-gray-900 dark:text-white">{{ $transfer->sent_at ? $transfer->sent_at->format('d/m/Y H:i') : 'Belum Dikirim' }}</span>
                </div>
                <div>
                    <span class="text-xs text-gray-400 block">Waktu Diterima</span>
                    <span class="font-bold text-gray-900 dark:text-white">{{ $transfer->received_at ? $transfer->received_at->format('d/m/Y H:i') : 'Menunggu Diterima' }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="font-bold text-gray-900 dark:text-white text-base">Daftar Item Transfer</h3>
            </div>
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900 text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-6 py-3.5">SKU</th>
                        <th class="px-6 py-3.5">Nama Produk</th>
                        <th class="px-6 py-3.5 text-center">Qty Dikirim</th>
                        <th class="px-6 py-3.5 text-center">Qty Diterima</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($transfer->items as $item)
                        <tr>
                            <td class="px-6 py-4 font-mono text-xs text-gray-500">{{ $item->product->sku }}</td>
                            <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">{{ $item->product->name }}</td>
                            <td class="px-6 py-4 text-center font-bold text-gray-900 dark:text-white">{{ number_format($item->qty_sent, 0) }} Pcs</td>
                            <td class="px-6 py-4 text-center font-bold text-emerald-600">{{ $item->qty_received !== null ? number_format($item->qty_received, 0) . ' Pcs' : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
