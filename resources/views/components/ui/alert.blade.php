@props([
    'type' => 'info', // success, error, warning, info
    'title' => null,
    'dismissible' => true,
])

@php
$typeClasses = match ($type) {
    'success' => 'bg-success-50/80 border-success-200 text-success-800 dark:bg-success-950/40 dark:border-success-800/60 dark:text-success-300',
    'error'   => 'bg-danger-50/80 border-danger-200 text-danger-800 dark:bg-danger-950/40 dark:border-danger-800/60 dark:text-danger-300',
    'warning' => 'bg-warning-50/80 border-warning-200 text-warning-800 dark:bg-warning-950/40 dark:border-warning-800/60 dark:text-warning-300',
    default   => 'bg-primary-50/80 border-primary-200 text-primary-800 dark:bg-primary-950/40 dark:border-primary-800/60 dark:text-primary-300',
};

$icon = match ($type) {
    'success' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
    'error'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
    'warning' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />',
    default   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
};
@endphp

<div x-data="{ show: true }"
     x-show="show"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     role="status"
     {{ $attributes->merge(['class' => "p-4 rounded-xl border flex items-start space-x-3 text-sm {$typeClasses}"]) }}>

    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        {!! $icon !!}
    </svg>

    <div class="flex-1">
        @if ($title)
            <h5 class="font-semibold mb-1">{{ $title }}</h5>
        @endif
        <div class="leading-relaxed">
            {{ $slot }}
        </div>
    </div>

    @if ($dismissible)
        <button type="button"
                @click="show = false"
                class="ml-auto flex-shrink-0 -mr-1 -mt-1 p-1 rounded-md opacity-70 hover:opacity-100 focus:outline-none transition-opacity"
                aria-label="Tutup">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    @endif
</div>
