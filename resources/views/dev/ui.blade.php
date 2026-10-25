<x-app-layout>
    <x-slot name="title">Katalog Komponen UI Design System</x-slot>

    <div class="space-y-8 max-w-6xl mx-auto">
        <div>
            <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 text-xs font-semibold mb-2">
                <span>Dev Only (APP_ENV=local)</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Katalog Komponen UI Blade</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Design system tokens, micro-interactions, variants (normal, loading, disabled, error).</p>
        </div>

        <!-- Section 1: KPI Stats -->
        <section class="space-y-3">
            <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400">1. KPI Stat Cards (`x-ui.stat`)</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <x-ui.stat title="Penjualan Bulan Ini" value="Rp 125.500.000" :delta="12.5" deltaType="up" deltaText="vs target bulanan">
                    <x-slot name="icon">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </x-slot>
                </x-ui.stat>

                <x-ui.stat title="Retur Barang" value="Rp 2.450.000" :delta="4.1" deltaType="down" deltaText="berkurang baik">
                    <x-slot name="icon">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z" />
                        </svg>
                    </x-slot>
                </x-ui.stat>

                <x-ui.stat title="Stok Menipis" value="3 SKU" :delta="0" deltaType="neutral" deltaText="perlu restock segera">
                    <x-slot name="icon">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </x-slot>
                </x-ui.stat>

                <x-ui.stat title="Piutang Berjalan" value="Rp 45.000.000" :delta="2.0" deltaType="up" deltaText="jatuh tempo minggu ini" url="#">
                    <x-slot name="icon">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </x-slot>
                </x-ui.stat>
            </div>
        </section>

        <!-- Section 2: Badges -->
        <section class="space-y-3">
            <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400">2. Status Badges (`x-ui.badge`)</h2>
            <x-ui.card>
                <div class="flex flex-wrap items-center gap-3">
                    <x-ui.badge variant="success" :dot="true">Paid / Lunas</x-ui.badge>
                    <x-ui.badge variant="warning" :dot="true">Partial / Parsial</x-ui.badge>
                    <x-ui.badge variant="danger" :dot="true">Void / Batal</x-ui.badge>
                    <x-ui.badge variant="primary" :dot="true">Posted / Terbit</x-ui.badge>
                    <x-ui.badge variant="info" :dot="true">In Transit</x-ui.badge>
                    <x-ui.badge variant="gray" :dot="true">Draft</x-ui.badge>
                    <x-ui.badge variant="success" size="sm">Small Badge</x-ui.badge>
                    <x-ui.badge variant="primary" size="sm">Tag SKU</x-ui.badge>
                </div>
            </x-ui.card>
        </section>

        <!-- Section 3: Alerts -->
        <section class="space-y-3">
            <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400">3. Alerts & Notifikasi (`x-ui.alert`)</h2>
            <div class="space-y-3">
                <x-ui.alert type="success" title="Transaksi Berhasil">
                    Penerimaan barang GR/2026/10/0001 telah berhasil diposting dan kartu stok telah diperbarui.
                </x-ui.alert>
                <x-ui.alert type="warning" title="Peringatan Limit Kredit">
                    Pelanggan Toko Jam Abadi telah mencapai 95% dari batas plafon limit kredit (Rp 50.000.000).
                </x-ui.alert>
                <x-ui.alert type="error" title="Stok Tidak Mencukupi">
                    Produk SEIKO-000001 hanya tersisa 2 pcs di Gudang Utama, permintaan pengeluaran adalah 5 pcs.
                </x-ui.alert>
                <x-ui.alert type="info">
                    Informasi: Periode akuntansi bulan Oktober 2026 berstatus TERBUKA untuk transaksi.
                </x-ui.alert>
            </div>
        </section>

        <!-- Section 4: Money Input & Live Formatting -->
        <section class="space-y-3">
            <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400">4. Money Input Format Ribuan Live (`x-ui.money-input`)</h2>
            <x-ui.card description="Ketik angka apa saja, format titik rupiah otomatis muncul, sedangkan input mengirim angka integer murni.">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <x-input-label value="Normal Input" />
                        <div class="mt-1">
                            <x-ui.money-input name="demo_price_1" :value="1250000" />
                        </div>
                    </div>
                    <div>
                        <x-input-label value="Disabled Variant" />
                        <div class="mt-1">
                            <x-ui.money-input name="demo_price_2" :value="5000000" :disabled="true" />
                        </div>
                    </div>
                    <div>
                        <x-input-label value="Readonly Variant" />
                        <div class="mt-1">
                            <x-ui.money-input name="demo_price_3" :value="750000" :readonly="true" />
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </section>

        <!-- Section 5: Date Range with Presets -->
        <section class="space-y-3">
            <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400">5. Date Range Presets (`x-ui.date-range`)</h2>
            <x-ui.card description="Klik salah satu preset (Hari ini, 7 hari, Bulan ini, Bulan lalu, Tahun ini) untuk mengisi otomatis kedua tanggal.">
                <x-ui.date-range fromName="demo_from" toName="demo_to" />
            </x-ui.card>
        </section>

        <!-- Section 6: Skeletons & Loading States -->
        <section class="space-y-3">
            <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400">6. Loading Skeletons (`x-ui.skeleton`)</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <x-ui.skeleton type="card" />
                <x-ui.skeleton type="lines" :lines="4" class="p-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700" />
                <x-ui.skeleton type="table" :lines="2" />
            </div>
        </section>

        <!-- Section 7: Empty State Component -->
        <section class="space-y-3">
            <h2 class="text-sm font-bold uppercase tracking-wider text-gray-400">7. Empty State (`x-ui.empty`)</h2>
            <x-ui.empty title="Belum Ada Dokumen Transaksi" description="Silakan buat penerimaan barang atau invoice baru untuk melihat data di sini.">
                <button type="button" class="px-4 py-2 rounded-xl bg-primary-600 text-white text-xs font-semibold shadow-sm">
                    + Buat Transaksi Baru
                </button>
            </x-ui.empty>
        </section>
    </div>
</x-app-layout>
