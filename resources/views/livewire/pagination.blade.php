@if ($paginator->hasPages())
<nav role="navigation" aria-label="{{ __('general.pagination.navigation') }}" class="mt-4">
    <div
        class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-200 mt-4 pt-4 pb-4 dark:border-gray-700">
        <div class="mb-4 sm:mb-0 text-center sm:text-left">
            <p class="text-sm text-gray-700 dark:text-gray-400 leading-5">
                {{ __('general.pagination.shown') }}
                <span class="font-medium">{{ $paginator->firstItem() }}</span>
                {{ __('general.pagination.up_to') }}
                <span class="font-medium">{{ $paginator->lastItem() }}</span>
                {{ __('general.pagination.of') }}
                <span class="font-medium">{{ $paginator->total() }}</span>
                {{ __('general.pagination.items') }}
            </p>
        </div>

        <div class="flex justify-center sm:justify-end">
            <span class="flex items-center space-x-2">
                @if ($paginator->onFirstPage())
                    <span
                        class="relative inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-500 text-sm font-semibold rounded-sm min-w-[40px] opacity-50 cursor-not-allowed"
                        aria-disabled="true" aria-label="{{ __('general.pagination.previous') }}">
                        <i class="fas fa-chevron-left py-1"></i>
                    </span>
                @else
                    <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled"
                        class="relative inline-flex items-center justify-center px-4 py-2 bg-indigo-50 text-[#490BF4] hover:text-white hover:bg-[#490BF4] dark:text-white dark:bg-indigo-500 text-sm font-semibold rounded-sm min-w-[40px] transition-colors duration-200"
                        rel="prev" aria-label="{{ __('general.pagination.previous') }}">
                        <i class="fas fa-chevron-left py-1"></i>
                    </button>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span
                            class="relative inline-flex items-center justify-center px-2 py-2 text-sm font-medium text-gray-600">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span wire:key="paginator-{{ $paginator->getPageName() }}-page-{{ $page }}"
                                    class="z-10 bg-indigo-500 text-white font-semibold dark:bg-[#490BF4] relative inline-flex items-center justify-center px-4 py-2 text-sm rounded-sm min-w-[40px]"
                                    aria-current="page">{{ $page }}</span>
                            @else
                                <button type="button" wire:key="paginator-{{ $paginator->getPageName() }}-page-{{ $page }}"
                                    wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                    class="relative inline-flex items-center justify-center px-4 py-2 bg-indigo-50 text-[#490BF4] hover:text-white hover:bg-[#490BF4] dark:text-white dark:bg-indigo-500 text-sm font-semibold rounded-sm min-w-[40px] transition-colors duration-200">{{ $page }}</button>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled"
                        class="relative inline-flex items-center justify-center px-4 py-2 bg-indigo-50 text-[#490BF4] hover:text-white hover:bg-[#490BF4] dark:text-white dark:bg-indigo-500 text-sm font-semibold rounded-sm min-w-[40px] transition-colors duration-200"
                        rel="next" aria-label="{{ __('general.pagination.next') }}">
                        <i class="fas fa-chevron-right py-1"></i>
                    </button>
                @else
                    <span
                        class="relative inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-500 text-sm font-semibold rounded-sm min-w-[40px] opacity-50 cursor-not-allowed"
                        aria-disabled="true" aria-label="{{ __('general.pagination.next') }}">
                        <i class="fas fa-chevron-right py-1"></i>
                    </span>
                @endif
            </span>
        </div>
    </div>
</nav>
@endif
