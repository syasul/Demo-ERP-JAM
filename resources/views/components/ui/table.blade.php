@props([
    'headers' => [],
    'sticky' => false,
])

<div {{ $attributes->merge(['class' => 'overflow-x-auto rounded-xl border border-gray-100 dark:border-gray-700/60 shadow-sm']) }}>
    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700/60 text-left text-sm">
        @if (!empty($headers))
            <thead class="bg-gray-50/80 dark:bg-gray-800/80 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider {{ $sticky ? 'sticky top-0 z-10 backdrop-blur' : '' }}">
                <tr>
                    @foreach ($headers as $header)
                        <th scope="col" class="px-6 py-3.5">
                            {{ $header }}
                        </th>
                    @endforeach
                </tr>
            </thead>
        @elseif (isset($headerSlot))
            <thead class="bg-gray-50/80 dark:bg-gray-800/80 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider {{ $sticky ? 'sticky top-0 z-10 backdrop-blur' : '' }}">
                {{ $headerSlot }}
            </thead>
        @endif

        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200">
            {{ $slot }}
        </tbody>

        @if (isset($footerSlot))
            <tfoot class="bg-gray-50 dark:bg-gray-800 font-semibold text-gray-900 dark:text-white border-t border-gray-200 dark:border-gray-700">
                {{ $footerSlot }}
            </tfoot>
        @endif
    </table>
</div>
