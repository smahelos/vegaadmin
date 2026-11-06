<?php

namespace App\Infrastructure\Persistence\Eloquent\User\Adapters;

use App\Domain\Shared\Pagination\DTO\PaginatedResult;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Adapter to convert domain PaginatedResult to Laravel LengthAwarePaginator
 * for UI/backpack needs. Keep this in Infrastructure.
 */
class PaginatorAdapter
{
    public static function toLengthAwarePaginator(PaginatedResult $result): LengthAwarePaginator
    {
        $urlGenerator = url();
        $path = $urlGenerator->current();
        $request = request();
        $query = $request->query();

        return new LengthAwarePaginator(
            items: $result->items(),
            total: $result->total(),
            perPage: $result->perPage(),
            currentPage: $result->currentPage(),
            options: [
                'path' => $path,
                'query' => $query,
            ],
        );
    }
}
