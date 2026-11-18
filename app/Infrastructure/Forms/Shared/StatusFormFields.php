<?php

declare(strict_types=1);

namespace App\Infrastructure\Forms\Shared;

use App\Models\Status;
use App\Models\StatusCategory;
use Illuminate\Database\Eloquent\Builder;

/**
 * Status options of admin filters: the statuses of one category, and the categories of the status list.
 */
final class StatusFormFields
{
    /**
     * Inactive statuses included: a filter finds stored records, which may hold a status no longer offered for new ones.
     *
     * @return array<int, string> status name by id
     */
    public static function names(string $category): array
    {
        $statusQuery = Status::whereHas(
            'category',
            fn (Builder $query): Builder => $query->where('slug', $category),
        );
        $names = $statusQuery->pluck('name', 'id');

        return $names->all();
    }

    /** @return array<string, string> */
    public static function categoryFilter(): array
    {
        return [
            'name' => 'category_id',
            'type' => 'select2',
            'label' => __('statuses.filters.category'),
        ];
    }

    /** @return array<int, string> category name by id */
    public static function categoryNames(): array
    {
        $categoryQuery = StatusCategory::query();
        $names = $categoryQuery->pluck('name', 'id');

        return $names->all();
    }
}
