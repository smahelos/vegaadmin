<?php

declare(strict_types=1);

namespace App\Livewire\Product;

use App\Application\Product\Queries\ListProducts;
use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\Product\DTO\ProductSearch;
use App\Domain\Product\Enums\ProductSort;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Livewire\Shared\ActsForUser;
use App\Livewire\Shared\Paginates;
use App\Livewire\Shared\Sorts;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The user's products with search, an active/inactive filter and sorting.
 */
final class ProductList extends Component
{
    use ActsForUser;
    use Paginates;
    use Sorts;

    private const PER_PAGE = 10;

    private const SORTS = [
        ProductSort::CREATED,
        ProductSort::NAME,
        ProductSort::CATEGORY,
        ProductSort::PRICE,
        ProductSort::TAX,
        ProductSort::DEFAULT,
        ProductSort::INVOICES,
    ];

    #[Url]
    public string $search = '';

    /** '' for all products, 'active' or 'inactive' */
    #[Url]
    public string $status = '';

    #[Url(as: 'sort')]
    public string $orderBy = 'created_at';

    #[Url(as: 'direction')]
    public bool $orderAsc = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->resetPage();
    }

    protected function sortableColumns(): array
    {
        $columns = [];
        foreach (self::SORTS as $sort) {
            $columns[$sort->value] = $sort->value;
        }

        return $columns;
    }

    protected function defaultSort(): string
    {
        return ProductSort::CREATED->value;
    }

    public function render(AuthorizingUseCaseRunnerInterface $useCases): View
    {
        $owner = $this->userId();
        // A refusal thrown while the page renders the component would reach the browser as a view error
        abort_unless(
            $useCases->allowed(
                Actor::user($owner),
                ListProducts::class,
            ),
            403,
        );
        $active = match ($this->status) {
            'active' => true,
            'inactive' => false,
            default => null,
        };
        $products = $useCases->run(
            Actor::user($owner),
            new ListProducts(
                $owner,
                new ProductSearch(
                    $this->search,
                    $active,
                    ProductSort::from($this->sortColumn()),
                    $this->orderAsc,
                    [],
                ),
                new PageRequest($this->getPage(), self::PER_PAGE),
            ),
        );

        return view(
            'livewire.product.product-list',
            ['products' => $this->paginator($products)],
        );
    }
}
