<?php

declare(strict_types=1);

namespace App\Livewire\Shared;

/**
 * Sorting of a Livewire list by a column the list allows. $orderBy comes from the URL and
 * sortBy() from the browser, so both are untrusted: an unknown sort key never reaches the
 * query, it falls back to the default.
 *
 * The component declares the public properties `string $orderBy` and `bool $orderAsc`.
 */
trait Sorts
{
    /**
     * Sort keys the view offers, mapped to the column or alias the query sorts by.
     *
     * @return array<string, string>
     */
    abstract protected function sortableColumns(): array;

    abstract protected function defaultSort(): string;

    /** Sort by the given key, toggling the direction when it is already the current one */
    public function sortBy(string $key): void
    {
        if (! array_key_exists($key, $this->sortableColumns())) {
            return;
        }

        if ($this->sortKey() === $key) {
            $this->orderAsc = ! $this->orderAsc;

            return;
        }

        $this->orderBy = $key;
        $this->orderAsc = true;
    }

    /** The sort key in effect */
    protected function sortKey(): string
    {
        if (array_key_exists($this->orderBy, $this->sortableColumns())) {
            return $this->orderBy;
        }

        return $this->defaultSort();
    }

    protected function sortColumn(): string
    {
        return $this->sortableColumns()[$this->sortKey()];
    }

    protected function sortDirection(): string
    {
        return $this->orderAsc ? 'asc' : 'desc';
    }
}
