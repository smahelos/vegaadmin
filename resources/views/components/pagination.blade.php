@if ($paginator->hasPages())
<nav role="navigation" aria-label="{{ __('general.pagination.navigation') }}" class="mt-4">
    <div
        class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-200 mt-4 pt-4 pb-4 dark:border-gray-700">
        <div class="mb-4 sm:mb-0 text-center sm:text-left float-left">
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

        <div class="flex justify-center sm:justify-end float-right">
            <span class="flex items-center space-x-2">
                {{-- Previous page --}}
                @if ($paginator->onFirstPage())
                    <span
                        class="relative inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-500 text-sm font-semibold rounded-sm min-w-[40px] opacity-50 cursor-not-allowed"
                        aria-disabled="true" aria-label="{{ __('general.pagination.previous') }}">
                        <i class="fas fa-chevron-left py-1"></i>
                    </span>
                @else
                    @if (str_contains(request()->url(), 'livewire') || request()->routeIs('livewire*'))
                        <button type="button" wire:click="setPage({{ $paginator->currentPage() - 1 }})"
                            class="relative inline-flex items-center justify-center px-4 py-2 bg-indigo-50 text-[#490BF4] hover:text-white hover:bg-[#490BF4] text-sm font-semibold rounded-sm min-w-[40px] transition-colors duration-200"
                            rel="prev" aria-label="{{ __('general.pagination.previous') }}">
                            <i class="fas fa-chevron-left py-1"></i>
                        </button>
                    @else
                        <a href="{{ route(request()->route()->getName(), array_merge(request()->query(), ['page' => $paginator->currentPage() - 1, 'locale' => app()->getLocale()])) }}"
                            class="relative inline-flex items-center justify-center px-4 py-2 bg-indigo-50 text-[#490BF4] hover:text-white hover:bg-[#490BF4] text-sm font-semibold rounded-sm min-w-[40px] transition-colors duration-200"
                            rel="prev" aria-label="{{ __('general.pagination.previous') }}">
                            <i class="fas fa-chevron-left py-1"></i>
                        </a>
                    @endif
                @endif

                {{-- Pages numbers --}}
                @php
                    $window = 2; // Number of pages before and after the current page
                    $currentPage = $paginator->currentPage();
                    $lastPage = $paginator->lastPage();
                @endphp

                {{-- First page + dots --}}
                @if($currentPage > ($window + 2))
                    @if (str_contains(request()->url(), 'livewire') || request()->routeIs('livewire*'))
                    <button type="button" wire:click="setPage(1)"
                        class="relative inline-flex items-center justify-center px-4 py-2 bg-indigo-50 text-[#490BF4] hover:text-white dark:text-white hover:bg-[#490BF4] dark:bg-indigo-500 text-sm font-semibold rounded-sm min-w-[40px] transition-colors duration-200">1</button>
                    @else
                    <a href="{{ route(request()->route()->getName(), array_merge(request()->query(), ['page' => 1, 'locale' => app()->getLocale()])) }}"
                        class="relative inline-flex items-center justify-center px-4 py-2 bg-indigo-50 text-[#490BF4] hover:text-white dark:text-white hover:bg-[#490BF4] dark:bg-indigo-500 text-sm font-semibold rounded-sm min-w-[40px] transition-colors duration-200">1</a>
                    @endif
                    <span
                        class="relative inline-flex items-center justify-center px-2 py-2 text-sm font-medium text-gray-600">...</span>
                @endif

                {{-- Pages around current page --}}
                @for($i = max(1, $currentPage - $window); $i <= min($lastPage, $currentPage + $window); $i++)
                    @if($i==$currentPage) <span
                        class="z-10 bg-indigo-500 text-white font-semibold dark:bg-[#490BF4] relative inline-flex items-center justify-center px-4 py-2 text-sm rounded-sm min-w-[40px]"
                        aria-current="page">{{ $i
                        }}</span>
                    @else
                        @if (str_contains(request()->url(), 'livewire') || request()->routeIs('livewire*'))
                            <button type="button" wire:click="setPage({{ $i }})"
                                class="relative inline-flex items-center justify-center px-4 py-2 bg-indigo-50 text-[#490BF4] hover:text-white hover:bg-[#490BF4] dark:text-white dark:bg-indigo-500 text-sm font-semibold rounded-sm min-w-[40px] transition-colors duration-200">{{
                                $i }}</button>
                        @else
                            <a href="{{ route(request()->route()->getName(), array_merge(request()->query(), ['page' => $i, 'locale' => app()->getLocale()])) }}"
                                class="relative inline-flex items-center justify-center px-4 py-2 bg-indigo-50 text-[#490BF4] hover:text-white dark:text-white hover:bg-[#490BF4] dark:bg-indigo-500 text-sm font-semibold rounded-sm min-w-[40px] transition-colors duration-200">{{
                                $i }}</a>
                        @endif
                    @endif
                @endfor

                {{-- Last page + dots --}}
                @if($currentPage < $lastPage - ($window + 1)) <span
                    class="relative inline-flex items-center justify-center px-2 py-2 text-sm font-medium text-gray-600">
                    ...</span>
                    @if (str_contains(request()->url(), 'livewire') || request()->routeIs('livewire*'))
                        <button type="button" wire:click="setPage({{ $lastPage }})"
                            class="relative inline-flex items-center justify-center px-4 py-2 bg-indigo-50 text-[#490BF4] hover:text-white hover:bg-[#490BF4] dark:text-white dark:bg-indigo-500 text-sm font-semibold rounded-sm min-w-[40px] transition-colors duration-200">{{
                            $lastPage
                            }}</button>
                    @else
                        <a href="{{ route(request()->route()->getName(), array_merge(request()->query(), ['page' => $lastPage, 'locale' => app()->getLocale()])) }}"
                            class="relative inline-flex items-center justify-center px-4 py-2 bg-indigo-50 text-[#490BF4] hover:text-white hover:bg-[#490BF4] dark:text-white dark:bg-indigo-500 text-sm font-semibold rounded-sm min-w-[40px] transition-colors duration-200">{{
                            $lastPage }}</a>
                    @endif
                @endif

                {{-- Next page --}}
                @if ($paginator->hasMorePages())
                    @if (str_contains(request()->url(), 'livewire') || request()->routeIs('livewire*'))
                        <button type="button" wire:click="setPage({{ $paginator->currentPage() + 1 }})"
                            class="relative inline-flex items-center justify-center px-4 py-2 bg-indigo-50 text-[#490BF4] hover:text-white hover:bg-[#490BF4] dark:text-white dark:bg-indigo-500 text-sm font-semibold rounded-sm min-w-[40px] transition-colors duration-200"
                            rel="next" aria-label="{{ __('general.pagination.next') }}">
                            <i class="fas fa-chevron-right py-1"></i>
                        </button>
                    @else
                        <a href="{{ route(request()->route()->getName(), array_merge(request()->query(), ['page' => $paginator->currentPage() + 1, 'locale' => app()->getLocale()])) }}"
                            class="relative inline-flex items-center justify-center px-4 py-2 bg-indigo-50 text-[#490BF4] hover:text-white hover:bg-[#490BF4] dark:text-white dark:bg-indigo-500 text-sm font-semibold rounded-sm min-w-[40px] transition-colors duration-200"
                            rel="next" aria-label="{{ __('general.pagination.next') }}">
                            <i class="fas fa-chevron-right py-1"></i>
                        </a>
                    @endif
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
