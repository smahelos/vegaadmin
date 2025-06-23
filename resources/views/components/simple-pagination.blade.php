@if ($paginator->hasPages())
<nav role="navigation" aria-label="Pagination" class="pagination-container">
    <div class="pagination-wrapper">
        {{-- Previous page --}}
        @if (!$paginator->onFirstPage())
        @if (str_contains(request()->url(), 'livewire'))
        <button type="button" wire:click="setPage({{ $paginator->currentPage() - 1 }})"
            class="pagination-btn">←</button>
        @else
        <a href="?page={{ $paginator->currentPage() - 1 }}" class="pagination-btn">←</a>
        @endif
        @endif

        {{-- Page numbers --}}
        @foreach(range(1, $paginator->lastPage()) as $page)
        @if($page == $paginator->currentPage())
        <span class="pagination-btn pagination-btn-active">{{ $page }}</span>
        @else
        @if (str_contains(request()->url(), 'livewire'))
        <button type="button" wire:click="setPage({{ $page }})" class="pagination-btn">{{ $page }}</button>
        @else
        <a href="?page={{ $page }}" class="pagination-btn">{{ $page }}</a>
        @endif
        @endif
        @endforeach

        {{-- Next page --}}
        @if ($paginator->hasMorePages())
        @if (str_contains(request()->url(), 'livewire'))
        <button type="button" wire:click="setPage({{ $paginator->currentPage() + 1 }})"
            class="pagination-btn">→</button>
        @else
        <a href="?page={{ $paginator->currentPage() + 1 }}" class="pagination-btn">→</a>
        @endif
        @endif
    </div>
</nav>
@endif
