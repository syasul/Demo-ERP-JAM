@props([
    'fromName' => 'from',
    'toName' => 'to',
    'from' => null,
    'to' => null,
])

@php
$fromVal = old($fromName, $from ?? request($fromName, now()->startOfMonth()->toDateString()));
$toVal   = old($toName, $to ?? request($toName, now()->toDateString()));
@endphp

<div x-data="{
        from: '{{ $fromVal }}',
        to: '{{ $toVal }}',
        formatDate(d) {
            return d.toISOString().split('T')[0];
        },
        setPreset(preset) {
            const today = new Date();
            if (preset === 'today') {
                this.from = this.formatDate(today);
                this.to = this.formatDate(today);
            } else if (preset === '7days') {
                const past = new Date();
                past.setDate(today.getDate() - 6);
                this.from = this.formatDate(past);
                this.to = this.formatDate(today);
            } else if (preset === 'this_month') {
                const first = new Date(today.getFullYear(), today.getMonth(), 1);
                this.from = this.formatDate(first);
                this.to = this.formatDate(today);
            } else if (preset === 'last_month') {
                const first = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                const last = new Date(today.getFullYear(), today.getMonth(), 0);
                this.from = this.formatDate(first);
                this.to = this.formatDate(last);
            } else if (preset === 'this_year') {
                const first = new Date(today.getFullYear(), 0, 1);
                this.from = this.formatDate(first);
                this.to = this.formatDate(today);
            }
        }
     }"
     class="flex flex-wrap items-center gap-2">

    <!-- Preset dropdown / pills -->
    <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-850 p-0.5 text-xs">
        <button type="button"
                @click="setPreset('today')"
                class="px-2.5 py-1 font-medium rounded-md text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-white dark:hover:bg-gray-700 transition">
            Hari Ini
        </button>
        <button type="button"
                @click="setPreset('7days')"
                class="px-2.5 py-1 font-medium rounded-md text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-white dark:hover:bg-gray-700 transition">
            7 Hari
        </button>
        <button type="button"
                @click="setPreset('this_month')"
                class="px-2.5 py-1 font-medium rounded-md text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-white dark:hover:bg-gray-700 transition">
            Bulan Ini
        </button>
        <button type="button"
                @click="setPreset('last_month')"
                class="px-2.5 py-1 font-medium rounded-md text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-white dark:hover:bg-gray-700 transition">
            Bulan Lalu
        </button>
        <button type="button"
                @click="setPreset('this_year')"
                class="px-2.5 py-1 font-medium rounded-md text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-white dark:hover:bg-gray-700 transition">
            Tahun Ini
        </button>
    </div>

    <!-- Date input controls -->
    <div class="flex items-center space-x-1.5">
        <input type="date"
               name="{{ $fromName }}"
               x-model="from"
               class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-xs px-2.5 py-1.5 focus:border-primary-500 focus:ring-primary-500">
        <span class="text-xs text-gray-400">s/d</span>
        <input type="date"
               name="{{ $toName }}"
               x-model="to"
               class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white text-xs px-2.5 py-1.5 focus:border-primary-500 focus:ring-primary-500">
    </div>
</div>
