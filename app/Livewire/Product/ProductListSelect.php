<?php

declare(strict_types=1);

namespace App\Livewire\Product;

use App\Application\Product\Queries\GetProduct;
use App\Application\Product\Queries\ListProducts;
use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\Product\DTO\ProductSearch;
use App\Domain\Product\Enums\ProductSort;
use App\Domain\Product\Exceptions\ProductNotFoundException;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Livewire\Shared\ActsForUser;
use App\Livewire\Shared\Paginates;
use App\Livewire\Shared\Sorts;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Picks one of the user's products for an invoice line; the products already on the invoice are left out.
 */
final class ProductListSelect extends Component
{
    use ActsForUser;
    use Paginates;
    use Sorts;

    private const SORTS = [
        ProductSort::NAME,
        ProductSort::CATEGORY,
        ProductSort::PRICE,
        ProductSort::CURRENCY,
        ProductSort::TAX,
    ];

    #[Url(except: '')]
    public string $search = '';

    #[Url(as: 'sort', except: 'name')]
    public string $orderBy = 'name';

    #[Url(as: 'direction', except: true)]
    public bool $orderAsc = true;

    #[Url(except: 10)]
    public int $perPage = 10;

    /**
     * Ids of the products already on the invoice, sent by the invoice form's script.
     *
     * @var list<mixed>
     */
    public array $selectedProductIds = [];

    public ?string $errorMessage = null;

    /**
     * @var array<string, string>
     */
    protected $listeners = [
        'setSelectedProductIds' => 'handleSetSelectedProductIds',
    ];

    /**
     * Accepts the ids as they are dispatched: an array, or an array under 'ids'.
     */
    public function handleSetSelectedProductIds(mixed $params): void
    {
        if (is_array($params) && isset($params['ids']) && is_array($params['ids'])) {
            $params = $params['ids'];
        }
        if (is_array($params)) {
            $this->setSelectedProductIds($params);
        }
    }

    /**
     * Called by resources/js/product-selector.js.
     *
     * @param  array<mixed>  $ids
     */
    public function setSelectedProductIds(array $ids): void
    {
        $this->selectedProductIds = array_values($ids);
    }

    public function updatingSearch(): void
    {
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
        foreach (self::SORTS as $sort) {
            $columns[$sort->value] = $sort->value;
        }

        return $columns;
    }

    protected function defaultSort(): string
    {
        return ProductSort::NAME->value;
    }

    /**
     * Sends the product's line data to the invoice form and closes the picker.
     */
    public function selectProduct(AuthorizingUseCaseRunnerInterface $useCases, int $id): bool
    {
        $owner = $this->userId();
        abort_unless(
            $useCases->allowed(
                Actor::user($owner),
                GetProduct::class,
            ),
            403,
        );
        try {
            $product = $useCases->run(
                Actor::user($owner),
                new GetProduct(
                    $owner,
                    ProductId::fromInt($id),
                ),
            );
        } catch (ProductNotFoundException) {
            // Another user's product is answered like a missing one
            $this->errorMessage = __('products.messages.not_found');

            return false;
        }

        $this->dispatch(
            'product-selected',
            productData: [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price?->getAmount() ?? '0',
                'tax_rate' => $product->tax['rate'] ?? null,
                'unit' => $product->unit->value,
                'currency' => $product->currency ?? 'CZK',
            ],
        );
        $this->dispatch('closeModal');

        return true;
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
        // The ids come from the browser: only positive integers reach the query
        $excluded = [];
        foreach ($this->selectedProductIds as $id) {
            if (is_numeric($id) && (int) $id > 0) {
                $excluded[] = ProductId::fromInt((int) $id);
            }
        }
        $products = $useCases->run(
            Actor::user($owner),
            new ListProducts(
                $owner,
                new ProductSearch(
                    $this->search,
                    null,
                    ProductSort::from($this->sortColumn()),
                    $this->orderAsc,
                    $excluded,
                ),
                new PageRequest(
                    $this->getPage(),
                    max(1, $this->perPage),
                ),
            ),
        );

        return view(
            'livewire.product.product-list-select',
            ['products' => $this->paginator($products)],
        );
    }
}
