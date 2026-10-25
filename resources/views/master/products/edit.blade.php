<x-app-layout>
    <x-slot name="title">Edit Produk Jam — {{ $product->sku }}</x-slot>

    <div class="max-w-4xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Edit Produk Jam</h1>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Perbarui data produk, harga jual, dan pengaturan stok.</p>
            </div>
            <a href="{{ route('master.products.index') }}" class="text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                ← Kembali ke Daftar
            </a>
        </div>

        <form method="POST" action="{{ route('master.products.update', $product) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Section 1: Informasi Dasar -->
            <x-ui.card title="Informasi Produk" description="Data identitas produk, kode SKU, dan pengelompokan">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <x-input-label for="name" value="Nama Produk Jam *" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full text-sm" :value="old('name', $product->name)" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="sku" value="Kode SKU *" />
                        <x-text-input id="sku" name="sku" type="text" class="mt-1 block w-full text-sm font-mono" :value="old('sku', $product->sku)" required />
                        <x-input-error :messages="$errors->get('sku')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="barcode" value="Kode Barcode / EAN" />
                        <x-text-input id="barcode" name="barcode" type="text" class="mt-1 block w-full text-sm font-mono" :value="old('barcode', $product->barcode)" />
                        <x-input-error :messages="$errors->get('barcode')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="category_id" value="Kategori Jam *" />
                        <select id="category_id" name="category_id" required class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(old('category_id', $product->category_id) == $cat->id)>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('category_id')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="unit_id" value="Satuan *" />
                        <select id="unit_id" name="unit_id" required class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-sm focus:border-primary-500 focus:ring-primary-500">
                            @foreach ($units as $u)
                                <option value="{{ $u->id }}" @selected(old('unit_id', $product->unit_id) == $u->id)>
                                    {{ $u->name }} ({{ $u->code }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('unit_id')" class="mt-1" />
                    </div>
                </div>
            </x-ui.card>

            <!-- Section 2: Finansial & Harga -->
            <x-ui.card title="Penetapan Harga" description="Harga beli modal dan harga jual eceran/grosir">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="cost_price" value="Harga Modal / Beli (HPP) *" />
                        <div class="mt-1">
                            <x-ui.money-input name="cost_price" :value="old('cost_price', $product->cost_price)" />
                        </div>
                        <x-input-error :messages="$errors->get('cost_price')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="sell_price" value="Harga Jual *" />
                        <div class="mt-1">
                            <x-ui.money-input name="sell_price" :value="old('sell_price', $product->sell_price)" />
                        </div>
                        <x-input-error :messages="$errors->get('sell_price')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="min_stock" value="Batas Peringatan Stok Minimum" />
                        <x-text-input id="min_stock" name="min_stock" type="number" step="1" min="0" class="mt-1 block w-full text-sm" :value="old('min_stock', (int)$product->min_stock)" />
                    </div>

                    <div>
                        <x-input-label for="image" value="Ganti Foto Produk" />
                        <input id="image" name="image" type="file" accept="image/png,image/jpeg,image/webp" class="mt-1 block w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 dark:file:bg-primary-950 dark:file:text-primary-300" />
                        @if ($product->image_path)
                            <div class="mt-2 flex items-center space-x-2 text-xs text-gray-500">
                                <img src="{{ Storage::url($product->image_path) }}" alt="{{ $product->name }}" class="w-8 h-8 rounded object-cover border">
                                <span>Foto saat ini</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700/60 flex items-center space-x-6">
                    <label class="inline-flex items-center text-xs text-gray-700 dark:text-gray-300 font-medium">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active)) class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-primary-600 shadow-sm focus:ring-primary-500 mr-2">
                        Produk Aktif untuk Transaksi
                    </label>

                    <label class="inline-flex items-center text-xs text-gray-700 dark:text-gray-300 font-medium">
                        <input type="checkbox" name="track_batch" value="1" @checked(old('track_batch', $product->track_batch)) class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-primary-600 shadow-sm focus:ring-primary-500 mr-2">
                        Lacak Nomor Batch & Kedaluwarsa (Garansi)
                    </label>
                </div>
            </x-ui.card>

            <!-- Submit Button -->
            <div class="flex items-center justify-end space-x-3">
                <a href="{{ route('master.products.index') }}" class="px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-500 text-white text-xs font-semibold shadow-md shadow-primary-500/20 transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
