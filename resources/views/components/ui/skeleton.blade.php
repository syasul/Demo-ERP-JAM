@props([
    'lines' => 3,
    'type' => 'lines', // lines, card, table
])

@if ($type === 'card')
    <div {{ $attributes->merge(['class' => 'p-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700/60 animate-pulse']) }}>
        <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/3 mb-4"></div>
        <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-1/2 mb-3"></div>
        <div class="h-3 bg-gray-100 dark:bg-gray-750 rounded w-2/3"></div>
    </div>
@elseif ($type === 'table')
    <div {{ $attributes->merge(['class' => 'p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700/60 animate-pulse space-y-3']) }}>
        <div class="h-8 bg-gray-200 dark:bg-gray-700 rounded w-full"></div>
        @for ($i = 0; $i < $lines; $i++)
            <div class="h-10 bg-gray-100 dark:bg-gray-750 rounded w-full"></div>
        @endfor
    </div>
@else
    <div {{ $attributes->merge(['class' => 'animate-pulse space-y-2.5']) }}>
        @for ($i = 0; $i < $lines; $i++)
            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded" style="width: {{ rand(65, 100) }}%"></div>
        @endfor
    </div>
@endif
