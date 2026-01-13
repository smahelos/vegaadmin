<?php

namespace App\Infrastructure\Persistence\Eloquent\Shared\Status\Repositories;

use App\Models\StatusCategory;

class EloquentStatusCategoryRepository
{
    public function findIdBySlug(string $slug): ?int
    {
        return StatusCategory::where('slug', $slug)->value('id');
    }

    public function getAllForDropdown(): array
    {
        return StatusCategory::orderBy('name')->pluck('name', 'id')->toArray();
    }

    public function findById(int $id): ?StatusCategory
    {
        return StatusCategory::find($id);
    }

    public function findBySlug(string $slug): ?StatusCategory
    {
        return StatusCategory::where('slug', $slug)->first();
    }

    public function getStatusCategoriesSlugIdMap(): array
    {
        return StatusCategory::query()->pluck('slug', 'id')->toArray();
    }

    public function getStatusCategoriesIdSlugMap(): array
    {
        return StatusCategory::query()->pluck('id', 'slug')->toArray();
    }
}
