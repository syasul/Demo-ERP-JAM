<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Cetak Label QR Code & Barcode</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Pilih produk atau rak untuk dicetak ke stiker A4 atau printer thermal.</p>
            </div>
            <a href="{{ route('qr.scan') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                Buka Pemindai QR
            </a>
        </div>
    </x-slot>

    <div x-data="{
        template: 'a4_3x8',
        selectedTab: 'products',
        selectedItems: [],
        selectAll: false,

        toggleProduct(p) {
            const idx = this.selectedItems.findIndex(i => i.sku === p.sku);
            if (idx >= 0) {
                this.selectedItems.splice(idx, 1);
            } else {
                this.selectedItems.push({
                    type: 'P',
                    sku: p.sku,
                    name: p.name,
                    price: p.sell_price,
                    copies: 1
                });
            }
        },

        isSelected(sku) {
            return this.selectedItems.some(i => i.sku === sku);
        },

        addLocation(loc, wh) {
            this.selectedItems.push({
                type: 'L',
                sku: loc.code,
                name: wh.name + ' - ' + (loc.name || loc.code),
                warehouse_code: wh.code,
                loc_code: loc.code,
                copies: 1
            });
        }
    }" class="max-w-6xl mx-auto space-y-6">

        <form action="{{ route('qr.labels.generate') }}" method="POST" target="_blank" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            @csrf

            <!-- Left 2 Cols: Product / Shelf Selector -->
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-center gap-4 border-b border-gray-200 dark:border-gray-700 pb-3 mb-4">
                        <button type="button" @click="selectedTab = 'products'" :class="selectedTab === 'products' ? 'text-blue-600 border-b-2 border-blue-600 font-bold' : 'text-gray-500'" class="pb-2 text-sm">
                            Label Produk ({{ $products->total() }})
                        </button>
                        <button type="button" @click="selectedTab = 'locations'" :class="selectedTab === 'locations' ? 'text-blue-600 border-b-2 border-blue-600 font-bold' : 'text-gray-500'" class="pb-2 text-sm">
                            Label Rak Gudang
                        </button>
                    </div>

                    <!-- Products Table -->
                    <div x-show="selectedTab === 'products'">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-gray-50 dark:bg-gray-900 text-xs text-gray-500 uppercase">
                                    <tr>
                                        <th class="p-3 w-8">Pilih</th>
                                        <th class="p-3">Produk</th>
                                        <th class="p-3">Kategori</th>
                                        <th class="p-3 text-right">Harga Jual</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach($products as $prod)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 cursor-pointer" @click="toggleProduct({{ json_encode($prod) }})">
                                            <td class="p-3 text-center">
                                                <input type="checkbox" :checked="isSelected('{{ $prod->sku }}')" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            </td>
                                            <td class="p-3 font-medium text-gray-900 dark:text-white">
                                                {{ $prod->name }}
                                                <div class="text-xs text-gray-400">{{ $prod->sku }}</div>
                                            </td>
                                            <td class="p-3 text-xs text-gray-500">{{ $prod->category->name ?? '-' }}</td>
                                            <td class="p-3 text-right font-medium text-gray-900 dark:text-white">@rupiah($prod->sell_price)</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            {{ $products->links() }}
                        </div>
                    </div>

                    <!-- Locations Selector -->
                    <div x-show="selectedTab === 'locations'" class="space-y-4">
                        @foreach($warehouses as $wh)
                            <div class="border border-gray-100 dark:border-gray-700 rounded-xl p-3">
                                <h4 class="font-bold text-gray-900 dark:text-white text-sm mb-2">{{ $wh->name }} ({{ $wh->code }})</h4>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($wh->locations as $loc)
                                        <button type="button" @click="addLocation({{ json_encode($loc) }}, {{ json_encode($wh) }})" class="px-2.5 py-1.5 bg-gray-100 dark:bg-gray-700 hover:bg-blue-50 dark:hover:bg-blue-900/30 text-xs rounded-lg border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                                            <span>+ {{ $loc->code }}</span>
                                            <span class="text-gray-400 font-normal">({{ $loc->name ?? 'Rak' }})</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Right Col: Queue & Print Config -->
            <div class="space-y-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm space-y-4">
                    <h3 class="font-bold text-gray-900 dark:text-white text-sm">Pengaturan Kertas & Stiker</h3>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Ukuran / Format Label</label>
                        <select name="template" x-model="template" class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                            <option value="a4_3x8">Kertas A4 Grid (3 Kolom × 8 Baris - Stiker 63.5×33.9 mm)</option>
                            <option value="a4_4x10">Kertas A4 Grid (4 Kolom × 10 Baris - Stiker 48.5×25.4 mm)</option>
                            <option value="thermal_38x25">Printer Thermal Stiker (Roll 38×25 mm)</option>
                        </select>
                    </div>

                    <div class="border-t border-gray-100 dark:border-gray-700 pt-3">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-semibold text-xs text-gray-900 dark:text-white uppercase tracking-wider">Antrian Cetak (<span x-text="selectedItems.length"></span>)</h4>
                            <button type="button" @click="selectedItems = []" class="text-xs text-rose-500 hover:underline">Hapus Semua</button>
                        </div>

                        <div class="max-h-64 overflow-y-auto space-y-2 pr-1">
                            <template x-for="(item, idx) in selectedItems" :key="idx">
                                <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-900 rounded-lg text-xs">
                                    <div class="truncate mr-2">
                                        <input type="hidden" :name="'items[' + idx + '][type]'" :value="item.type">
                                        <input type="hidden" :name="'items[' + idx + '][sku]'" :value="item.sku">
                                        <input type="hidden" :name="'items[' + idx + '][name]'" :value="item.name">
                                        <input type="hidden" :name="'items[' + idx + '][price]'" :value="item.price || ''">
                                        <input type="hidden" :name="'items[' + idx + '][warehouse_code]'" :value="item.warehouse_code || ''">
                                        <input type="hidden" :name="'items[' + idx + '][loc_code]'" :value="item.loc_code || ''">

                                        <div class="font-medium text-gray-800 dark:text-gray-200 truncate" x-text="item.name"></div>
                                        <div class="text-gray-400" x-text="item.sku"></div>
                                    </div>
                                    <div class="flex items-center gap-1.5 flex-shrink-0">
                                        <span class="text-gray-500">Qty:</span>
                                        <input type="number" :name="'items[' + idx + '][copies]'" x-model.number="item.copies" min="1" max="100" class="w-12 py-1 px-1 text-center text-xs border-gray-300 dark:border-gray-600 rounded">
                                        <button type="button" @click="selectedItems.splice(idx, 1)" class="text-rose-500 ml-1 hover:text-rose-700">&times;</button>
                                    </div>
                                </div>
                            </template>
                            <div x-show="selectedItems.length === 0" class="text-center py-6 text-gray-400 text-xs">
                                Centang produk atau klik rak di sebelah kiri untuk menambahkan ke antrian cetak.
                            </div>
                        </div>
                    </div>

                    <button type="submit" :disabled="selectedItems.length === 0" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-40 text-white rounded-xl text-sm font-semibold shadow-sm flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        <span>Cetak Label PDF</span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
