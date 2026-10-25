<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Stock Opname Fisik</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Pencocokan saldo sistem dengan hitungan fisik riil di gudang.</p>
            </div>
            <a href="{{ route('opnames.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold rounded-lg text-white bg-blue-600 hover:bg-blue-700 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Mulai Sesi Opname Baru
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-6 py-3.5">Nomor Sesi</th>
                            <th class="px-6 py-3.5">Tanggal</th>
                            <th class="px-6 py-3.5">Gudang</th>
                            <th class="px-6 py-3.5">Disetujui Oleh</th>
                            <th class="px-6 py-3.5 text-center">Status</th>
                            <th class="px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($opnames as $op)
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750 transition-colors">
                                <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">
                                    <a href="{{ route('opnames.show', $op) }}" class="text-blue-600 hover:underline">
                                        {{ $op->number }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                    {{ $op->opname_date->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                    {{ $op->warehouse->name }}
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500">
                                    {{ $op->approvedBy->name ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <x-ui.badge :variant="$op->status->badgeVariant()">
                                        {{ $op->status->label() }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('opnames.show', $op) }}" class="text-sm font-semibold text-blue-600 hover:text-blue-800">
                                        Buka &rarr;
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-gray-400">
                                    Belum ada sesi stock opname.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($opnames->hasPages())
                <div class="p-4 border-t border-gray-100 dark:border-gray-700">
                    {{ $opnames->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
