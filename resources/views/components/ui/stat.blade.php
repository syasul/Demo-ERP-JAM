@props([
    'title',
    'value',
    'delta' => null,
    'deltaType' => 'up', // up, down, neutral
    'deltaText' => 'vs periode lalu',
    'icon' => null,
    'url' => null,
])

<div {{ $attributes->merge(['class' => 'relative bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-xl p-5 shadow-sm hover:shadow-md transition-all duration-200 group']) }}>
    @if ($url)
        <a href="{{ $url }}" class="absolute inset-0 z-10" aria-label="{{ $title }}"></a>
    @endif

    <div class="flex items-start justify-between">
        <div>
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                {{ $title }}
            </p>
            <h4 class="mt-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                {{ $value }}
            </h4>
        </div>

        @if ($icon)
            <div class="p-2.5 rounded-lg bg-primary-50 dark:bg-primary-950/50 text-primary-600 dark:text-primary-400 group-hover:scale-105 transition-transform">
                {{ $icon }}
            </div>
        @endif
    </div>

    @if ($delta !== null)
        <div class="mt-4 flex items-center text-xs font-medium">
            @if ($deltaType === 'up')
                <span class="inline-flex items-center text-success-600 dark:text-success-400 mr-2 font-semibold">
                    <svg class="w-3.5 h-3.5 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                    </svg>
                    ▲ {{ $delta }}%
                </span>
            @elseif ($deltaType === 'down')
                <span class="inline-flex items-center text-danger-600 dark:text-danger-400 mr-2 font-semibold">
                    <svg class="w-3.5 h-3.5 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                    </svg>
                    ▼ {{ $delta }}%
                </span>
            @else
                <span class="inline-flex items-center text-gray-500 dark:text-gray-400 mr-2 font-semibold">
                    • {{ $delta }}%
                </span>
            @endif

            <span class="text-gray-400 dark:text-gray-500 truncate">
                {{ $deltaText }}
            </span>
        </div>
    @endif
</div>
