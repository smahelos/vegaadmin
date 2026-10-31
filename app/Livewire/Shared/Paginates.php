<?php

declare(strict_types=1);

namespace App\Livewire\Shared;

use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\WithPagination;

/**
 * Livewire pagination rendered by Billex's own view, so every list shows the same
 * translated controls through the standard ->links().
 */
trait Paginates
{
    use WithPagination;

    public function paginationView(): string
    {
        return 'livewire.pagination';
    }

    /**
     * @template T
     *
     * @param  PaginatedResult<T>  $result
     * @return LengthAwarePaginator<int, T>
     */
    protected function paginator(PaginatedResult $result, string $pageName = 'page'): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            $result->items(),
            $result->total(),
            $result->perPage(),
            $result->currentPage(),
            ['pageName' => $pageName],
        );
    }
}
