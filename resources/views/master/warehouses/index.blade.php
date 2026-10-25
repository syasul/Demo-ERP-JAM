<x-app-layout>
    <x-slot name="title">Gudang & Lokasi Rak</x-slot>

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Master Gudang & Rak Penyimpanan</h1>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Multi-gudang dan penataan rak lokasi berbasis koordinat (Zona-Rak-Level).</p>
            </div>
        </div>

        <!-- Form Tambah Gudang Baru -->
        <x-ui.card title="Tambah Gudang Baru" description="Buat gudang atau lokasi penyimpanan fisik baru">
            <form method="POST" action="{{ route('master.warehouses.store') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                @csrf
                <div>
                    <x-input-label for="code" value="Kode Gudang *" />
                    <x-text-input id="code" name="code" type="text" class="mt-1 block w-full text-xs font-mono" placeholder="WH-SURABAYA" required />
                </div>
                <div>
                    <x-input-label for="name" value="Nama Gudang *" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full text-xs" placeholder="Gudang Cabang Surabaya" required />
                </div>
                <div>
                    <x-input-label for="address" value="Alamat Lokasi" />
                    <x-text-input id="address" name="address" type="text" class="mt-1 block w-full text-xs" placeholder="Jl. Basuki Rahmat No. 45" />
                </div>
                <div>
                    <button type="submit" class="w-full py-2 px-4 rounded-lg bg-primary-600 hover:bg-primary-500 text-white text-xs font-semibold shadow-sm transition">
                        + Tambah Gudang
                    </button>
                </div>
            </form>
        </x-ui.card>

        <!-- List Warehouses and Shelves -->
        <div class="space-y-6">
            @foreach ($warehouses as $warehouse)
                <x-ui.card :title="$warehouse->name . ' (' . $warehouse->code . ')'"
                           :description="$warehouse->address ?? 'Alamat belum diatur'">
                    <x-slot name="actions">
                        <x-ui.badge variant="primary">{{ $warehouse->locations->count() }} Lokasi Rak</x-ui.badge>
                    </x-slot>

                    <!-- Add Location Shelf Form -->
                    <div class="mb-4 p-3 rounded-lg bg-gray-50 dark:bg-gray-850 border border-gray-100 dark:border-gray-700/60">
                        <form method="POST" action="{{ route('master.warehouses.locations.store', $warehouse) }}" class="flex flex-wrap items-center gap-3">
                            @csrf
                            <div class="flex-1 min-w-[160px]">
                                <input type="text"
                                       name="code"
                                       required
                                       placeholder="Kode Rak (Format: A-01-03)"
                                       class="block w-full rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-xs font-mono px-3 py-1.5 focus:border-primary-500 focus:ring-primary-500">
                            </div>
                            <div class="flex-1 min-w-[200px]">
                                <input type="text"
                                       name="name"
                                       placeholder="Keterangan (mis: Rak Display Atas)"
                                       class="block w-full rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-xs px-3 py-1.5 focus:border-primary-500 focus:ring-primary-500">
                            </div>
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-gray-900 hover:bg-gray-800 dark:bg-gray-700 text-white text-xs font-medium transition">
                                + Tambah Rak
                            </button>
                        </form>
                        <p class="text-[11px] text-gray-400 mt-1.5">
                            * Format baku: <code>{ZONA}-{RAK}-{LEVEL}</code> (Contoh: <code>A-01-01</code>, <code>B-02-05</code>).
                        </p>
                    </div>

                    <!-- Shelves Grid -->
                    @if ($warehouse->locations->isNotEmpty())
                        <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-5 gap-2.5">
                            @foreach ($warehouse->locations as $loc)
                                <div class="p-2.5 rounded-lg border border-gray-100 dark:border-gray-700/80 bg-white dark:bg-gray-800/80 flex items-center justify-between shadow-2xs">
                                    <div>
                                        <div class="font-mono text-xs font-bold text-primary-600 dark:text-primary-400">{{ $loc->code }}</div>
                                        <div class="text-[10px] text-gray-400 truncate max-w-[110px]">{{ $loc->name ?? 'Rak Standar' }}</div>
                                    </div>
                                    <span class="w-2 h-2 rounded-full bg-emerald-400" title="Siap Digunakan"></span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-gray-400 py-2">Belum ada lokasi rak di gudang ini.</p>
                    @endif
                </x-ui.card>
            @endforeach
        </div>
    </div>
</x-app-layout>
