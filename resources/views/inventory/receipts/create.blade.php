<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Input Penerimaan Barang Baru</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Catat penerimaan fisik barang jam tangan dari supplier.</p>
            </div>
            <a href="{{ route('receipts.index') }}" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400">
                &larr; Kembali ke Daftar
            </a>
        </div>
    </x-slot>

    <div x-data="goodsReceiptForm({{ json_encode($products) }})" class="max-w-5xl mx-auto">
        <form method="POST" action="{{ route('receipts.store') }}" class="space-y-6">
            @csrf

            <!-- Header Info -->
            <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm space-y-4">
                <h3 class="font-bold text-gray-900 dark:text-white text-base">Informasi Dokumen</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Supplier Pemasok *</label>
                        <select name="supplier_id" required class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                            <option value="">Pilih Supplier</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Gudang Tujuan *</label>
                        <select name="warehouse_id" x-model="selectedWarehouse" required class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}">{{ $w->name }} ({{ $w->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Tanggal Terima *</label>
                        <input type="date" name="receipt_date" value="{{ date('Y-m-d') }}" required
                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">No. Surat Jalan Supplier</label>
                        <input type="text" name="supplier_ref" placeholder="Misal: SJ-2026/0891"
                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-1">Catatan Tambahan</label>
                        <input type="text" name="note" placeholder="Kondisi barang, armada pengirim, dll..."
                               class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-gray-900 dark:text-white text-base">Rincian Barang Masuk</h3>
                    <button type="button" @click="addItem()" class="px-3 py-1.5 bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 rounded-lg text-xs font-semibold hover:bg-blue-100">
                        + Tambah Baris
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900 text-xs text-gray-500 uppercase">
                            <tr>
                                <th class="p-3">Produk *</th>
                                <th class="p-3 w-32">Jumlah (Qty) *</th>
                                <th class="p-3 w-44">Harga Beli Satuan (Rp) *</th>
                                <th class="p-3 w-40 text-right">Subtotal</th>
                                <th class="p-3 w-12"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <template x-for="(item, index) in items" :key="index">
                                <tr>
                                    <td class="p-3">
                                        <select :name="'items[' + index + '][product_id]'" x-model="item.product_id" @change="onProductSelect(index)" required
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                                            <option value="">Pilih Produk Jam</option>
                                            <template x-for="p in productList" :key="p.id">
                                                <option :value="p.id" x-text="p.name + ' (' + p.sku + ')'"></option>
                                            </template>
                                        </select>
                                    </td>
                                    <td class="p-3">
                                        <input type="number" step="0.001" min="0.001" :name="'items[' + index + '][qty]'" x-model.number="item.qty" required
                                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm text-center">
                                    </td>
                                    <td class="p-3">
                                        <input type="number" min="0" :name="'items[' + index + '][unit_cost]'" x-model.number="item.unit_cost" required
                                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm text-right">
                                    </td>
                                    <td class="p-3 text-right font-bold text-gray-900 dark:text-white" x-text="formatRupiah(item.qty * item.unit_cost)">
                                    </td>
                                    <td class="p-3 text-center">
                                        <button type="button" @click="removeItem(index)" class="text-rose-500 hover:text-rose-700 font-bold">&times;</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40">
                            <tr>
                                <td colspan="3" class="p-3 font-bold text-right text-gray-900 dark:text-white">Total Estimasi Biaya:</td>
                                <td class="p-3 text-right font-black text-blue-600 dark:text-blue-400 text-base" x-text="formatRupiah(calculateGrandTotal())"></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Submit Actions -->
            <div class="flex items-center justify-end gap-3">
                <button type="submit" name="action" value="draft" class="px-5 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 font-semibold text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800">
                    Simpan Sebagai Draft
                </button>
                <button type="submit" name="action" value="post" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 font-semibold text-sm text-white shadow-sm">
                    Simpan & Posting Sekarang
                </button>
            </div>
        </form>
    </div>

    <script>
        function goodsReceiptForm(products) {
            return {
                productList: products,
                selectedWarehouse: '{{ $warehouses->first()->id ?? 1 }}',
                items: [
                    { product_id: '', qty: 1, unit_cost: 0 }
                ],

                addItem() {
                    this.items.push({ product_id: '', qty: 1, unit_cost: 0 });
                },

                removeItem(idx) {
                    if (this.items.length > 1) {
                        this.items.splice(idx, 1);
                    }
                },

                onProductSelect(idx) {
                    const pid = this.items[idx].product_id;
                    const found = this.productList.find(p => p.id == pid);
                    if (found) {
                        this.items[idx].unit_cost = found.cost_price || 0;
                    }
                },

                calculateGrandTotal() {
                    return this.items.reduce((sum, item) => sum + (Number(item.qty || 0) * Number(item.unit_cost || 0)), 0);
                },

                formatRupiah(amount) {
                    return 'Rp ' + Number(amount || 0).toLocaleString('id-ID');
                }
            };
        }
    </script>
</x-app-layout>
