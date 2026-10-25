<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{
        darkMode: localStorage.getItem('darkMode') === 'true',
        sidebarOpen: window.innerWidth >= 1024,
        sidebarExpanded: localStorage.getItem('sidebarExpanded') !== 'false',
        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('darkMode', this.darkMode);
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        },
        toggleSidebar() {
            if (window.innerWidth < 1024) {
                this.sidebarOpen = !this.sidebarOpen;
            } else {
                this.sidebarExpanded = !this.sidebarExpanded;
                localStorage.setItem('sidebarExpanded', this.sidebarExpanded);
            }
        }
      }"
      x-init="
        if (darkMode) document.documentElement.classList.add('dark');
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) sidebarOpen = true;
        });
      ">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title . ' — ' . config('app.name', 'ERP Toko Jam') : config('app.name', 'ERP Toko Jam') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-100 min-h-screen">
        <div class="flex h-screen overflow-hidden">

            <!-- Mobile Backdrop -->
            <div x-show="sidebarOpen && window.innerWidth < 1024"
                 x-transition:enter="transition-opacity ease-linear duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-300"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="sidebarOpen = false"
                 class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm lg:hidden"
                 style="display: none;"></div>

            <!-- Sidebar -->
            <aside class="fixed inset-y-0 left-0 z-50 flex flex-col bg-white dark:bg-gray-800 border-r border-gray-200/80 dark:border-gray-700/80 transition-all duration-300 ease-in-out lg:static lg:translate-x-0"
                   :class="{
                        'translate-x-0': sidebarOpen,
                        '-translate-x-full': !sidebarOpen,
                        'w-64': sidebarExpanded,
                        'w-20': !sidebarExpanded && window.innerWidth >= 1024
                   }">

                <!-- Brand / Logo Header -->
                <div class="flex items-center justify-between h-16 px-4 border-b border-gray-100 dark:border-gray-700/70">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 overflow-hidden">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-primary-600 to-primary-400 text-white flex items-center justify-center font-bold text-lg shadow-md shadow-primary-500/20 flex-shrink-0">
                            ⏱
                        </div>
                        <div x-show="sidebarExpanded || window.innerWidth < 1024" class="truncate">
                            <span class="font-bold text-base text-gray-900 dark:text-white tracking-tight">ERP Toko Jam</span>
                            <span class="block text-[10px] uppercase font-semibold text-primary-600 dark:text-primary-400">Enterprise Edition</span>
                        </div>
                    </a>

                    <!-- Close button on mobile -->
                    <button type="button"
                            @click="sidebarOpen = false"
                            class="p-1 rounded-lg text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 lg:hidden">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Navigation Links -->
                <nav class="flex-1 px-3 py-4 space-y-6 overflow-y-auto scrollbar-thin">
                    <!-- Group: Dashboard -->
                    <div>
                        <div x-show="sidebarExpanded || window.innerWidth < 1024" class="px-3 mb-2 text-[11px] font-bold tracking-wider uppercase text-gray-400 dark:text-gray-500">
                            Dashboard
                        </div>
                        <div class="space-y-1">
                            @can('dashboard.warehouse')
                                <a href="{{ route('dashboard') }}"
                                   class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('dashboard*') ? 'bg-primary-50 text-primary-600 dark:bg-primary-950/60 dark:text-primary-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700/60 hover:text-gray-900 dark:hover:text-white' }}">
                                    <svg class="w-5 h-5 flex-shrink-0 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                    </svg>
                                    <span x-show="sidebarExpanded || window.innerWidth < 1024">Dashboard</span>
                                </a>
                            @endcan
                        </div>
                    </div>

                    <!-- Group: Operasional Gudang & QR -->
                    @canany(['stock.in.create', 'stock.view', 'qr.scan', 'stock.out.create', 'stock.transfer', 'stock.opname.create'])
                        <div>
                            <div x-show="sidebarExpanded || window.innerWidth < 1024" class="px-3 mb-2 text-[11px] font-bold tracking-wider uppercase text-gray-400 dark:text-gray-500">
                                Gudang & QR Code
                            </div>
                            <div class="space-y-1">
                                @can('qr.scan')
                                    <a href="{{ route('qr.scan') }}"
                                       class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('qr.scan') ? 'bg-primary-50 text-primary-600 dark:bg-primary-950/60 dark:text-primary-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700/60 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg class="w-5 h-5 flex-shrink-0 mr-3 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                        </svg>
                                        <span x-show="sidebarExpanded || window.innerWidth < 1024">Scan Barcode / QR</span>
                                    </a>
                                @endcan

                                @can('qr.label.print')
                                    <a href="{{ route('qr.labels') }}"
                                       class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('qr.labels*') ? 'bg-primary-50 text-primary-600 dark:bg-primary-950/60 dark:text-primary-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700/60 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg class="w-5 h-5 flex-shrink-0 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                        <span x-show="sidebarExpanded || window.innerWidth < 1024">Cetak Label QR</span>
                                    </a>
                                @endcan

                                @can('stock.view')
                                    <a href="{{ route('stock.position') }}"
                                       class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('stock.position') ? 'bg-primary-50 text-primary-600 dark:bg-primary-950/60 dark:text-primary-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700/60 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg class="w-5 h-5 flex-shrink-0 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                        <span x-show="sidebarExpanded || window.innerWidth < 1024">Posisi Stok</span>
                                    </a>

                                    <a href="{{ route('stock.card') }}"
                                       class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('stock.card') ? 'bg-primary-50 text-primary-600 dark:bg-primary-950/60 dark:text-primary-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700/60 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg class="w-5 h-5 flex-shrink-0 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <span x-show="sidebarExpanded || window.innerWidth < 1024">Kartu Stok</span>
                                    </a>
                                @endcan

                                @can('stock.in.create')
                                    <a href="{{ route('receipts.index') }}"
                                       class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('receipts*') ? 'bg-primary-50 text-primary-600 dark:bg-primary-950/60 dark:text-primary-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700/60 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg class="w-5 h-5 flex-shrink-0 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                        </svg>
                                        <span x-show="sidebarExpanded || window.innerWidth < 1024">Barang Masuk (GR)</span>
                                    </a>
                                @endcan

                                @can('stock.out.create')
                                    <a href="{{ route('issues.index') }}"
                                       class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('issues*') ? 'bg-primary-50 text-primary-600 dark:bg-primary-950/60 dark:text-primary-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700/60 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg class="w-5 h-5 flex-shrink-0 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                        </svg>
                                        <span x-show="sidebarExpanded || window.innerWidth < 1024">Barang Keluar (GI)</span>
                                    </a>
                                @endcan

                                @can('stock.transfer')
                                    <a href="{{ route('transfers.index') }}"
                                       class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('transfers*') ? 'bg-primary-50 text-primary-600 dark:bg-primary-950/60 dark:text-primary-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700/60 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg class="w-5 h-5 flex-shrink-0 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                        </svg>
                                        <span x-show="sidebarExpanded || window.innerWidth < 1024">Transfer Gudang</span>
                                    </a>
                                @endcan

                                @can('stock.opname.create')
                                    <a href="{{ route('opnames.index') }}"
                                       class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('opnames*') ? 'bg-primary-50 text-primary-600 dark:bg-primary-950/60 dark:text-primary-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700/60 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg class="w-5 h-5 flex-shrink-0 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                        </svg>
                                        <span x-show="sidebarExpanded || window.innerWidth < 1024">Stock Opname</span>
                                    </a>
                                @endcan
                            </div>
                        </div>
                    @endcanany

                    <!-- Group: Master Data -->
                    @canany(['product.view', 'customer.view', 'supplier.manage'])
                        <div>
                            <div x-show="sidebarExpanded || window.innerWidth < 1024" class="px-3 mb-2 text-[11px] font-bold tracking-wider uppercase text-gray-400 dark:text-gray-500">
                                Master Data
                            </div>
                            <div class="space-y-1">
                                @can('product.view')
                                    <a href="{{ route('master.products.index') }}"
                                       class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('master.products*') ? 'bg-primary-50 text-primary-600 dark:bg-primary-950/60 dark:text-primary-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700/60 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg class="w-5 h-5 flex-shrink-0 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span x-show="sidebarExpanded || window.innerWidth < 1024">Produk Jam</span>
                                    </a>
                                @endcan

                                @can('customer.view')
                                    <a href="{{ route('master.customers.index') }}"
                                       class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('master.customers*') ? 'bg-primary-50 text-primary-600 dark:bg-primary-950/60 dark:text-primary-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700/60 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg class="w-5 h-5 flex-shrink-0 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <span x-show="sidebarExpanded || window.innerWidth < 1024">Pelanggan</span>
                                    </a>
                                @endcan

                                @can('supplier.manage')
                                    <a href="{{ route('master.suppliers.index') }}"
                                       class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('master.suppliers*') ? 'bg-primary-50 text-primary-600 dark:bg-primary-950/60 dark:text-primary-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700/60 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg class="w-5 h-5 flex-shrink-0 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        <span x-show="sidebarExpanded || window.innerWidth < 1024">Supplier</span>
                                    </a>
                                @endcan

                                @can('stock.view')
                                    <a href="{{ route('master.warehouses.index') }}"
                                       class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('master.warehouses*') ? 'bg-primary-50 text-primary-600 dark:bg-primary-950/60 dark:text-primary-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700/60 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg class="w-5 h-5 flex-shrink-0 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                                        </svg>
                                        <span x-show="sidebarExpanded || window.innerWidth < 1024">Gudang & Rak</span>
                                    </a>
                                @endcan
                            </div>
                        </div>
                    @endcanany

                    <!-- Group: Pengaturan -->
                    @canany(['settings.manage', 'user.manage'])
                        <div>
                            <div x-show="sidebarExpanded || window.innerWidth < 1024" class="px-3 mb-2 text-[11px] font-bold tracking-wider uppercase text-gray-400 dark:text-gray-500">
                                Pengaturan
                            </div>
                            <div class="space-y-1">
                                @can('user.manage')
                                    <a href="{{ route('settings.users.index') }}"
                                       class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('settings.users*') ? 'bg-primary-50 text-primary-600 dark:bg-primary-950/60 dark:text-primary-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700/60 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg class="w-5 h-5 flex-shrink-0 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                        </svg>
                                        <span x-show="sidebarExpanded || window.innerWidth < 1024">Pengguna & Role</span>
                                    </a>
                                @endcan

                                @can('settings.manage')
                                    <a href="{{ route('settings.index') }}"
                                       class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('settings.index') ? 'bg-primary-50 text-primary-600 dark:bg-primary-950/60 dark:text-primary-400 font-semibold' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700/60 hover:text-gray-900 dark:hover:text-white' }}">
                                        <svg class="w-5 h-5 flex-shrink-0 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span x-show="sidebarExpanded || window.innerWidth < 1024">Pengaturan Sistem</span>
                                    </a>
                                @endcan
                            </div>
                        </div>
                    @endcanany
                </nav>

                <!-- Sidebar Footer: Collapse Toggle (Desktop) -->
                <div class="hidden lg:flex items-center justify-between p-3 border-t border-gray-100 dark:border-gray-700/60 bg-gray-50/50 dark:bg-gray-800/50">
                    <button type="button"
                            @click="toggleSidebar()"
                            class="p-2 rounded-lg text-gray-500 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700/60 transition"
                            :title="sidebarExpanded ? 'Ciutkan Sidebar' : 'Bentangkan Sidebar'">
                        <svg class="w-5 h-5 transition-transform" :class="{ 'rotate-180': !sidebarExpanded }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                        </svg>
                    </button>
                    <span x-show="sidebarExpanded" class="text-xs text-gray-400">v2.0 Beta</span>
                </div>
            </aside>

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

                <!-- Topbar Header -->
                <header class="h-16 bg-white dark:bg-gray-800 border-b border-gray-200/80 dark:border-gray-700/80 flex items-center justify-between px-4 sm:px-6 z-10">
                    <!-- Left: Mobile toggle + Breadcrumb -->
                    <div class="flex items-center space-x-3">
                        <button type="button"
                                @click="toggleSidebar()"
                                class="p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 lg:hidden focus:outline-none">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        @if (isset($header))
                            <div>{{ $header }}</div>
                        @else
                            <div class="flex items-center space-x-2 text-sm">
                                <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">ERP</a>
                                @if (isset($title))
                                    <span class="text-gray-300 dark:text-gray-600">/</span>
                                    <span class="font-medium text-gray-800 dark:text-white">{{ $title }}</span>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Right: Search, QR scan shortcut, Dark Mode, Profile -->
                    <div class="flex items-center space-x-2 sm:space-x-3">
                        <!-- Quick Scan QR shortcut -->
                        @can('qr.scan')
                            <a href="{{ route('qr.scan') }}"
                               class="hidden sm:inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg bg-primary-50 dark:bg-primary-950/50 text-primary-600 dark:text-primary-400 text-xs font-semibold hover:bg-primary-100 dark:hover:bg-primary-900/50 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                </svg>
                                <span>Scan QR</span>
                            </a>
                        @endcan

                        <!-- Dark Mode Toggle Button -->
                        <button type="button"
                                @click="toggleDarkMode()"
                                class="p-2 rounded-lg text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700/60 transition focus:outline-none"
                                aria-label="Ganti Tema Gelap / Terang">
                            <!-- Sun icon when dark -->
                            <svg x-show="darkMode" class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <!-- Moon icon when light -->
                            <svg x-show="!darkMode" class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </button>

                        <!-- User Profile Dropdown -->
                        <div x-data="{ open: false }" class="relative">
                            <button type="button"
                                    @click="open = !open"
                                    class="flex items-center space-x-2 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700/60 focus:outline-none transition">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-primary-500 to-indigo-600 text-white flex items-center justify-center font-bold text-xs uppercase shadow-sm">
                                    {{ substr(Auth::user()->name ?? 'U', 0, 2) }}
                                </div>
                                <div class="hidden sm:block text-left">
                                    <div class="text-xs font-semibold text-gray-900 dark:text-white leading-tight">
                                        {{ Auth::user()->name ?? 'User' }}
                                    </div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400 capitalize">
                                        {{ Auth::user()->roles->first()->name ?? 'Staff' }}
                                    </div>
                                </div>
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div x-show="open"
                                 @click.away="open = false"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-48 rounded-xl bg-white dark:bg-gray-800 shadow-xl border border-gray-100 dark:border-gray-700 py-1 z-50"
                                 style="display: none;">
                                <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-700 text-xs">
                                    <p class="font-semibold text-gray-900 dark:text-white truncate">{{ Auth::user()->name }}</p>
                                    <p class="text-gray-500 dark:text-gray-400 truncate">{{ Auth::user()->email }}</p>
                                </div>

                                <a href="{{ route('profile.edit') }}"
                                   class="block px-4 py-2 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    Profil Saya
                                </a>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="w-full text-left px-4 py-2 text-xs text-danger-600 hover:bg-danger-50 dark:hover:bg-danger-950/40">
                                        Keluar (Logout)
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Page Main Content Slot -->
                <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <!-- Global Toast Notification System -->
        <x-ui.toast />
    </body>
</html>
