<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('issues.index') }}" class="text-gray-500 hover:text-gray-700">&larr;</a>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $issue->number }}</h1>
                        <x-ui.badge :variant="$issue->status->badgeVariant()">
                            {{ $issue->status->label() }}
                        </x-ui.badge>
                    </div>
                    <p class="text-xs text-gray-500">Pengeluaran Barang • {{ $issue->type->label() }} • Dibuat {{ $issue->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('issues.pdf', $issue) }}" target="_blank"
                   class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Cetak Surat Jalan (PDF)
                </a>

                @if($issue->status->value === 'draft')
                    <form method="POST" action="{{ route('issues.post', $issue) }}">
                        @csrf
                        <button type="submit" onclick="return confirm('Posting pengeluaran barang ini sekarang? Stok fisik akan langsung berkurang.')"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold shadow-sm">
                            Posting Sekarang
                        </button>
                    </form>
                @endif

                @if($issue->status->value !== 'draft' && $issue->status->value !== 'void')
                    <div x-data="{ openVoid: false }">
                        <button type="button" @click="openVoid = true" class="px-3 py-2 bg-rose-50 text-rose-600 hover:bg-rose-100 rounded-lg text-sm font-semibold border border-rose-200">
                            Void / Batalkan
                        </button>

                        <div x-show="openVoid" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" style="display: none;">
                            <div @click.away="openVoid = false" class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full p-6 space-y-4">
                                <h3 class="font-bold text-gray-900 dark:text-white text-base">Konfirmasi Void Pengeluaran</h3>
                                <p class="text-sm text-gray-500">Stok yang telah dikeluarkan akan dikembalikan ke saldo gudang.</p>

                                <form method="POST" action="{{ route('issues.void', $issue) }}">
                                    @csrf
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Alasan Pembatalan *</label>
                                        <textarea name="reason" rows="3" required placeholder="Contoh: Kesalahan input nomor atau salah gudang..."
                                                  class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm"></textarea>
                                    </div>

                                    <div class="flex justify-end gap-2 mt-4">
                                        <button type="button" @click="openVoid = false" class="px-4 py-2 rounded-lg text-sm text-gray-600 hover:bg-gray-100">Batal</button>
                                        <button type="submit" class="px-4 py-2 bg-rose-600 text-white rounded-lg text-sm font-semibold hover:bg-rose-700">Ya, Void Dokumen</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="space-y-6 max-w-5xl mx-auto">
        <!-- Details Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-6 shadow-sm">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-center">
                <div class="md:col-span-3 grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-xs text-gray-400 block">Tipe Pengeluaran</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $issue->type->label() }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 block">Gudang Asal</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $issue->warehouse->name }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 block">Tanggal Pengeluaran</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $issue->issue_date->format('d F Y') }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 block">Nama Penerima / Kurir</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $issue->recipient_name ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 block">Diposting Oleh</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $issue->postedBy->name ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-400 block">Waktu Posting</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $issue->posted_at ? $issue->posted_at->format('d/m/Y H:i') : '-' }}</span>
                    </div>
                    @if($issue->shipping_address)
                        <div class="col-span-2 sm:col-span-3">
                            <span class="text-xs text-gray-400 block">Alamat / Keterangan</span>
                            <span class="text-gray-700 dark:text-gray-300">{{ $issue->shipping_address }}</span>
                        </div>
                    @endif
                    @if($issue->voided_reason)
                        <div class="col-span-2 sm:col-span-3 p-3 bg-rose-50 dark:bg-rose-950/30 rounded-xl text-rose-700 dark:text-rose-300 text-xs">
                            <strong>Alasan Void:</strong> {{ $issue->voided_reason }} (oleh {{ $issue->voidedBy->name ?? 'User' }} pada {{ $issue->voided_at->format('d/m/Y H:i') }})
                        </div>
                    @endif
                </div>

                <!-- QR Signature Box -->
                <div class="flex flex-col items-center justify-center p-3 bg-gray-50 dark:bg-gray-900 rounded-xl border border-gray-100 dark:border-gray-800 text-center">
                    <div class="w-24 h-24 bg-white p-1 rounded-lg shadow-xs flex items-center justify-center">
                        {!! $qrSvg !!}
                    </div>
                    <span class="text-[10px] font-mono text-gray-400 mt-1">QR Dokumen Resmi</span>
                </div>
            </div>
        </div>

        <!-- Items Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-700">
                <h3 class="font-bold text-gray-900 dark:text-white text-base">Rincian Barang yang Dikeluarkan</h3>
            </div>
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900 text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-6 py-3.5">SKU</th>
                        <th class="px-6 py-3.5">Nama Produk Jam</th>
                        <th class="px-6 py-3.5">Lokasi Rak</th>
                        <th class="px-6 py-3.5 text-center">Jumlah Keluar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($issue->items as $item)
                        <tr>
                            <td class="px-6 py-4 font-mono text-xs text-gray-500">{{ $item->product->sku }}</td>
                            <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">{{ $item->product->name }}</td>
                            <td class="px-6 py-4 text-xs text-gray-500">{{ $item->location->code ?? 'Gudang Umum' }}</td>
                            <td class="px-6 py-4 text-center font-bold text-gray-900 dark:text-white">{{ number_format($item->qty, 0) }} Pcs</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-900 font-bold border-t border-gray-100 dark:border-gray-700">
                    <tr>
                        <td colspan="3" class="px-6 py-4 text-right text-gray-900 dark:text-white">Total Kuantitas:</td>
                        <td class="px-6 py-4 text-center text-blue-600 dark:text-blue-400 font-black text-base">{{ number_format($issue->items->sum('qty'), 0) }} Pcs</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</x-app-layout>
