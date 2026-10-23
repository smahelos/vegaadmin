<?php

declare(strict_types=1);

namespace App\Livewire\Shared;

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
}
