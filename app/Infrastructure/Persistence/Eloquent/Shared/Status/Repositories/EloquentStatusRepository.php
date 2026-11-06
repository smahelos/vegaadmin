<?php

namespace App\Infrastructure\Persistence\Eloquent\Shared\Status\Repositories;

use App\Models\Status;

class EloquentStatusRepository
{
    public function findIdBySlug(string $slug): ?int
    {
        $query = Status::where('slug', $slug);

        return $query->value('id');
    }

    public function getAllForDropdown(): array
    {
        $query = Status::orderBy('name');
        $statuses = $query->pluck('name', 'id');

        return $statuses->toArray();
    }
}
