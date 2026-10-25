<?php

namespace App\Infrastructure\Persistence\Eloquent\Shared\Status\Repositories;

use App\Models\Status;

class EloquentStatusRepository
{
    public function findIdBySlug(string $slug): ?int
    {
        return Status::where('slug', $slug)->value('id');
    }

    public function getAllForDropdown(): array
    {
        return Status::orderBy('name')->pluck('name', 'id')->toArray();
    }
}
