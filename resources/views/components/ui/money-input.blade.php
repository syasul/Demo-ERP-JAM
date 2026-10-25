@props([
    'name',
    'value' => 0,
    'id' => null,
    'placeholder' => '0',
    'disabled' => false,
    'readonly' => false,
])

@php
$id = $id ?? $name;
$initialValue = (int) old($name, $value ?? 0);
@endphp

<div x-data="{
        raw: {{ $initialValue }},
        formatted: '',
        format(val) {
            if (!val && val !== 0) return '';
            let isNeg = val < 0;
            let str = Math.abs(val).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            return isNeg ? '-' + str : str;
        },
        update(e) {
            let input = e.target.value;
            let isNeg = input.includes('-');
            let clean = input.replace(/[^\d]/g, '');
            if (!clean) {
                this.raw = 0;
                this.formatted = '';
                return;
            }
            let num = parseInt(clean, 10);
            this.raw = isNeg ? -num : num;
            this.formatted = this.format(this.raw);
        },
        init() {
            this.formatted = this.raw ? this.format(this.raw) : '';
        }
     }"
     class="relative rounded-lg shadow-sm">

    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Rp</span>
    </div>

    <!-- Hidden field with exact integer value for form submission -->
    <input type="hidden"
           :name="'{{ $name }}'"
           :value="raw">

    <!-- Visible text input with live Indonesian thousand dot separators -->
    <input type="text"
           id="{{ $id }}"
           x-model="formatted"
           @input="update($event)"
           placeholder="{{ $placeholder }}"
           @if ($disabled) disabled @endif
           @if ($readonly) readonly @endif
           {{ $attributes->merge(['class' => 'block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white pl-9 pr-3 py-2 text-right text-sm font-mono focus:border-primary-500 focus:ring-primary-500 disabled:opacity-60 disabled:bg-gray-100 dark:disabled:bg-gray-800']) }}>
</div>
