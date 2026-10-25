<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Pemindai QR & Barcode Gudang</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">Pindai barang masuk, pengeluaran, opname, atau cek info secara instan.</p>
            </div>
            <a href="{{ route('qr.labels') }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:bg-gray-700">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Cetak Label
            </a>
        </div>
    </x-slot>

    <div x-data="qrScanner()" x-init="initScanner()" class="max-w-4xl mx-auto space-y-6">
        <!-- Mode Tabs -->
        <div class="grid grid-cols-4 gap-2 bg-gray-200 dark:bg-gray-800 p-1.5 rounded-xl text-center font-medium text-sm">
            <button type="button" @click="setMode('in')" :class="mode === 'in' ? 'bg-white dark:bg-gray-900 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900'" class="py-2.5 rounded-lg transition-all flex items-center justify-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                <span>Masuk</span>
            </button>
            <button type="button" @click="setMode('out')" :class="mode === 'out' ? 'bg-white dark:bg-gray-900 text-amber-600 dark:text-amber-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900'" class="py-2.5 rounded-lg transition-all flex items-center justify-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                <span>Keluar</span>
            </button>
            <button type="button" @click="setMode('opname')" :class="mode === 'opname' ? 'bg-white dark:bg-gray-900 text-emerald-600 dark:text-emerald-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900'" class="py-2.5 rounded-lg transition-all flex items-center justify-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                <span>Opname</span>
            </button>
            <button type="button" @click="setMode('info')" :class="mode === 'info' ? 'bg-white dark:bg-gray-900 text-indigo-600 dark:text-indigo-400 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900'" class="py-2.5 rounded-lg transition-all flex items-center justify-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Cek Info</span>
            </button>
        </div>

        <!-- Scanner Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-4 bg-gray-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                    <span class="text-sm font-medium" x-text="cameraActive ? 'Kamera Aktif & Menunggu Kode...' : 'Kamera Non-Aktif'"></span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="toggleCamera()" class="px-3 py-1 bg-gray-800 hover:bg-gray-700 rounded-lg text-xs font-medium text-gray-300">
                        <span x-text="cameraActive ? 'Matikan Kamera' : 'Nyalakan Kamera'"></span>
                    </button>
                </div>
            </div>

            <!-- Viewport Kamera -->
            <div class="relative bg-black aspect-video sm:aspect-[21/9] flex items-center justify-center overflow-hidden">
                <div id="qr-reader" class="w-full h-full" x-show="cameraActive"></div>
                <div x-show="!cameraActive" class="text-center p-6 text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <p class="text-sm">Klik 'Nyalakan Kamera' atau gunakan Scanner Barcode USB / Input Manual di bawah.</p>
                </div>
            </div>

            <!-- Hardware Scanner & Fallback Input -->
            <div class="p-4 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 flex items-center gap-3">
                <div class="relative flex-1">
                    <input type="text" x-model="manualInput" @keydown.enter.prevent="processPayload(manualInput); manualInput = ''"
                           placeholder="Scan barcode USB atau ketik SKU / Rak lalu tekan Enter..."
                           class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm focus:ring-blue-500 focus:border-blue-500 pl-9">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    </div>
                </div>
                <button type="button" @click="processPayload(manualInput); manualInput = ''" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
                    Proses
                </button>
            </div>
        </div>

        <!-- Feedback Alert Banner -->
        <div x-show="bannerMessage" x-transition class="p-4 rounded-xl flex items-center justify-between"
             :class="bannerType === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-rose-50 text-rose-800 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300'">
            <div class="flex items-center gap-2">
                <span x-text="bannerType === 'success' ? '✅' : '❌'"></span>
                <span class="text-sm font-medium" x-text="bannerMessage"></span>
            </div>
            <button type="button" @click="bannerMessage = ''" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>

        <!-- Info Mode Result Card -->
        <div x-show="mode === 'info' && lastProduct" x-transition class="bg-white dark:bg-gray-800 p-6 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">Informasi Produk Terpindai</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-4">
                <div>
                    <span class="text-xs text-gray-500">SKU</span>
                    <p class="font-bold text-gray-900 dark:text-white" x-text="lastProduct?.sku"></p>
                </div>
                <div>
                    <span class="text-xs text-gray-500">Nama Produk</span>
                    <p class="font-bold text-gray-900 dark:text-white" x-text="lastProduct?.name"></p>
                </div>
                <div>
                    <span class="text-xs text-gray-500">Kategori / Satuan</span>
                    <p class="font-bold text-gray-900 dark:text-white" x-text="lastProduct?.category + ' / ' + lastProduct?.unit"></p>
                </div>
                <div>
                    <span class="text-xs text-gray-500">Harga Jual</span>
                    <p class="font-bold text-blue-600" x-text="formatRupiah(lastProduct?.sell_price || 0)"></p>
                </div>
            </div>
        </div>

        <!-- Document Header Options (for IN / OUT mode) -->
        <div x-show="mode === 'in' || mode === 'out'" class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm space-y-4">
            <h3 class="font-semibold text-gray-900 dark:text-white text-sm" x-text="mode === 'in' ? 'Informasi Penerimaan (Goods Receipt)' : 'Informasi Pengeluaran (Goods Issue)'"></h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Gudang Target *</label>
                    <select x-model="selectedWarehouse" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}">{{ $wh->name }} ({{ $wh->code }})</option>
                        @endforeach
                    </select>
                </div>

                <template x-if="mode === 'in'">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Supplier Pemasok *</label>
                        <select x-model="selectedSupplier" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </template>

                <template x-if="mode === 'out'">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Alasan Pengeluaran *</label>
                        <select x-model="issueType" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-white text-sm">
                            <option value="damaged">Barang Rusak / Cacat</option>
                            <option value="adjustment">Penyesuaian Internal</option>
                            <option value="return_supplier">Retur ke Supplier</option>
                        </select>
                    </div>
                </template>

                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Rak Aktif Saat Ini</label>
                    <div class="px-3 py-2 bg-gray-100 dark:bg-gray-900 rounded-lg text-sm font-semibold text-gray-800 dark:text-gray-200" x-text="activeLocation ? activeLocation.location_code : 'Semua Rak (Default)'"></div>
                </div>
            </div>
        </div>

        <!-- Scanned Items List Table -->
        <div x-show="mode === 'in' || mode === 'out' || mode === 'opname'" class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-white text-sm">Daftar Item Terpindai</h3>
                    <p class="text-xs text-gray-500">Total: <span x-text="scannedItems.length"></span> produk</p>
                </div>
                <button type="button" @click="clearItems()" class="text-xs text-rose-600 hover:text-rose-700 font-medium">
                    Kosongkan List
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-900 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-4 py-3">Produk</th>
                            <th class="px-4 py-3">Rak</th>
                            <th class="px-4 py-3 text-center">Jumlah</th>
                            <th class="px-4 py-3 text-right" x-show="mode === 'in'">Harga Modal</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <template x-for="(item, index) in scannedItems" :key="index">
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 dark:text-white" x-text="item.name"></div>
                                    <div class="text-xs text-gray-500" x-text="item.sku"></div>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400" x-text="item.location_code || '-'"></td>
                                <td class="px-4 py-3 text-center">
                                    <div class="inline-flex items-center gap-1">
                                        <button type="button" @click="if(item.qty > 1) item.qty--" class="w-6 h-6 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 hover:bg-gray-200 flex items-center justify-center font-bold">-</button>
                                        <input type="number" x-model.number="item.qty" min="1" class="w-14 text-center py-1 px-1 text-sm border-gray-300 dark:border-gray-600 rounded bg-transparent">
                                        <button type="button" @click="item.qty++" class="w-6 h-6 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 hover:bg-gray-200 flex items-center justify-center font-bold">+</button>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right" x-show="mode === 'in'">
                                    <input type="number" x-model.number="item.unit_cost" class="w-24 text-right py-1 px-2 text-sm border-gray-300 dark:border-gray-600 rounded">
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button type="button" @click="removeItem(index)" class="text-rose-500 hover:text-rose-700 text-xs">Hapus</button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="scannedItems.length === 0">
                            <td colspan="5" class="py-8 text-center text-gray-400 text-sm">
                                Belum ada item yang dipindai. Arahkan kamera atau gunakan scanner barcode.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3" x-show="scannedItems.length > 0">
                <button type="button" @click="submitCommit()" :disabled="isSubmitting"
                        class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-xl text-sm font-semibold shadow-sm transition-all flex items-center gap-2">
                    <span x-show="isSubmitting" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                    <span x-text="mode === 'in' ? 'Posting Penerimaan Barang' : 'Posting Pengeluaran Barang'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Html5Qrcode Library & Web Audio Beep Synthesis -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        function qrScanner() {
            return {
                mode: 'in',
                cameraActive: false,
                html5QrCode: null,
                manualInput: '',
                lastPayload: '',
                lastScanTime: 0,
                bannerMessage: '',
                bannerType: 'success',
                selectedWarehouse: '{{ $warehouses->first()->id ?? 1 }}',
                selectedSupplier: '{{ $suppliers->first()->id ?? 1 }}',
                issueType: 'damaged',
                activeLocation: null,
                lastProduct: null,
                isSubmitting: false,
                scannedItems: [],

                initScanner() {
                    const saved = sessionStorage.getItem('erp_scan_items');
                    if (saved) {
                        try { this.scannedItems = JSON.parse(saved); } catch(e) {}
                    }
                    this.$watch('scannedItems', val => {
                        sessionStorage.setItem('erp_scan_items', JSON.stringify(val));
                    });
                },

                setMode(newMode) {
                    this.mode = newMode;
                    this.bannerMessage = '';
                },

                playBeep(isSuccess = true) {
                    try {
                        const ctx = new (window.AudioContext || window.webkitAudioContext)();
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.connect(gain);
                        gain.connect(ctx.destination);

                        if (isSuccess) {
                            osc.frequency.setValueAtTime(880, ctx.currentTime); // High pitch beep
                            gain.gain.setValueAtTime(0.1, ctx.currentTime);
                            osc.start();
                            osc.stop(ctx.currentTime + 0.12);
                            if (navigator.vibrate) navigator.vibrate(60);
                        } else {
                            osc.frequency.setValueAtTime(320, ctx.currentTime); // Low warning buzz
                            gain.gain.setValueAtTime(0.15, ctx.currentTime);
                            osc.start();
                            osc.stop(ctx.currentTime + 0.25);
                            if (navigator.vibrate) navigator.vibrate([100, 50, 100]);
                        }
                    } catch(e) {}
                },

                formatRupiah(num) {
                    return 'Rp ' + Number(num).toLocaleString('id-ID');
                },

                toggleCamera() {
                    if (this.cameraActive) {
                        this.stopCamera();
                    } else {
                        this.startCamera();
                    }
                },

                startCamera() {
                    this.html5QrCode = new Html5Qrcode("qr-reader");
                    const config = { fps: 10, qrbox: { width: 250, height: 250 } };

                    this.html5QrCode.start(
                        { facingMode: "environment" },
                        config,
                        (decodedText) => {
                            this.processPayload(decodedText);
                        },
                        (errorMessage) => {}
                    ).then(() => {
                        this.cameraActive = true;
                    }).catch(err => {
                        this.bannerMessage = 'Tidak dapat mengakses kamera. Pastikan izin kamera telah diberikan.';
                        this.bannerType = 'error';
                        this.cameraActive = false;
                    });
                },

                stopCamera() {
                    if (this.html5QrCode) {
                        this.html5QrCode.stop().then(() => {
                            this.cameraActive = false;
                        }).catch(() => {
                            this.cameraActive = false;
                        });
                    }
                },

                processPayload(payload) {
                    if (!payload || payload.trim() === '') return;

                    const now = Date.now();
                    // Debounce: ignore exact same payload within 1.5 seconds
                    if (payload === this.lastPayload && (now - this.lastScanTime) < 1500) {
                        return;
                    }

                    this.lastPayload = payload;
                    this.lastScanTime = now;

                    fetch('{{ route("api.qr.resolve") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            payload: payload,
                            mode: this.mode,
                            warehouse_id: this.selectedWarehouse
                        })
                    })
                    .then(res => res.json().then(data => ({ status: res.status, ok: res.ok, body: data })))
                    .then(response => {
                        if (!response.ok) {
                            this.playBeep(false);
                            this.bannerMessage = response.body.message || 'QR/Barcode tidak valid.';
                            this.bannerType = 'error';
                            return;
                        }

                        this.playBeep(true);
                        const result = response.body;

                        if (result.type === 'location') {
                            this.activeLocation = result.data;
                            this.bannerMessage = `Rak aktif diatur ke: ${result.data.location_code}`;
                            this.bannerType = 'success';
                            return;
                        }

                        if (result.type === 'product') {
                            this.lastProduct = result.data;

                            if (this.mode === 'info') {
                                this.bannerMessage = `Info produk ${result.data.name} berhasil dimuat.`;
                                this.bannerType = 'success';
                                return;
                            }

                            // Check duplicate in list
                            const existing = this.scannedItems.find(i => i.product_id === result.data.id && i.location_id === (this.activeLocation?.location_id || null));
                            if (existing) {
                                existing.qty += 1;
                            } else {
                                this.scannedItems.push({
                                    product_id: result.data.id,
                                    sku: result.data.sku,
                                    name: result.data.name,
                                    unit_cost: result.data.cost_price,
                                    location_id: this.activeLocation?.location_id || null,
                                    location_code: this.activeLocation?.location_code || null,
                                    qty: 1
                                });
                            }

                            this.bannerMessage = `Ditambahkan: ${result.data.name} (Qty: 1)`;
                            this.bannerType = 'success';
                        }
                    })
                    .catch(err => {
                        this.playBeep(false);
                        this.bannerMessage = 'Terjadi kesalahan koneksi server.';
                        this.bannerType = 'error';
                    });
                },

                removeItem(index) {
                    this.scannedItems.splice(index, 1);
                },

                clearItems() {
                    this.scannedItems = [];
                    sessionStorage.removeItem('erp_scan_items');
                },

                submitCommit() {
                    if (this.scannedItems.length === 0) return;
                    this.isSubmitting = true;

                    // Generate client idempotency key
                    const idempotencyKey = 'key_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

                    fetch('{{ route("api.qr.commit") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Idempotency-Key': idempotencyKey
                        },
                        body: JSON.stringify({
                            mode: this.mode,
                            warehouse_id: this.selectedWarehouse,
                            supplier_id: this.selectedSupplier,
                            issue_type: this.issueType,
                            items: this.scannedItems
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        this.isSubmitting = false;
                        if (data.status === 'success') {
                            this.playBeep(true);
                            this.clearItems();
                            alert(`Berhasil! Dokumen: ${data.document_number} telah diposting ke sistem.`);
                        } else {
                            this.playBeep(false);
                            alert(data.message || 'Gagal memposting data.');
                        }
                    })
                    .catch(err => {
                        this.isSubmitting = false;
                        this.playBeep(false);
                        alert('Terjadi kesalahan jaringan.');
                    });
                }
            };
        }
    </script>
</x-app-layout>
