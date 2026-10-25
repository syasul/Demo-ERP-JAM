@props([
    'name',
    'title' => 'Konfirmasi Tindakan',
    'content' => 'Apakah Anda yakin ingin melanjutkan tindakan ini? Data yang dihapus tidak dapat dipulihkan.',
    'confirmText' => 'Ya, Lanjutkan',
    'cancelText' => 'Batal',
    'action' => '#',
    'method' => 'POST',
])

<div x-data="{ open: false }"
     x-on:open-confirm-{{ $name }}.window="open = true"
     x-on:close-confirm-{{ $name }}.window="open = false"
     x-show="open"
     class="relative z-50"
     style="display: none;"
     role="dialog"
     aria-modal="true">

    <div x-show="open"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
         @click="open = false"></div>

    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div x-show="open"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-gray-100 dark:border-gray-700">

                <div class="p-6">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-danger-100 dark:bg-danger-950/60 text-danger-600 dark:text-danger-400 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                {{ $title }}
                            </h3>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                                {{ $content }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-800/60 px-6 py-4 flex flex-row-reverse gap-3 border-t border-gray-100 dark:border-gray-700/60">
                    <form action="{{ $action }}" method="{{ in_array(strtoupper($method), ['GET', 'POST']) ? $method : 'POST' }}">
                        @csrf
                        @if (!in_array(strtoupper($method), ['GET', 'POST']))
                            @method($method)
                        @endif
                        <button type="submit"
                                class="inline-flex justify-center rounded-lg bg-danger-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-danger-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-danger-600 transition">
                            {{ $confirmText }}
                        </button>
                    </form>
                    <button type="button"
                            @click="open = false"
                            class="inline-flex justify-center rounded-lg bg-white dark:bg-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-650 transition">
                        {{ $cancelText }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
