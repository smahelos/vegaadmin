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
                        <span class="pagination-btn pagination-btn-disabled" aria-disabled="true" aria-label="{{ __('general.pagination.previous') }}">
                            <i class="fas fa-chevron-left py-1"></i>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" class="pagination-btn" rel="prev" aria-label="{{ __('general.pagination.previous') }}">
                            <i class="fas fa-chevron-left py-1"></i>
                        </a>
                    @endif

                    {{-- Pages numbers --}}
                    @php
                        $window = 2; // Number of pages before and after the current page
                        $currentPage = $paginator->currentPage();
                        $lastPage = $paginator->lastPage();
                    @endphp

                    {{-- First page + dots --}}
                    @if($currentPage > ($window + 2))
                        <a href="{{ $paginator->url(1) }}" class="pagination-btn">1</a>
                        <span class="pagination-dot">...</span>
                    @endif

                    {{-- Pages around actual page --}}
                    @for($i = max(1, $currentPage - $window); $i <= min($lastPage, $currentPage + $window); $i++)
                        @if($i == $currentPage)
                            <span class="pagination-btn pagination-btn-active" aria-current="page">{{ $i }}</span>
                        @else
                            <a href="{{ $paginator->url($i) }}" class="pagination-btn">{{ $i }}</a>
                        @endif
                    @endfor

                    {{-- Last page + dots --}}
                    @if($currentPage < $lastPage - ($window + 1))
                        <span class="pagination-dot">...</span>
                        <a href="{{ $paginator->url($lastPage) }}" class="pagination-btn">{{ $lastPage }}</a>
                    @endif

                    {{-- Next page --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" class="pagination-btn" rel="next" aria-label="{{ __('general.pagination.next') }}">
                            <i class="fas fa-chevron-right py-1"></i>
                        </a>
                    @else
                        <span class="pagination-btn pagination-btn-disabled" aria-disabled="true" aria-label="{{ __('general.pagination.next') }}">
                            <i class="fas fa-chevron-right py-1"></i>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
