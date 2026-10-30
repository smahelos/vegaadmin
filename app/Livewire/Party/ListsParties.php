<?php

declare(strict_types=1);

namespace App\Livewire\Party;

use App\Domain\Party\DTO\PartySearch;
use App\Domain\Party\Enums\PartySort;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Livewire\Shared\Paginates;
use App\Livewire\Shared\Sorts;
use Livewire\Attributes\Url;

/**
 * The list behaviour businesses and contacts share: search, active or archived records, sorting by an allowed
 * column and pagination.
 */
trait ListsParties
{
    use Paginates;
    use Sorts;

    private const PER_PAGE = 10;

    #[Url]
    public string $search = '';

    #[Url]
    public bool $archived = false;

    #[Url(as: 'sort')]
    public string $orderBy = 'name';

    #[Url(as: 'direction')]
    public bool $orderAsc = true;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function showArchived(bool $archived): void
    {
        $this->archived = $archived;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    protected function sortableColumns(): array
    {
        $columns = [];
        foreach (PartySort::cases() as $sort) {
            $columns[$sort->value] = $sort->value;
        }

        return $columns;
    }

    protected function defaultSort(): string
    {
        return PartySort::NAME->value;
    }

    protected function partySearch(): PartySearch
    {
        return new PartySearch(
            $this->search,
            $this->archived,
            PartySort::from($this->sortColumn()),
            $this->orderAsc,
        );
    }

    protected function pageRequest(): PageRequest
    {
        return new PageRequest($this->getPage(), self::PER_PAGE);
    }
}
