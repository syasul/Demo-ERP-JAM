@props([
    'title' => 'Belum ada data',
    'description' => 'Data untuk tampilan ini masih kosong atau belum tersedia.',
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'text-center py-12 px-4 rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700/60 bg-gray-50/50 dark:bg-gray-800/20']) }}>
    <div class="mx-auto w-12 h-12 flex items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-500 mb-4">
        @if ($icon)
            {{ $icon }}
        @else
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
        @endif
    </div>

    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
        {{ $title }}
    </h3>

    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
        {{ $description }}
    </p>

    @if ($slot->isNotEmpty())
        <div class="mt-6 flex justify-center">
            {{ $slot }}
        </div>
    @endif
</div>
