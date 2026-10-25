<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Input Pengeluaran Barang Baru</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Pencatatan pengeluaran barang jam tangan dari gudang.</p>
            </div>
            <a href="{{ route('issues.index') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400">
                &larr; Kembali ke Daftar
            </a>
        </div>
    </x-slot>

    <div x-data="goodsIssueForm({{ json_encode($products) }})" class="max-w-5xl mx-auto">
        <form method="POST" action="{{ route('issues.store') }}" class="space-y-6">
            @csrf

            <!-- Header Info -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm space-y-4">
                <h3 class="font-bold text-gray-900 dark:text-white text-base">Informasi Pengeluaran</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Gudang Asal *</label>
                        <select name="warehouse_id" x-model="selectedWarehouse" required class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->name }} ({{ $w->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Tipe Pengeluaran *</label>
                        <select name="type" required class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                            <option value="damaged">Barang Rusak / Hilang</option>
                            <option value="adjustment">Penyesuaian Opname</option>
                            <option value="return_supplier">Retur ke Supplier</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Tanggal Keluar *</label>
                        <input type="date" name="issue_date" value="{{ date('Y-m-d') }}" required
                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Nama Penerima / PIC</label>
                        <input type="text" name="recipient_name" placeholder="Penerima atau kurir..."
                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Alamat Tujuan / Keterangan</label>
                        <input type="text" name="shipping_address" placeholder="Alamat atau keterangan pengiriman..."
                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-gray-900 dark:text-white text-base">Daftar Barang yang Dikeluarkan</h3>
                    <button type="button" @click="addItem()" class="px-3 py-1.5 bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 rounded-lg text-xs font-semibold hover:bg-blue-100">
                        + Tambah Baris
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900 text-xs text-gray-500 uppercase">
                            <tr>
                                <th class="p-3">Produk *</th>
                                <th class="p-3 w-40 text-center">Stok Tersedia</th>
                                <th class="p-3 w-36 text-center">Jumlah Keluar *</th>
                                <th class="p-3 w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <template x-for="(item, index) in items" :key="index">
                                <tr>
                                    <td class="p-3">
                                        <select :name="'items[' + index + '][product_id]'" x-model="item.product_id" @change="fetchStock(index)" required
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                                            <option value="">Pilih Produk Jam</option>
                                            <template x-for="p in productList" :key="p.id">
                                                <option :value="p.id" x-text="p.name + ' (' + p.sku + ')'"></option>
                                            </template>
                                        </select>
                                    </td>
                                    <td class="p-3 text-center">
                                        <span class="inline-block px-2.5 py-1 rounded-md text-xs font-bold"
                                              :class="item.available >= item.qty ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300'"
                                              x-text="item.available !== null ? item.available + ' Pcs' : 'Pilih Produk'"></span>
                                    </td>
                                    <td class="p-3">
                                        <input type="number" step="0.001" min="0.001" :name="'items[' + index + '][qty]'" x-model.number="item.qty" required
                                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm text-center">
                                    </td>
                                    <td class="p-3 text-center">
                                        <button type="button" @click="removeItem(index)" class="text-rose-500 hover:text-rose-700 font-bold">&times;</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Submit Actions -->
            <div class="flex items-center justify-end gap-3">
                <button type="submit" name="action" value="draft" class="px-5 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 font-semibold text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
                    Simpan Sebagai Draft
                </button>
                <button type="submit" name="action" value="post" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 font-semibold text-sm text-white shadow-sm">
                    Simpan & Posting Pengeluaran
                </button>
            </div>
        </form>
    </div>

    <script>
        function goodsIssueForm(products) {
            return {
                productList: products,
                selectedWarehouse: '{{ $warehouses->first()->id ?? 1 }}',
                items: [
                    { product_id: '', qty: 1, available: null }
                ],

                addItem() {
                    this.items.push({ product_id: '', qty: 1, available: null });
                },

                removeItem(idx) {
                    if (this.items.length > 1) {
                        this.items.splice(idx, 1);
                    }
                },

                fetchStock(idx) {
                    const pid = this.items[idx].product_id;
                    if (!pid) return;

                    fetch(`{{ route('api.stock.available') }}?product=${pid}&warehouse=${this.selectedWarehouse}`)
                        .then(res => res.json())
                        .then(data => {
                            this.items[idx].available = Number(data.available || 0);
                        })
                        .catch(() => {
                            this.items[idx].available = 0;
                        });
                }
            };
        }
    </script>
</x-app-layout>
