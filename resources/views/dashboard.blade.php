<x-app-layout>
    <x-slot name="title">Dashboard</x-slot>

    <div class="space-y-6">
        <!-- Welcome Banner -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-primary-700 via-primary-600 to-indigo-600 p-6 sm:p-8 text-white shadow-lg">
            <div class="relative z-10 max-w-2xl">
                <div class="inline-flex items-center space-x-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium backdrop-blur-md mb-3">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Sistem ERP Beroperasi Normal</span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    Selamat Datang, {{ Auth::user()->name }}!
                </h2>
                <p class="mt-2 text-sm text-primary-100 leading-relaxed">
                    Ringkasan operasional bisnis jam tangan Anda untuk hari ini, {{ now()->translatedFormat('l, d F Y') }}.
                </p>
            </div>

            <!-- Decorative watch clock illustration behind banner -->
            <div class="absolute -right-10 -bottom-10 opacity-15 pointer-events-none">
                <svg class="w-64 h-64" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <!-- KPI Stat Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            <x-ui.stat title="Total SKU Produk"
                       value="50 Model"
                       :delta="8.4"
                       deltaType="up"
                       deltaText="vs bulan lalu"
                       url="{{ route('master.products.index') }}">
                <x-slot name="icon">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </x-slot>
            </x-ui.stat>

            <x-ui.stat title="Gudang & Rak Aktif"
                       value="2 Gudang / 20 Rak"
                       :delta="0"
                       deltaType="neutral"
                       deltaText="kapasitas normal"
                       url="{{ route('master.warehouses.index') }}">
                <x-slot name="icon">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                    </svg>
                </x-slot>
            </x-ui.stat>

            <x-ui.stat title="Pelanggan Terdaftar"
                       value="20 Mitra"
                       :delta="15.2"
                       deltaType="up"
                       deltaText="pertumbuhan mitra"
                       url="{{ route('master.customers.index') }}">
                <x-slot name="icon">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </x-slot>
            </x-ui.stat>

            <x-ui.stat title="Pemasok Terverifikasi"
                       value="5 Distributor"
                       :delta="0"
                       deltaType="neutral"
                       deltaText="semua kontrak aktif"
                       url="{{ route('master.suppliers.index') }}">
                <x-slot name="icon">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </x-slot>
            </x-ui.stat>
        </div>

        <!-- Quick Access Action Center -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Quick Links Card -->
            <x-ui.card title="Aksi Cepat & Navigasi" description="Pintasan langsung ke tugas rutin harian" class="lg:col-span-2">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <a href="{{ route('master.products.create') }}"
                       class="p-4 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 hover:bg-white dark:hover:bg-gray-700 hover:shadow-md transition text-center group">
                        <div class="w-10 h-10 mx-auto rounded-lg bg-primary-100 dark:bg-primary-950/60 text-primary-600 dark:text-primary-400 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">Tambah Produk</span>
                    </a>

                    <a href="/qr/scan"
                       class="p-4 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 hover:bg-white dark:hover:bg-gray-700 hover:shadow-md transition text-center group">
                        <div class="w-10 h-10 mx-auto rounded-lg bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">Scan Barcode / QR</span>
                    </a>

                    <a href="{{ route('master.customers.create') }}"
                       class="p-4 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 hover:bg-white dark:hover:bg-gray-700 hover:shadow-md transition text-center group">
                        <div class="w-10 h-10 mx-auto rounded-lg bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">Mitra Pelanggan</span>
                    </a>

                    <a href="{{ route('master.warehouses.index') }}"
                       class="p-4 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 hover:bg-white dark:hover:bg-gray-700 hover:shadow-md transition text-center group">
                        <div class="w-10 h-10 mx-auto rounded-lg bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10l8 4 8-4V7M4 7l8 4 8-4M4 7l8-4 8 4" />
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">Kelola Lokasi Rak</span>
                    </a>

                    <a href="{{ route('settings.index') }}"
                       class="p-4 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 hover:bg-white dark:hover:bg-gray-700 hover:shadow-md transition text-center group">
                        <div class="w-10 h-10 mx-auto rounded-lg bg-purple-100 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            </svg>
                        </div>
                        <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">Pengaturan Toko</span>
                    </a>

                    @if (app()->environment('local'))
                        <a href="{{ route('dev.ui') }}"
                           class="p-4 rounded-xl border border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50 hover:bg-white dark:hover:bg-gray-700 hover:shadow-md transition text-center group">
                            <div class="w-10 h-10 mx-auto rounded-lg bg-rose-100 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                                </svg>
                            </div>
                            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">Katalog UI /dev/ui</span>
                        </a>
                    @endif
                </div>
            </x-ui.card>

            <!-- Right: System Information Card -->
            <x-ui.card title="Status Sistem" description="Konfigurasi parameter operasional aktif">
                <div class="space-y-3 text-xs">
                    <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700/60">
                        <span class="text-gray-500 dark:text-gray-400">Tarif PPN Resmi</span>
                        <x-ui.badge variant="primary">11.00 % (1100 bp)</x-ui.badge>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700/60">
                        <span class="text-gray-500 dark:text-gray-400">Metode Valuasi HPP</span>
                        <x-ui.badge variant="success">Moving Average</x-ui.badge>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700/60">
                        <span class="text-gray-500 dark:text-gray-400">Basis Komisi Sales</span>
                        <x-ui.badge variant="warning">Cash Basis (Payment)</x-ui.badge>
                    </div>
                    <div class="flex justify-between py-2 border-b border-gray-100 dark:border-gray-700/60">
                        <span class="text-gray-500 dark:text-gray-400">Stok Minus</span>
                        <x-ui.badge variant="danger">Dilarang (Negative Guard)</x-ui.badge>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="text-gray-500 dark:text-gray-400">Format Barcode/QR</span>
                        <span class="font-mono text-gray-700 dark:text-gray-300">ERP1|P|{SKU}</span>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
