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

    public function findById(int $id): ?Status
    {
        return Status::find($id);
    }

    public function findBySlug(string $slug): ?Status
    {
        return Status::where('slug', $slug)->first();
    }

    public function getStatusSlugIdMap(): array
    {
        return Status::query()->pluck('slug', 'id')->toArray();
    }

    public function getStatusIdSlugMap(): array
    {
        return Status::query()->pluck('id', 'slug')->toArray();
    }
}
