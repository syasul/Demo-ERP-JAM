<x-app-layout>
    <x-slot name="title">Daftar Produk Jam</x-slot>

    <div class="space-y-6">
        <!-- Header & Action Row -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Master Produk Jam</h1>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Kelola katalog produk, harga jual, dan spesifikasi arloji.</p>
            </div>

            @can('product.manage')
                <div class="flex items-center space-x-3">
                    <a href="{{ route('master.products.create') }}"
                       class="inline-flex items-center space-x-2 px-4 py-2 rounded-xl bg-primary-600 hover:bg-primary-500 text-white text-xs font-semibold shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Tambah Produk</span>
                    </a>
                </div>
            @endcan
        </div>

        <!-- Filter & Search Toolbar -->
        <x-ui.card :padding="false">
            <form method="GET" action="{{ route('master.products.index') }}" class="p-4 flex flex-wrap items-center gap-3">
                <!-- Search Input -->
                <div class="flex-1 min-w-[220px]">
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="Cari SKU, Barcode, atau Nama Jam..."
                               class="block w-full rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white pl-9 pr-3 py-2 text-xs focus:border-primary-500 focus:ring-primary-500">
                    </div>
                </div>

                <!-- Category Filter -->
                <div class="w-44">
                    <select name="category_id"
                            class="block w-full rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white py-2 text-xs focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="w-32">
                    <select name="status"
                            class="block w-full rounded-lg border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white py-2 text-xs focus:border-primary-500 focus:ring-primary-500">
                        <option value="">Semua Status</option>
                        <option value="active" @selected(request('status') === 'active')>Aktif</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                    </select>
                </div>

                <button type="submit"
                        class="px-4 py-2 rounded-lg bg-gray-900 hover:bg-gray-800 dark:bg-gray-700 dark:hover:bg-gray-600 text-white text-xs font-medium transition">
                    Filter
                </button>

                @if (request()->hasAny(['search', 'category_id', 'status']))
                    <a href="{{ route('master.products.index') }}"
                       class="px-3 py-2 rounded-lg text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 text-xs transition">
                        Reset
                    </a>
                @endif
            </form>
        </x-ui.card>

        <!-- Product Table -->
        <x-ui.table :headers="['Produk & SKU', 'Kategori', 'Harga Modal (HPP)', 'Harga Jual', 'Min. Stok', 'Status', 'Aksi']">
            @forelse ($products as $product)
                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-lg overflow-hidden flex-shrink-0 border border-gray-200 dark:border-gray-700">
                                @if ($product->image_path)
                                    <img src="{{ Storage::url($product->image_path) }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                @else
                                    ⏱
                                @endif
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900 dark:text-white">
                                    {{ $product->name }}
                                </div>
                                <div class="flex items-center space-x-2 text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    <span class="font-mono bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded">{{ $product->sku }}</span>
                                    @if ($product->barcode)
                                        <span>• {{ $product->barcode }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-xs font-medium text-gray-600 dark:text-gray-300">
                        {{ $product->category->name }}
                    </td>
                    <td class="px-6 py-4 text-xs font-mono font-medium text-gray-700 dark:text-gray-300">
                        @rupiah($product->cost_price)
                    </td>
                    <td class="px-6 py-4 text-xs font-mono font-semibold text-gray-900 dark:text-white">
                        @rupiah($product->sell_price)
                    </td>
                    <td class="px-6 py-4 text-xs font-mono text-gray-500 dark:text-gray-400">
                        {{ (int) $product->min_stock }} {{ $product->unit->code }}
                    </td>
                    <td class="px-6 py-4">
                        @if ($product->is_active)
                            <x-ui.badge variant="success" :dot="true">Aktif</x-ui.badge>
                        @else
                            <x-ui.badge variant="gray" :dot="true">Nonaktif</x-ui.badge>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center space-x-2">
                            @can('product.manage')
                                <a href="{{ route('master.products.edit', $product) }}"
                                   class="p-1.5 rounded-lg text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-950/60 transition"
                                   title="Edit Produk">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                </a>

                                <form method="POST" action="{{ route('master.products.destroy', $product) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-1.5 rounded-lg text-danger-600 hover:bg-danger-50 dark:hover:bg-danger-950/60 transition"
                                            title="Hapus Produk">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="p-8 text-center">
                        <x-ui.empty title="Tidak ada produk ditemukan" description="Coba ubah kata kunci pencarian atau filter kategori Anda." />
                    </td>
                </tr>
            @endforelse
        </x-ui.table>

        <!-- Pagination -->
        <div>
            {{ $products->links() }}
        </div>
    </div>
</x-app-layout>
