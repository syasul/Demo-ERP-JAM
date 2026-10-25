<x-app-layout>
    <x-slot name="title">Daftar Pemasok / Supplier</x-slot>

    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Daftar Supplier Jam</h1>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Distributor resmi arloji, suku cadang, dan aksesoris.</p>
            </div>

            @can('supplier.manage')
                <a href="{{ route('master.suppliers.create') }}"
                   class="inline-flex items-center space-x-2 px-4 py-2 rounded-xl bg-primary-600 hover:bg-primary-500 text-white text-xs font-semibold shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Supplier</span>
                </a>
            @endcan
        </div>

        <x-ui.table :headers="['Kode & Nama Supplier', 'Kontak', 'Alamat', 'Termin Bayar', 'Status', 'Aksi']">
            @forelse ($suppliers as $s)
                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                    <td class="px-6 py-4">
                        <div class="font-semibold text-gray-900 dark:text-white">{{ $s->name }}</div>
                        <div class="font-mono text-xs text-gray-400 mt-0.5">{{ $s->code }}</div>
                    </td>
                    <td class="px-6 py-4 text-xs text-gray-600 dark:text-gray-300">
                        <div>{{ $s->phone ?? '—' }}</div>
                        <div class="text-gray-400 mt-0.5">{{ $s->email ?? '—' }}</div>
                    </td>
                    <td class="px-6 py-4 text-xs text-gray-600 dark:text-gray-300 max-w-xs truncate">
                        {{ $s->address ?? '—' }}
                    </td>
                    <td class="px-6 py-4 text-xs font-mono font-medium text-gray-700 dark:text-gray-300">
                        {{ $s->payment_term_days }} Hari
                    </td>
                    <td class="px-6 py-4">
                        @if ($s->is_active)
                            <x-ui.badge variant="success" :dot="true">Aktif</x-ui.badge>
                        @else
                            <x-ui.badge variant="gray" :dot="true">Nonaktif</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @can('supplier.manage')
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('master.suppliers.edit', $s) }}" class="p-1.5 rounded-lg text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-950/60 transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </a>
                            </div>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="p-8 text-center">
                        <x-ui.empty title="Belum ada data supplier" description="Data distributor jam akan muncul di sini." />
                    </td>
                </tr>
            @endforelse
        </x-ui.table>

        <div>
            {{ $suppliers->links() }}
        </div>
    </div>
</x-app-layout>
