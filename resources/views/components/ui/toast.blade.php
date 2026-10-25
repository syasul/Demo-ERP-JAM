<div x-data="{
        messages: [],
        add(type, text) {
            const id = Date.now();
            this.messages.push({ id, type, text });
            setTimeout(() => this.remove(id), 5000);
        },
        remove(id) {
            this.messages = this.messages.filter(m => m.id !== id);
        },
        init() {
            @if (session('success'))
                this.add('success', '{{ session('success') }}');
            @endif
            @if (session('error'))
                this.add('error', '{{ session('error') }}');
            @endif
            @if (session('warning'))
                this.add('warning', '{{ session('warning') }}');
            @endif
            @if (session('status'))
                this.add('info', '{{ session('status') }}');
            @endif
        }
     }"
     class="fixed bottom-5 right-5 z-50 flex flex-col space-y-2 pointer-events-none max-w-sm w-full"
     role="status"
     aria-live="polite">

    <template x-for="msg in messages" :key="msg.id">
        <div x-transition:enter="transform ease-out duration-300 transition"
             x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
             x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="pointer-events-auto flex items-center justify-between p-4 rounded-xl shadow-lg border text-sm backdrop-blur-md"
             :class="{
                'bg-success-50/95 border-success-200 text-success-900 dark:bg-success-950/90 dark:border-success-800 dark:text-success-200': msg.type === 'success',
                'bg-danger-50/95 border-danger-200 text-danger-900 dark:bg-danger-950/90 dark:border-danger-800 dark:text-danger-200': msg.type === 'error',
                'bg-warning-50/95 border-warning-200 text-warning-900 dark:bg-warning-950/90 dark:border-warning-800 dark:text-warning-200': msg.type === 'warning',
                'bg-primary-50/95 border-primary-200 text-primary-900 dark:bg-primary-950/90 dark:border-primary-800 dark:text-primary-200': msg.type === 'info'
             }">

            <div class="flex items-center space-x-3">
                <template x-if="msg.type === 'success'">
                    <svg class="w-5 h-5 text-success-600 dark:text-success-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </template>
                <template x-if="msg.type === 'error'">
                    <svg class="w-5 h-5 text-danger-600 dark:text-danger-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </template>
                <template x-if="msg.type === 'warning'">
                    <svg class="w-5 h-5 text-warning-600 dark:text-warning-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </template>
                <template x-if="msg.type === 'info'">
                    <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>
                <span class="font-medium" x-text="msg.text"></span>
            </div>

            <button type="button"
                    @click="remove(msg.id)"
                    class="ml-4 p-1 rounded-md opacity-60 hover:opacity-100 transition-opacity"
                    aria-label="Tutup">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>
