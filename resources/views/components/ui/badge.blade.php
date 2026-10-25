@props([
    'variant' => 'gray', // primary, success, warning, danger, info, gray
    'size' => 'md',      // sm, md
    'dot' => false,
])

@php
$variantClasses = match ($variant) {
    'primary' => 'bg-primary-50 text-primary-700 ring-primary-600/20 dark:bg-primary-950/60 dark:text-primary-300 dark:ring-primary-400/30',
    'success' => 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-950/60 dark:text-success-300 dark:ring-success-500/30',
    'warning' => 'bg-warning-50 text-warning-800 ring-warning-600/20 dark:bg-warning-950/60 dark:text-warning-300 dark:ring-warning-500/30',
    'danger'  => 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-950/60 dark:text-danger-300 dark:ring-danger-500/30',
    'info'    => 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-950/60 dark:text-sky-300 dark:ring-sky-500/30',
    default   => 'bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-600/30',
};

$sizeClasses = match ($size) {
    'sm' => 'text-[11px] px-2 py-0.5',
    default => 'text-xs px-2.5 py-1',
};

$dotClasses = match ($variant) {
    'primary' => 'bg-primary-500',
    'success' => 'bg-success-500',
    'warning' => 'bg-warning-500',
    'danger'  => 'bg-danger-500',
    'info'    => 'bg-sky-500',
    default   => 'bg-gray-400',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center font-medium rounded-full ring-1 ring-inset {$variantClasses} {$sizeClasses}"]) }}>
    @if ($dot)
        <span class="w-1.5 h-1.5 rounded-full {{ $dotClasses }} mr-1.5"></span>
    @endif
    {{ $slot }}
</span>
