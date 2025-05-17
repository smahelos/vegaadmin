@if ($paginator->hasPages())
<nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="pagination-container">
    <div class="pagination-wrapper border-t border-gray-200 mt-4 pt-4 pb-4">
        <div class="pagination-info float-left">
            <p class="text-sm text-gray-700 leading-5">
                {{ __('general.pagination.shown') }}
                <span class="font-medium">{{ $paginator->firstItem() }}</span>
                {{ __('general.pagination.up_to') }}
                <span class="font-medium">{{ $paginator->lastItem() }}</span>
                {{ __('general.pagination.of') }}
                <span class="font-medium">{{ $paginator->total() }}</span>
                {{ __('general.pagination.items') }}
            </p>
        </div>

        <div class="pagination-controls float-right">
            <span class="pagination-links">
                {{-- Previous page --}}
                @if ($paginator->onFirstPage())
                <span class="pagination-btn pagination-btn-disabled" aria-disabled="true"
                    aria-label="{{ __('general.pagination.previous') }}">
                    <i class="fas fa-chevron-left py-1"></i>
                </span>
                @else
                @if (request()->routeIs('livewire*'))
                <button type="button" wire:click="setPage('{{ $paginator->currentPage() - 1 }}')" class="pagination-btn"
                    rel="prev" aria-label="{{ __('general.pagination.previous') }}">
                    <i class="fas fa-chevron-left py-1"></i>
                </button>
                @else
                <a href="@localizedRoute(Route::currentRouteName(), ['page' => $paginator->currentPage() - 1] + request()->except(['page', 'lang']))"
                    class="pagination-btn" rel="prev" aria-label="{{ __('general.pagination.previous') }}">
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
                @if (request()->routeIs('livewire*'))
                <button type="button" wire:click="setPage(1)" class="pagination-btn">1</button>
                @else
                <a href="@localizedRoute(Route::currentRouteName(), ['page' => 1] + request()->except(['page', 'lang']))"
                    class="pagination-btn">1</a>
                @endif
                <span class="pagination-dot">...</span>
                @endif

                {{-- Pages around actual page --}}
                @for($i = max(1, $currentPage - $window); $i <= min($lastPage, $currentPage + $window); $i++)
                    @if($i==$currentPage) <span class="pagination-btn pagination-btn-active" aria-current="page">{{ $i
                    }}</span>
            @else
            @if (request()->routeIs('livewire*'))
            <button type="button" wire:click="setPage({{ $i }})" class="pagination-btn">{{ $i }}</button>
            @else
            <a href="@localizedRoute(Route::currentRouteName(), ['page' => $i] + request()->except(['page', 'lang']))"
                class="pagination-btn">{{ $i }}</a>
            @endif
            @endif
            @endfor

            {{-- Last page + dots --}}
            @if($currentPage < $lastPage - ($window + 1)) <span class="pagination-dot">...</span>
                @if (request()->routeIs('livewire*'))
                <button type="button" wire:click="setPage({{ $lastPage }})" class="pagination-btn">{{ $lastPage
                    }}</button>
                @else
                <a href="@localizedRoute(Route::currentRouteName(), ['page' => $lastPage] + request()->except(['page', 'lang']))"
                    class="pagination-btn">{{ $lastPage }}</a>
                @endif
                @endif

                {{-- Next page --}}
                @if ($paginator->hasMorePages())
                @if (request()->routeIs('livewire*'))
                <button type="button" wire:click="setPage('{{ $paginator->currentPage() + 1 }}')" class="pagination-btn"
                    rel="next" aria-label="{{ __('general.pagination.next') }}">
                    <i class="fas fa-chevron-right py-1"></i>
                </button>
                @else
                <a href="@localizedRoute(Route::currentRouteName(), ['page' => $paginator->currentPage() + 1] + request()->except(['page', 'lang']))"
                    class="pagination-btn" rel="next" aria-label="{{ __('general.pagination.next') }}">
                    <i class="fas fa-chevron-right py-1"></i>
                </a>
                @endif
                @else
                <span class="pagination-btn pagination-btn-disabled" aria-disabled="true"
                    aria-label="{{ __('general.pagination.next') }}">
                    <i class="fas fa-chevron-right py-1"></i>
                </span>
                @endif
                </span>
        </div>
    </div>
</nav>
@endif
