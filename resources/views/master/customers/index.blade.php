<x-app-layout>
    <x-slot name="title">Daftar Pelanggan / Mitra Toko</x-slot>

    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Mitra & Pelanggan</h1>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Daftar toko jam, retailer, dan pelanggan korporat.</p>
            </div>

            @can('customer.manage')
                <a href="{{ route('master.customers.create') }}"
                   class="inline-flex items-center space-x-2 px-4 py-2 rounded-xl bg-primary-600 hover:bg-primary-500 text-white text-xs font-semibold shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Tambah Pelanggan</span>
                </a>
            @endcan
        </div>

        <!-- Filter -->
        <x-ui.card :padding="false">
            <form method="GET" action="{{ route('master.customers.index') }}" class="p-4 flex items-center gap-3">
                <div class="flex-1">
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Cari kode, nama toko, atau nomor telepon..."
                           class="block w-full rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 text-xs focus:border-primary-500 focus:ring-primary-500">
                </div>
                <button type="submit" class="px-4 py-2 rounded-lg bg-gray-900 hover:bg-gray-800 dark:bg-gray-700 text-white text-xs font-medium transition">
                    Cari
                </button>
            </form>
        </x-ui.card>

        <!-- Table -->
        <x-ui.table :headers="['Kode & Nama Mitra', 'Kota & Kontak', 'Termin Bayar', 'Limit Kredit', 'Sales PIC', 'Status', 'Aksi']">
            @forelse ($customers as $c)
                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                    <td class="px-6 py-4">
                        <div class="font-semibold text-gray-900 dark:text-white">{{ $c->name }}</div>
                        <div class="font-mono text-xs text-gray-400 mt-0.5">{{ $c->code }}</div>
                    </td>
                    <td class="px-6 py-4 text-xs text-gray-600 dark:text-gray-300">
                        <div>{{ $c->city ?? 'Jakarta' }}</div>
                        <div class="text-gray-400 font-mono mt-0.5">{{ $c->phone ?? '—' }}</div>
                    </td>
                    <td class="px-6 py-4 text-xs font-medium">
                        @if ($c->payment_term_days > 0)
                            <span class="text-amber-600 dark:text-amber-400 font-semibold">{{ $c->payment_term_days }} Hari</span>
                        @else
                            <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Tunai / COD</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-xs font-mono font-semibold text-gray-900 dark:text-white">
                        @rupiah($c->credit_limit)
                    </td>
                    <td class="px-6 py-4 text-xs text-gray-600 dark:text-gray-300">
                        {{ $c->defaultSales?->user?->name ?? '—' }}
                    </td>
                    <td class="px-6 py-4">
                        @if ($c->is_active)
                            <x-ui.badge variant="success" :dot="true">Aktif</x-ui.badge>
                        @else
                            <x-ui.badge variant="gray" :dot="true">Nonaktif</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @can('customer.manage')
                            <div class="flex items-center space-x-2">
                                <a href="{{ route('master.customers.edit', $c) }}" class="p-1.5 rounded-lg text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-950/60 transition">
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
                    <td colspan="7" class="p-8 text-center">
                        <x-ui.empty title="Belum ada data pelanggan" description="Data pelanggan yang ditambahkan akan muncul di sini." />
                    </td>
                </tr>
            @endforelse
        </x-ui.table>

        <div>
            {{ $customers->links() }}
        </div>
    </div>
</x-app-layout>
