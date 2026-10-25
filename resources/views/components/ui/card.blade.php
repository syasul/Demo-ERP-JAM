@props([
    'title' => null,
    'description' => null,
    'actions' => null,
    'footer' => null,
    'padding' => true,
])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-xl shadow-sm overflow-hidden transition-all duration-200']) }}>
    @if ($title || $actions)
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700/60 flex flex-wrap items-center justify-between gap-4">
            <div>
                @if ($title)
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white leading-tight">
                        {{ $title }}
                    </h3>
                @endif
                @if ($description)
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ $description }}
                    </p>
                @endif
            </div>

            @if ($actions)
                <div class="flex items-center space-x-2">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif

    <div @class(['p-6' => $padding])>
        {{ $slot }}
    </div>

    @if ($footer)
        <div class="px-6 py-3 bg-gray-50/50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-700/60 text-sm text-gray-600 dark:text-gray-400">
            {{ $footer }}
        </div>
    @endif
</div>
