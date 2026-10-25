<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Transfer Antar Gudang</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Pengiriman dan penerimaan mutasi stok antar lokasi gudang.</p>
            </div>
            <a href="{{ route('transfers.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-lg text-white bg-blue-600 hover:bg-blue-700 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Buat Surat Transfer
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-6 py-3.5">Nomor Transfer</th>
                            <th class="px-6 py-3.5">Gudang Asal</th>
                            <th class="px-6 py-3.5">Gudang Tujuan</th>
                            <th class="px-6 py-3.5">Waktu Kirim</th>
                            <th class="px-6 py-3.5 text-center">Status</th>
                            <th class="px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($transfers as $t)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750 transition-colors">
                                <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">
                                    <a href="{{ route('transfers.show', $t) }}" class="text-blue-600 hover:underline">
                                        {{ $t->number }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                    {{ $t->fromWarehouse->name }}
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                    {{ $t->toWarehouse->name }}
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500">
                                    {{ $t->sent_at ? $t->sent_at->format('d/m/Y H:i') : '-' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <x-ui.badge :variant="$t->status->badgeVariant()">
                                        {{ $t->status->label() }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('transfers.show', $t) }}" class="text-sm font-semibold text-blue-600 hover:text-blue-800">
                                        Rincian &rarr;
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-gray-400">
                                    Belum ada data transfer antar gudang.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($transfers->hasPages())
                <div class="p-4 border-t border-gray-100 dark:border-gray-700">
                    {{ $transfers->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
