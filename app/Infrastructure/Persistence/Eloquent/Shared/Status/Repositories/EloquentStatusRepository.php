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
}
