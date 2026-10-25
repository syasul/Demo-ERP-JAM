<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Buat Transfer Antar Gudang</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Pindahkan stok jam tangan dari satu gudang ke gudang lain.</p>
            </div>
            <a href="{{ route('transfers.index') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400">
                &larr; Kembali
            </a>
        </div>
    </x-slot>

    <div x-data="{
        items: [{ product_id: '', qty_sent: 1 }],
        productList: {{ json_encode($products) }},
        addItem() { this.items.push({ product_id: '', qty_sent: 1 }); },
        removeItem(idx) { if(this.items.length > 1) this.items.splice(idx, 1); }
    }" class="max-w-4xl mx-auto">
        <form method="POST" action="{{ route('transfers.store') }}" class="space-y-6">
            @csrf

            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm space-y-4">
                <h3 class="font-bold text-gray-900 dark:text-white text-base">Lokasi Transfer</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Gudang Asal (Pengirim) *</label>
                        <select name="from_warehouse_id" required class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Gudang Tujuan (Penerima) *</label>
                        <select name="to_warehouse_id" required class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}" {{ $loop->iteration === 2 ? 'selected' : '' }}>{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Catatan / Alasan Transfer</label>
                        <input type="text" name="note" placeholder="Misal: Penyeimbangan stok display..."
                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-gray-900 dark:text-white text-base">Item yang Ditransfer</h3>
                    <button type="button" @click="addItem()" class="px-3 py-1.5 bg-blue-50 text-blue-600 dark:bg-blue-900/30 rounded-lg text-xs font-semibold hover:bg-blue-100">
                        + Tambah Baris
                    </button>
                </div>

                <div class="space-y-3">
                    <template x-for="(item, idx) in items" :key="idx">
                        <div class="flex items-center gap-3">
                            <div class="flex-1">
                                <select :name="'items[' + idx + '][product_id]'" x-model="item.product_id" required
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                                    <option value="">Pilih Produk Jam</option>
                                    <template x-for="p in productList" :key="p.id">
                                        <option :value="p.id" x-text="p.name + ' (' + p.sku + ')'"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="w-32">
                                <input type="number" step="0.001" min="0.001" :name="'items[' + idx + '][qty_sent]'" x-model.number="item.qty_sent" required placeholder="Qty"
                                       class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm text-center">
                            </div>
                            <button type="button" @click="removeItem(idx)" class="text-rose-500 font-bold hover:text-rose-700 px-2">&times;</button>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-sm">
                    Buat Draft Transfer
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
