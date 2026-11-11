<?php

declare(strict_types=1);

namespace Tests\Feature\Application\Product\Queries;

use App\Application\Product\Queries\ListProducts;
use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\Product\DTO\ProductDTO;
use App\Domain\Product\DTO\ProductSearch;
use App\Domain\Product\Enums\ProductSort;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\Pagination\DTO\PageRequest;
use App\Models\InvoiceProduct;
use App\Models\Product;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class ListProductsTest extends TestCase
{
    use BuildsParties;

    #[Test]
    public function the_list_holds_only_the_users_products(): void
    {
        $owner = $this->frontendUser();
        $other = $this->frontendUser();
        $own = $this->productOf($owner, ['name' => 'Mine']);
        $this->productOf($other, ['name' => 'Theirs']);

        $names = $this->names($owner, $this->search());

        $this->assertSame([$own->name], $names);
    }

    #[Test]
    public function the_term_matches_the_name_or_the_description(): void
    {
        $owner = $this->frontendUser();
        $this->productOf($owner, ['name' => 'Garden hose']);
        $this->productOf($owner, ['name' => 'Rake', 'description' => 'For the garden']);
        $this->productOf($owner, ['name' => 'Hammer', 'description' => 'Tools']);

        $names = $this->names(
            $owner,
            $this->search(term: 'garden'),
        );

        $this->assertSame(['Garden hose', 'Rake'], $names);
    }

    #[Test]
    public function the_active_filter_and_the_exclusion_leave_products_out(): void
    {
        $owner = $this->frontendUser();
        $this->productOf($owner, ['name' => 'Active', 'is_active' => true]);
        $picked = $this->productOf($owner, ['name' => 'Picked', 'is_active' => true]);
        $this->productOf($owner, ['name' => 'Inactive', 'is_active' => false]);

        $names = $this->names(
            $owner,
            $this->search(
                active: true,
                excluded: [ProductId::fromInt($picked->id)],
            ),
        );

        $this->assertSame(['Active'], $names);
    }

    #[Test]
    public function the_list_sorts_by_the_number_of_invoices(): void
    {
        $owner = $this->frontendUser();
        $unused = $this->productOf($owner, ['name' => 'Unused']);
        $used = $this->productOf($owner, ['name' => 'Used']);
        $invoiceProductFactory = InvoiceProduct::factory();
        $invoiceProductFactory->create(['product_id' => $used->id]);

        $names = $this->names(
            $owner,
            $this->search(sort: ProductSort::INVOICES, ascending: false),
        );

        $this->assertSame([$used->name, $unused->name], $names);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function productOf(User $owner, array $attributes): Product
    {
        $productFactory = Product::factory();

        return $productFactory->create(['user_id' => $owner->id] + $attributes);
    }

    /**
     * @param  list<ProductId>  $excluded
     */
    private function search(
        ?string $term = null,
        ?bool $active = null,
        ProductSort $sort = ProductSort::NAME,
        bool $ascending = true,
        array $excluded = [],
    ): ProductSearch {
        return new ProductSearch(
            $term,
            $active,
            $sort,
            $ascending,
            $excluded,
        );
    }

    /**
     * @return list<string|null>
     */
    private function names(User $owner, ProductSearch $search): array
    {
        $useCases = app(AuthorizingUseCaseRunnerInterface::class);
        $page = $useCases->run(
            Actor::user(
                $this->idOf($owner),
            ),
            new ListProducts(
                $this->idOf($owner),
                $search,
                new PageRequest(1, 10),
            ),
        );

        return array_map(
            fn (ProductDTO $product): ?string => $product->name,
            $page->items(),
        );
    }
}
